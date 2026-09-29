<?php
declare(strict_types=1);

const SE_SURVEY_SCHEMA_VERSION = '2026-09-29.1';
const SE_RESULTS_THRESHOLD = 7;
const SE_INCOMPLETE_RETENTION_DAYS = 30;
const SE_OTP_TTL_SECONDS = 900;
const SE_SESSION_TTL_SECONDS = 3600;

function se_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function se_request_json(): array
{
    $raw = file_get_contents('php://input');
    $data = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($data)) {
        se_json(['ok' => false, 'error' => 'invalid_json'], 400);
    }
    return $data;
}

function se_private_root(): string
{
    $configured = trim((string)(getenv('SANS_EFFORT_PRIVATE_ROOT') ?: ''));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }
    $home = trim((string)(getenv('HOME') ?: ''));
    if ($home !== '') {
        return rtrim($home, '/') . '/private/lepotager';
    }
    return dirname(__DIR__, 3) . '/private';
}

function se_load_secret(string $envName, string $fileName): string
{
    $secret = trim((string)(getenv($envName) ?: ''));
    if ($secret !== '') {
        return $secret;
    }
    $path = se_private_root() . '/' . $fileName;
    if (is_file($path)) {
        $loaded = require $path;
        if (is_string($loaded)) {
            return trim($loaded);
        }
    }
    return '';
}

function se_identity_secret(): string
{
    return se_load_secret('SANS_EFFORT_IDENTITY_SECRET', 'sans-effort-identity-secret.php');
}

function se_contact_secret(): string
{
    return se_load_secret('SANS_EFFORT_CONTACT_SECRET', 'sans-effort-contact-secret.php');
}

function se_mail_secret(): string
{
    return se_load_secret('SANS_EFFORT_AUTH_MAIL_SECRET', 'sans-effort-auth-mail-secret.php');
}

function se_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $root = se_private_root();
    if (!is_dir($root) && !mkdir($root, 0700, true) && !is_dir($root)) {
        throw new RuntimeException('private_storage_unavailable');
    }

    $path = $root . '/sans-effort-survey.sqlite';
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 4000');
    $pdo->exec('PRAGMA journal_mode = WAL');
    se_migrate($pdo);
    @chmod($path, 0600);
    return $pdo;
}

