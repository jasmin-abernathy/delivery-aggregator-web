<?php
declare(strict_types=1);
require __DIR__ . '/survey-lib.php';

try {
    $pdo = se_db();
} catch (Throwable $e) {
    se_json(['ok' => false, 'error' => 'storage_unavailable'], 503);
}

$method = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method === 'GET') {
    se_json(['ok' => true, 'config' => se_survey_config()]);
}
if ($method !== 'POST') {
    se_json(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$data = se_request_json();
$action = (string)($data['action'] ?? 'save');
$localToken = trim((string)($data['local_token'] ?? ''));
$sessionToken = trim((string)($data['session_token'] ?? ''));

if ($action === 'load') {
    $respondent = se_session_respondent($pdo, $sessionToken, 'survey');
    if (!$respondent && $localToken !== '') {
        $respondent = se_find_respondent_by_local($pdo, $localToken);
    }
    if (!$respondent) {
        se_json(['ok' => true, 'config' => se_survey_config(), 'response' => null]);
    }

    $current = se_fetch_current_response($pdo, (int)$respondent['id']);
    $lastChanges = [];
    if ($current && (int)$current['version'] > 1) {
        $stmt = $pdo->prepare('SELECT * FROM responses WHERE respondent_id=? AND version=? LIMIT 1');
        $stmt->execute([(int)$respondent['id'], (int)$current['version'] - 1]);
        $previous = $stmt->fetch();
        if (is_array($previous)) {
            $lastChanges = se_visible_diff(
                se_decode_json_field($previous['answers_json'] ?? null),
                se_decode_json_field($current['answers_json'] ?? null)
            );
        }
    }

    se_json([
        'ok' => true,
        'config' => se_survey_config(),
        'response' => $current ? [
            'version' => (int)$current['version'],
            'complete' => (bool)$current['complete'],
            'answers' => se_decode_json_field($current['answers_json'] ?? null),
            'question_meta' => se_decode_json_field($current['question_meta_json'] ?? null),
            'change_comment' => (string)($current['change_comment'] ?? ''),
            'last_changes' => $lastChanges,
            'newsletter_opt_in' => (bool)$respondent['newsletter_opt_in'],
            'results_available' => (int)$respondent['current_complete'] === 1 && se_result_count($pdo) >= SE_RESULTS_THRESHOLD,
        ] : null,
    ]);
}

if ($action !== 'save') {
    se_json(['ok' => false, 'error' => 'unknown_action'], 422);
}

if ($localToken === '' || strlen($localToken) < 20) {
    se_json(['ok' => false, 'error' => 'local_token_required'], 422);
}

$answers = se_clean_answers($data['answers'] ?? []);
$comment = se_limit_text(trim((string)($data['change_comment'] ?? '')), 500);
$newsletterOptIn = ($data['newsletter_opt_in'] ?? false) === true;
$email = se_normalize_email((string)($data['email'] ?? ''));
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    se_json(['ok' => false, 'error' => 'invalid_email'], 422);
}

$respondent = se_session_respondent($pdo, $sessionToken, 'survey');
if (!$respondent) {
    $respondent = se_find_respondent_by_local($pdo, $localToken);
}

try {
    if (!$respondent) {
        if ($email !== '') {
            $existingByEmail = se_find_respondent_by_email($pdo, $email);
            if ($existingByEmail) {
                se_json(['ok' => false, 'error' => 'email_requires_authentication'], 409);
            }
        }
        $respondent = se_create_respondent($pdo, $localToken, $email !== '' ? $email : null);
    } elseif ($email !== '' && empty($respondent['identity_key'])) {
        $existingByEmail = se_find_respondent_by_email($pdo, $email);
        if ($existingByEmail && (int)$existingByEmail['id'] !== (int)$respondent['id']) {
            se_json(['ok' => false, 'error' => 'email_requires_authentication'], 409);
        }
        $encrypted = se_encrypt_email($email);
        $identity = se_identity_key($email);
        $stmt = $pdo->prepare('UPDATE respondents SET identity_key=?, email_cipher=?, updated_at=? WHERE id=?');
        $stmt->execute([$identity, $encrypted, se_now(), (int)$respondent['id']]);
        $respondent['identity_key'] = $identity;
        $respondent['email_cipher'] = $encrypted;
    }
} catch (Throwable $e) {
    se_json(['ok' => false, 'error' => 'identity_configuration_unavailable'], 503);
}

$current = se_fetch_current_response($pdo, (int)$respondent['id']);
$previousMeta = $current ? se_decode_json_field($current['question_meta_json'] ?? null) : [];
$currentMeta = se_current_question_meta();
$complete = se_is_complete($answers);
$now = se_now();
$createdNewVersion = false;

$pdo->beginTransaction();
try {
    if (!$current) {
        $stmt = $pdo->prepare('INSERT INTO responses(respondent_id,version,schema_version,answers_json,question_meta_json,change_comment,complete,submitted_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            (int)$respondent['id'], 1, SE_SURVEY_SCHEMA_VERSION,
            json_encode($answers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($currentMeta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $comment !== '' ? $comment : null, $complete ? 1 : 0, $now, $now,
        ]);
        $version = 1;
    } else {
        $version = (int)$current['version'];
        $oldAnswers = se_decode_json_field($current['answers_json'] ?? null);
        $oldMeta = se_decode_json_field($current['question_meta_json'] ?? null);
        $hasMeaningfulChange = $oldAnswers !== $answers || $oldMeta !== $currentMeta;

        if ((int)$current['complete'] === 0) {
            $stmt = $pdo->prepare('UPDATE responses SET schema_version=?,answers_json=?,question_meta_json=?,change_comment=?,complete=?,updated_at=? WHERE id=?');
            $stmt->execute([
                SE_SURVEY_SCHEMA_VERSION,
                json_encode($answers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                json_encode($currentMeta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $comment !== '' ? $comment : null, $complete ? 1 : 0, $now, (int)$current['id'],
            ]);
        } elseif ($hasMeaningfulChange) {
            $version++;
            $createdNewVersion = true;
            $stmt = $pdo->prepare('INSERT INTO responses(respondent_id,version,schema_version,answers_json,question_meta_json,change_comment,complete,submitted_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                (int)$respondent['id'], $version, SE_SURVEY_SCHEMA_VERSION,
                json_encode($answers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                json_encode($currentMeta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $comment !== '' ? $comment : null, $complete ? 1 : 0, $now, $now,
            ]);
        }
    }

    $latestComplete = $complete ? $version : (int)$respondent['latest_complete_version'];
    $stmt = $pdo->prepare('UPDATE respondents SET current_version=?, latest_complete_version=?, current_complete=?, newsletter_opt_in=?, reminder_sent_at=?, updated_at=?, last_activity_at=? WHERE id=?');
    $stmt->execute([
        $version, $latestComplete, $complete ? 1 : 0, $newsletterOptIn ? 1 : 0,
        $complete ? null : ($respondent['reminder_sent_at'] ?? null), $now, $now, (int)$respondent['id'],
    ]);

    if ($complete) {
        $stmt = $pdo->prepare('INSERT INTO result_access(respondent_id,granted_at) VALUES(?,?) ON CONFLICT(respondent_id) DO UPDATE SET granted_at=excluded.granted_at');
        $stmt->execute([(int)$respondent['id'], $now]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    se_json(['ok' => false, 'error' => 'save_failed'], 500);
}

$diff = [];
if ($current && $createdNewVersion) {
    $diff = se_visible_diff(se_decode_json_field($current['answers_json'] ?? null), $answers);
}

se_json([
    'ok' => true,
    'version' => $version,
    'complete' => $complete,
    'created_new_version' => $createdNewVersion,
    'changed_answers' => $diff,
    'results_available' => $complete && se_result_count($pdo) >= SE_RESULTS_THRESHOLD,
    'results_count' => se_result_count($pdo),
    'results_threshold' => SE_RESULTS_THRESHOLD,
]);
