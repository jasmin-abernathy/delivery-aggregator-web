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

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
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

// Honeypot optionnel pour le futur formulaire public.
if (trim((string)($data['website'] ?? '')) !== '') {
    sans_effort_reply(['ok' => true, 'status' => 'accepted']);
}

$email = strtolower(trim((string)($data['email'] ?? '')));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sans_effort_reply(['ok' => false, 'error' => 'invalid_email'], 422);
}
if (($data['consent'] ?? false) !== true) {
    sans_effort_reply(['ok' => false, 'error' => 'consent_required'], 422);
}

$secret = trim((string)(getenv('SANS_EFFORT_MAILING_SECRET') ?: ''));
$home = trim((string)(getenv('HOME') ?: ''));
$secretFile = $home !== '' ? rtrim($home, '/') . '/private/lepotager/mailing-subscribers-secret.php' : '';
if ($secret === '' && $secretFile !== '' && is_file($secretFile)) {
    $loaded = require $secretFile;
    if (is_string($loaded)) {
        $secret = trim($loaded);
    }
}
if ($secret === '') {
    sans_effort_reply(['ok' => false, 'error' => 'connector_not_configured'], 503);
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
