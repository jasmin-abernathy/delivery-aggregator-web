<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function sans_effort_reply(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function sans_effort_b64url_encode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function sans_effort_b64url_decode(string $value): string|false
{
    $padding = strlen($value) % 4;
    if ($padding !== 0) {
        $value .= str_repeat('=', 4 - $padding);
    }
    return base64_decode(strtr($value, '-_', '+/'), true);
}

function sans_effort_secret(): string
{
    $secret = trim((string)(getenv('SANS_EFFORT_MAILING_SECRET') ?: ''));
    $home = trim((string)(getenv('HOME') ?: ''));
    $secretFile = $home !== ''
        ? rtrim($home, '/') . '/private/lepotager/mailing-subscribers-secret.php'
        : '';

    if ($secret === '' && $secretFile !== '' && is_file($secretFile)) {
        $loaded = require $secretFile;
        if (is_string($loaded)) {
            $secret = trim($loaded);
        }
    }

    return $secret;
}

function sans_effort_make_challenge(string $secret): array
{
    $left = random_int(2, 9);
    $right = random_int(1, 9);
    $payload = [
        'left' => $left,
        'right' => $right,
        'expires' => time() + 600,
        'nonce' => bin2hex(random_bytes(8)),
    ];

    $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
        throw new RuntimeException('challenge_encode_failed');
    }

    $encoded = sans_effort_b64url_encode($json);
    $signature = sans_effort_b64url_encode(hash_hmac('sha256', $encoded, $secret, true));

    return [
        'question' => sprintf('Combien font %d + %d ?', $left, $right),
        'token' => $encoded . '.' . $signature,
        'expires_in' => 600,
    ];
}

function sans_effort_validate_challenge(string $token, mixed $answer, string $secret): bool
{
    $parts = explode('.', trim($token), 2);
    if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
        return false;
    }

    [$encoded, $signature] = $parts;
    $expectedSignature = sans_effort_b64url_encode(hash_hmac('sha256', $encoded, $secret, true));
    if (!hash_equals($expectedSignature, $signature)) {
        return false;
    }

    $decoded = sans_effort_b64url_decode($encoded);
    if (!is_string($decoded)) {
        return false;
    }

    $payload = json_decode($decoded, true);
    if (!is_array($payload)) {
        return false;
    }

    $left = filter_var($payload['left'] ?? null, FILTER_VALIDATE_INT);
    $right = filter_var($payload['right'] ?? null, FILTER_VALIDATE_INT);
    $expires = filter_var($payload['expires'] ?? null, FILTER_VALIDATE_INT);
    $submitted = filter_var($answer, FILTER_VALIDATE_INT);

    if ($left === false || $right === false || $expires === false || $submitted === false) {
        return false;
    }
    if ($expires < time() || $expires > time() + 660) {
        return false;
    }

    return $submitted === ($left + $right);
}

$method = (string)($_SERVER['REQUEST_METHOD'] ?? '');
$secret = sans_effort_secret();
if ($secret === '') {
    sans_effort_reply(['ok' => false, 'error' => 'connector_not_configured'], 503);
}

if ($method === 'GET') {
    try {
        sans_effort_reply([
            'ok' => true,
            'anti_robot' => sans_effort_make_challenge($secret),
        ]);
    } catch (Throwable $e) {
        sans_effort_reply(['ok' => false, 'error' => 'challenge_unavailable'], 500);
    }
}

if ($method !== 'POST') {
    sans_effort_reply(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
if (!str_starts_with($contentType, 'application/json')) {
    sans_effort_reply(['ok' => false, 'error' => 'json_required'], 415);
}

$raw = file_get_contents('php://input');
$data = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($data)) {
    sans_effort_reply(['ok' => false, 'error' => 'invalid_json'], 400);
}

// Champ invisible : un robot qui le remplit obtient une réponse neutre sans inscription.
if (trim((string)($data['website'] ?? '')) !== '') {
    sans_effort_reply(['ok' => true, 'status' => 'accepted']);
}

if (!sans_effort_validate_challenge(
    (string)($data['anti_robot_token'] ?? ''),
    $data['anti_robot_answer'] ?? null,
    $secret
)) {
    sans_effort_reply(['ok' => false, 'error' => 'anti_robot_failed'], 422);
}

$email = strtolower(trim((string)($data['email'] ?? '')));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sans_effort_reply(['ok' => false, 'error' => 'invalid_email'], 422);
}

// Cette case est le consentement explicite : aucune inscription sans elle.
if (($data['consent'] ?? false) !== true) {
    sans_effort_reply(['ok' => false, 'error' => 'consent_required'], 422);
}

$endpoint = trim((string)(getenv('SANS_EFFORT_MAILING_ENDPOINT') ?: ''));
if ($endpoint === '') {
    $endpoint = 'https://www.lepotager.org/mailing/subscribers-api.php';
}

$payload = json_encode([
    'list' => 'sans-effort-testers',
    'email' => $email,
    'consent' => true,
    'consent_at' => gmdate('c'),
    'source' => 'sans-effort-survey',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if (!is_string($payload)) {
    sans_effort_reply(['ok' => false, 'error' => 'connector_error'], 500);
}

$timestamp = (string)time();
$signature = hash_hmac('sha256', $timestamp . "\n" . $payload, $secret);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'timeout' => 6,
        'ignore_errors' => true,
        'header' => [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-LPW-Timestamp: ' . $timestamp,
            'X-LPW-Signature: ' . $signature,
        ],
        'content' => $payload,
    ],
]);

$response = @file_get_contents($endpoint, false, $context);
$status = 0;
if (isset($http_response_header) && is_array($http_response_header) && isset($http_response_header[0])) {
    if (preg_match('/\s(\d{3})\s/', (string)$http_response_header[0], $m)) {
        $status = (int)$m[1];
    }
}

if (!is_string($response) || $status < 200 || $status >= 300) {
    sans_effort_reply(['ok' => false, 'error' => 'mailing_unavailable'], 502);
}

$decoded = json_decode($response, true);
if (!is_array($decoded) || empty($decoded['ok'])) {
    sans_effort_reply(['ok' => false, 'error' => 'mailing_unavailable'], 502);
}

sans_effort_reply([
    'ok' => true,
    'status' => (string)($decoded['status'] ?? 'subscribed'),
]);
