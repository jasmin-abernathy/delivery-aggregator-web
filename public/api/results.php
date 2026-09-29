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
$sessionToken = trim((string)($data['session_token'] ?? ''));
$localToken = trim((string)($data['local_token'] ?? ''));
$respondent = se_session_respondent($pdo, $sessionToken, 'results');
if (!$respondent && $localToken !== '') {
    $respondent = se_find_respondent_by_local($pdo, $localToken);
}
if (!$respondent || (int)$respondent['current_complete'] !== 1) {
    se_json(['ok' => false, 'error' => 'authentication_required'], 401);
}

$count = se_result_count($pdo);
if ($count < SE_RESULTS_THRESHOLD) {
    se_json(['ok' => true, 'available' => false, 'count' => $count, 'threshold' => SE_RESULTS_THRESHOLD]);
}

$sql = 'SELECT r.answers_json FROM responses r JOIN respondents p ON p.id=r.respondent_id AND p.current_version=r.version WHERE p.current_complete=1';
$rows = $pdo->query($sql)->fetchAll();
$config = se_survey_config();
$aggregates = [];
foreach ($config['questions'] as $question) {
    if (!in_array($question['type'], ['single', 'multi'], true)) {
        continue;
    }
    $counts = [];
    foreach (($question['options'] ?? []) as $option) {
        $counts[$option['value']] = 0;
    }
    $answered = 0;
    foreach ($rows as $row) {
        $answers = se_decode_json_field($row['answers_json'] ?? null);
        if (!array_key_exists($question['id'], $answers)) {
            continue;
        }
        $value = $answers[$question['id']];
        $values = is_array($value) ? $value : [$value];
        if (!$values) continue;
        $answered++;
        foreach ($values as $selected) {
            if (array_key_exists((string)$selected, $counts)) {
                $counts[(string)$selected]++;
            }
        }
    }
    $options = [];
    foreach (($question['options'] ?? []) as $option) {
        $value = $option['value'];
        $n = $counts[$value] ?? 0;
        $options[] = [
            'value' => $value,
            'label' => $option['label'],
            'count' => $n,
            'percentage' => $answered > 0 ? round(($n / $answered) * 100, 1) : 0,
        ];
    }
    $aggregates[] = ['id' => $question['id'], 'title' => $question['title'], 'answered' => $answered, 'options' => $options];
}

se_json(['ok' => true, 'available' => true, 'count' => $count, 'threshold' => SE_RESULTS_THRESHOLD, 'results' => $aggregates]);
