<?php
declare(strict_types=1);
require __DIR__ . '/survey-lib.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    se_json(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

try {
    $pdo = se_db();
} catch (Throwable $e) {
    se_json(['ok' => false, 'error' => 'storage_unavailable'], 503);
}

$data = se_request_json();
$action = (string)($data['action'] ?? 'request');
$email = se_normalize_email((string)($data['email'] ?? ''));
$purpose = (string)($data['purpose'] ?? 'results');
if (!in_array($purpose, ['results', 'survey'], true)) {
    se_json(['ok' => false, 'error' => 'invalid_purpose'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    se_json(['ok' => false, 'error' => 'invalid_email'], 422);
}

try {
    $respondent = se_find_respondent_by_email($pdo, $email);
} catch (Throwable $e) {
    se_json(['ok' => false, 'error' => 'identity_configuration_unavailable'], 503);
}

if ($action === 'request') {
    // Réponse neutre pour ne pas révéler qu'une adresse a participé.
    if (!$respondent || ($purpose === 'results' && (int)$respondent['current_complete'] !== 1)) {
        se_json(['ok' => true, 'status' => 'code_if_eligible'], 202);
    }

    $recent = $pdo->prepare('SELECT created_at FROM otp_codes WHERE respondent_id=? AND purpose=? ORDER BY id DESC LIMIT 1');
    $recent->execute([(int)$respondent['id'], $purpose]);
    $last = $recent->fetchColumn();
    if (is_string($last) && strtotime($last) !== false && strtotime($last) > time() - 60) {
        se_json(['ok' => true, 'status' => 'code_if_eligible'], 202);
    }

    $code = (string)random_int(100000, 999999);
    $hash = password_hash($code, PASSWORD_DEFAULT);
    $expires = gmdate('Y-m-d\TH:i:s\Z', time() + SE_OTP_TTL_SECONDS);
    $stmt = $pdo->prepare('INSERT INTO otp_codes(respondent_id,purpose,code_hash,expires_at,created_at) VALUES(?,?,?,?,?)');
    $stmt->execute([(int)$respondent['id'], $purpose, $hash, $expires, se_now()]);

    $sent = se_send_auth_mail($email, 'otp', [
        'code' => $code,
        'expires_minutes' => 15,
        'purpose' => $purpose,
    ]);
    if (!$sent) {
        se_json(['ok' => false, 'error' => 'mail_unavailable'], 503);
    }
    se_json(['ok' => true, 'status' => 'code_if_eligible'], 202);
}

if ($action !== 'verify') {
    se_json(['ok' => false, 'error' => 'unknown_action'], 422);
}

$code = preg_replace('/\D+/', '', (string)($data['code'] ?? ''));
if (strlen($code) !== 6 || !$respondent) {
    se_json(['ok' => false, 'error' => 'invalid_or_expired_code'], 422);
}
if ($purpose === 'results' && (int)$respondent['current_complete'] !== 1) {
    se_json(['ok' => false, 'error' => 'not_eligible'], 403);
}

$stmt = $pdo->prepare('SELECT * FROM otp_codes WHERE respondent_id=? AND purpose=? AND consumed_at IS NULL AND expires_at>? ORDER BY id DESC LIMIT 1');
$stmt->execute([(int)$respondent['id'], $purpose, se_now()]);
$otp = $stmt->fetch();
if (!is_array($otp) || (int)$otp['attempts'] >= 5) {
    se_json(['ok' => false, 'error' => 'invalid_or_expired_code'], 422);
}

$pdo->prepare('UPDATE otp_codes SET attempts=attempts+1 WHERE id=?')->execute([(int)$otp['id']]);
if (!password_verify($code, (string)$otp['code_hash'])) {
    se_json(['ok' => false, 'error' => 'invalid_or_expired_code'], 422);
}

$token = se_random_token(32);
$expires = gmdate('Y-m-d\TH:i:s\Z', time() + SE_SESSION_TTL_SECONDS);
$pdo->beginTransaction();
try {
    $pdo->prepare('UPDATE otp_codes SET consumed_at=? WHERE id=?')->execute([se_now(), (int)$otp['id']]);
    $pdo->prepare('INSERT INTO sessions(respondent_id,purpose,token_hash,expires_at,created_at) VALUES(?,?,?,?,?)')
        ->execute([(int)$respondent['id'], $purpose, hash('sha256', $token), $expires, se_now()]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    se_json(['ok' => false, 'error' => 'session_failed'], 500);
}

se_json(['ok' => true, 'session_token' => $token, 'expires_in' => SE_SESSION_TTL_SECONDS, 'purpose' => $purpose]);