function se_migrate(PDO $pdo): void
{
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS respondents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    public_id TEXT NOT NULL UNIQUE,
    identity_key TEXT UNIQUE,
    local_token_hash TEXT UNIQUE,
    email_cipher TEXT,
    current_version INTEGER NOT NULL DEFAULT 1,
    latest_complete_version INTEGER NOT NULL DEFAULT 0,
    current_complete INTEGER NOT NULL DEFAULT 0,
    newsletter_opt_in INTEGER NOT NULL DEFAULT 0,
    reminder_sent_at TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    last_activity_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS responses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    respondent_id INTEGER NOT NULL,
    version INTEGER NOT NULL,
    schema_version TEXT NOT NULL,
    answers_json TEXT NOT NULL,
    question_meta_json TEXT NOT NULL,
    change_comment TEXT,
    complete INTEGER NOT NULL DEFAULT 0,
    submitted_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(respondent_id, version),
    FOREIGN KEY(respondent_id) REFERENCES respondents(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS result_access (
    respondent_id INTEGER PRIMARY KEY,
    granted_at TEXT NOT NULL,
    FOREIGN KEY(respondent_id) REFERENCES respondents(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS otp_codes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    respondent_id INTEGER NOT NULL,
    purpose TEXT NOT NULL,
    code_hash TEXT NOT NULL,
    expires_at TEXT NOT NULL,
    attempts INTEGER NOT NULL DEFAULT 0,
    consumed_at TEXT,
    created_at TEXT NOT NULL,
    FOREIGN KEY(respondent_id) REFERENCES respondents(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    respondent_id INTEGER NOT NULL,
    purpose TEXT NOT NULL,
    token_hash TEXT NOT NULL UNIQUE,
    expires_at TEXT NOT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY(respondent_id) REFERENCES respondents(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_responses_complete ON responses(complete, respondent_id, version);
CREATE INDEX IF NOT EXISTS idx_respondents_activity ON respondents(current_complete, last_activity_at);
CREATE INDEX IF NOT EXISTS idx_otp_lookup ON otp_codes(respondent_id, purpose, consumed_at, expires_at);
SQL);
}

function se_now(): string
{
    return gmdate('Y-m-d\TH:i:s\Z');
}

function se_random_token(int $bytes = 24): string
{
    return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
}

function se_normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function se_identity_key(string $email): string
{
    $secret = se_identity_secret();
    if ($secret === '') {
        throw new RuntimeException('identity_not_configured');
    }
    return hash_hmac('sha256', se_normalize_email($email), $secret);
}

function se_encrypt_email(string $email): string
{
    $secret = se_contact_secret();
    if ($secret === '') {
        throw new RuntimeException('contact_encryption_not_configured');
    }
    if (!function_exists('sodium_crypto_secretbox')) {
        throw new RuntimeException('sodium_unavailable');
    }
    $key = hash('sha256', $secret, true);
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $cipher = sodium_crypto_secretbox(se_normalize_email($email), $nonce, $key);
    return base64_encode($nonce . $cipher);
}

function se_decrypt_email(?string $encoded): ?string
{
    if (!$encoded) {
        return null;
    }
    $secret = se_contact_secret();
    if ($secret === '' || !function_exists('sodium_crypto_secretbox_open')) {
        return null;
    }
    $raw = base64_decode($encoded, true);
    if (!is_string($raw) || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
        return null;
    }
    $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $cipher = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $plain = sodium_crypto_secretbox_open($cipher, $nonce, hash('sha256', $secret, true));
    return is_string($plain) ? $plain : null;
}

function se_survey_config(): array
{
    return [
        'schema_version' => SE_SURVEY_SCHEMA_VERSION,
        'results_threshold' => SE_RESULTS_THRESHOLD,
        'incomplete_retention_days' => SE_INCOMPLETE_RETENTION_DAYS,
        'questions' => [
            [
                'id' => 'usage', 'revision' => 1, 'options_revision' => 1, 'essential' => true,
                'type' => 'single', 'title' => 'Dans quelle situation utiliseriez-vous surtout Sans Effort ?',
                'help' => 'Choisissez l’usage qui vous ressemble le plus aujourd’hui.',
                'options' => [
                    ['value' => 'meal', 'label' => 'Commander un repas'],
                    ['value' => 'grocery', 'label' => 'Faire des courses'],
                    ['value' => 'pickup', 'label' => 'Comparer avant un retrait'],
                    ['value' => 'antiwaste', 'label' => 'Trouver une option anti-gaspi'],
                    ['value' => 'compare', 'label' => 'Comparer sans commander tout de suite'],
                    ['value' => 'other', 'label' => 'Autre / préciser', 'other' => true],
                ],
            ],
            [
                'id' => 'priority', 'revision' => 1, 'options_revision' => 1, 'essential' => true,
                'type' => 'single', 'title' => 'Quand plusieurs offres correspondent, qu’est-ce qui compte le plus ?',
                'help' => 'Cela nous aide à régler le classement sans décider à votre place.',
                'options' => [
                    ['value' => 'total_price', 'label' => 'Le prix total le plus bas'],
                    ['value' => 'speed', 'label' => 'La livraison la plus rapide'],
                    ['value' => 'availability', 'label' => 'La disponibilité des produits'],
                    ['value' => 'rating', 'label' => 'La meilleure note disponible'],
                    ['value' => 'few_orders', 'label' => 'Le moins de commandes séparées'],
                    ['value' => 'other', 'label' => 'Autre / préciser', 'other' => true],
                ],
            ],
            [
                'id' => 'clarity', 'revision' => 1, 'options_revision' => 1, 'essential' => true,
                'type' => 'single', 'title' => 'La comparaison vous paraît-elle facile à comprendre ?',
                'help' => 'Pensez aux prix, frais, délais et statuts de données.',
                'options' => [
                    ['value' => 'yes', 'label' => 'Oui, clairement'],
                    ['value' => 'mostly', 'label' => 'Plutôt oui'],
                    ['value' => 'not_really', 'label' => 'Plutôt non'],
                    ['value' => 'no', 'label' => 'Non'],
                    ['value' => 'other', 'label' => 'Autre / préciser', 'other' => true],
                ],
            ],
            [
                'id' => 'trust', 'revision' => 1, 'options_revision' => 1, 'essential' => true,
                'type' => 'single', 'title' => 'Quelle information vous aiderait le plus à faire confiance au résultat ?',
                'help' => 'Une seule réponse suffit ; les autres informations pourront quand même être affichées.',
                'options' => [
                    ['value' => 'source', 'label' => 'La source et la fraîcheur des données'],
                    ['value' => 'fees', 'label' => 'Le détail exact des frais'],
                    ['value' => 'subscription', 'label' => 'L’effet de mes abonnements'],
                    ['value' => 'stock', 'label' => 'La disponibilité réelle des produits'],
                    ['value' => 'eta', 'label' => 'Des délais et créneaux fiables'],
                    ['value' => 'other', 'label' => 'Autre / préciser', 'other' => true],
                ],
            ],
            [
                'id' => 'handoff', 'revision' => 1, 'options_revision' => 1, 'essential' => true,
                'type' => 'single', 'title' => 'Le passage vers l’application officielle vous paraît-il clair ?',
                'help' => 'Sans Effort compare ; la commande reste finalisée dans le service choisi.',
                'options' => [
                    ['value' => 'yes', 'label' => 'Oui'],
                    ['value' => 'mostly', 'label' => 'Plutôt oui'],
                    ['value' => 'not_really', 'label' => 'Plutôt non'],
                    ['value' => 'no', 'label' => 'Non'],
                ],
            ],
            [
                'id' => 'list', 'revision' => 1, 'options_revision' => 1, 'essential' => false,
                'type' => 'multi', 'title' => 'Que voudriez-vous garder dans votre liste de référence ?',
                'help' => 'Question facultative. Plusieurs réponses sont possibles.',
                'options' => [
                    ['value' => 'items', 'label' => 'Plats ou produits'],
                    ['value' => 'quantity', 'label' => 'Quantités'],
                    ['value' => 'replacement', 'label' => 'Produits de remplacement'],
                    ['value' => 'constraints', 'label' => 'Contraintes ou préférences'],
                    ['value' => 'nothing', 'label' => 'Rien de plus'],
                    ['value' => 'other', 'label' => 'Autre / préciser', 'other' => true],
                ],
            ],
            [
                'id' => 'free_text', 'revision' => 1, 'options_revision' => 1, 'essential' => false,
                'type' => 'text', 'title' => 'Y a-t-il quelque chose qui vous gêne ou vous manque ?',
                'help' => 'Question facultative. Vous pouvez laisser ce champ vide.',
                'max_length' => 800,
            ],
        ],
    ];
}

function se_question_map(): array
{
    $map = [];
    foreach (se_survey_config()['questions'] as $question) {
        $map[$question['id']] = $question;
    }
    return $map;
}

function se_limit_text(string $value, int $length): string
{
    return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
}

function se_clean_answers(mixed $raw): array
{
    $incoming = is_array($raw) ? $raw : [];
    $clean = [];
    foreach (se_question_map() as $id => $question) {
        if (!array_key_exists($id, $incoming)) {
            continue;
        }
        $value = $incoming[$id];
        if ($question['type'] === 'multi') {
            $allowed = array_column($question['options'] ?? [], 'value');
            $values = is_array($value) ? $value : [];
            $selected = array_values(array_unique(array_filter(array_map('strval', $values), fn($v) => in_array($v, $allowed, true))));
            if ($selected) {
                $clean[$id] = $selected;
            }
        } elseif ($question['type'] === 'text') {
            $text = trim((string)$value);
            if ($text !== '') {
                $clean[$id] = se_limit_text($text, (int)($question['max_length'] ?? 800));
            }
        } else {
            $allowed = array_column($question['options'] ?? [], 'value');
            $selected = (string)$value;
            if (in_array($selected, $allowed, true)) {
                $clean[$id] = $selected;
            }
        }

        $otherKey = $id . '__other';
        if (array_key_exists($otherKey, $incoming)) {
            $other = trim((string)$incoming[$otherKey]);
            if ($other !== '') {
                $clean[$otherKey] = se_limit_text($other, 140);
            }
        }
    }
    return $clean;
}

function se_current_question_meta(): array
{
    $meta = [];
    foreach (se_question_map() as $id => $q) {
        $meta[$id] = [
            'revision' => (int)$q['revision'],
            'options_revision' => (int)$q['options_revision'],
            'essential' => (bool)$q['essential'],
        ];
    }
    return $meta;
}

function se_answer_present(array $answers, array $question): bool
{
    $id = $question['id'];
    if (!array_key_exists($id, $answers)) {
        return false;
    }
    $value = $answers[$id];
    return is_array($value) ? count($value) > 0 : trim((string)$value) !== '';
}

function se_is_complete(array $answers): bool
{
    foreach (se_survey_config()['questions'] as $question) {
        if (!$question['essential']) {
            continue;
        }
        if (!se_answer_present($answers, $question)) {
            return false;
        }
    }
    return true;
}

function se_fetch_current_response(PDO $pdo, int $respondentId): ?array
{
    $stmt = $pdo->prepare('SELECT r.* FROM responses r JOIN respondents p ON p.id=r.respondent_id WHERE r.respondent_id=? AND r.version=p.current_version LIMIT 1');
    $stmt->execute([$respondentId]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function se_find_respondent_by_local(PDO $pdo, string $localToken): ?array
{
    if ($localToken === '') {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM respondents WHERE local_token_hash=? LIMIT 1');
    $stmt->execute([hash('sha256', $localToken)]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function se_find_respondent_by_email(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM respondents WHERE identity_key=? LIMIT 1');
    $stmt->execute([se_identity_key($email)]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function se_create_respondent(PDO $pdo, string $localToken, ?string $email = null): array
{
    $now = se_now();
    $publicId = se_random_token(18);
    $identityKey = null;
    $emailCipher = null;
    if ($email !== null && $email !== '') {
        $identityKey = se_identity_key($email);
        $emailCipher = se_encrypt_email($email);
    }
    $stmt = $pdo->prepare('INSERT INTO respondents(public_id,identity_key,local_token_hash,email_cipher,created_at,updated_at,last_activity_at) VALUES(?,?,?,?,?,?,?)');
    $stmt->execute([$publicId, $identityKey, hash('sha256', $localToken), $emailCipher, $now, $now, $now]);
    $id = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare('SELECT * FROM respondents WHERE id=?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function se_session_respondent(PDO $pdo, string $token, string $purpose = ''): ?array
{
    if ($token === '') {
        return null;
    }
    $sql = 'SELECT p.* FROM sessions s JOIN respondents p ON p.id=s.respondent_id WHERE s.token_hash=? AND s.expires_at>?';
    $params = [hash('sha256', $token), se_now()];
    if ($purpose !== '') {
        $sql .= ' AND (s.purpose=? OR s.purpose=\'all\')';
        $params[] = $purpose;
    }
    $sql .= ' ORDER BY s.id DESC LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function se_decode_json_field(?string $value): array
{
    $decoded = is_string($value) ? json_decode($value, true) : null;
    return is_array($decoded) ? $decoded : [];
}

function se_visible_diff(array $older, array $newer): array
{
    $map = se_question_map();
    $diff = [];
    foreach ($map as $id => $question) {
        $old = $older[$id] ?? null;
        $new = $newer[$id] ?? null;
        $oldOther = $older[$id . '__other'] ?? null;
        $newOther = $newer[$id . '__other'] ?? null;
        if ($old === $new && $oldOther === $newOther) {
            continue;
        }
        $diff[] = ['id' => $id, 'title' => $question['title'], 'before' => $old, 'after' => $new, 'before_other' => $oldOther, 'after_other' => $newOther];
    }
    return $diff;
}

function se_result_count(PDO $pdo): int
{
    return (int)$pdo->query('SELECT COUNT(*) FROM respondents WHERE current_complete=1')->fetchColumn();
}

function se_send_auth_mail(string $email, string $kind, array $payload): bool
{
    $secret = se_mail_secret();
    if ($secret === '') {
        return false;
    }
    $endpoint = trim((string)(getenv('SANS_EFFORT_AUTH_MAIL_ENDPOINT') ?: ''));
    if ($endpoint === '') {
        $endpoint = 'https://www.lepotager.org/mailing/sans-effort-auth-api.php';
    }
    $body = json_encode(['email' => $email, 'kind' => $kind] + $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($body)) {
        return false;
    }
    $timestamp = (string)time();
    $signature = hash_hmac('sha256', $timestamp . "\n" . $body, $secret);
    $ctx = stream_context_create(['http' => [
        'method' => 'POST', 'timeout' => 8, 'ignore_errors' => true,
        'header' => [
            'Content-Type: application/json', 'Accept: application/json',
            'X-LPW-Timestamp: ' . $timestamp, 'X-LPW-Signature: ' . $signature,
        ],
        'content' => $body,
    ]]);
    $response = @file_get_contents($endpoint, false, $ctx);
    if (!is_string($response)) {
        return false;
    }
    $decoded = json_decode($response, true);
    return is_array($decoded) && !empty($decoded['ok']);
}
