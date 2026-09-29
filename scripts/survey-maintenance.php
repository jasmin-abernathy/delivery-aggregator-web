<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/public/api/survey-lib.php';

$pdo = se_db();
$now = time();
$reminderBefore = gmdate('Y-m-d\TH:i:s\Z', $now - 7 * 86400);
$purgeBefore = gmdate('Y-m-d\TH:i:s\Z', $now - SE_INCOMPLETE_RETENTION_DAYS * 86400);

$reminders = $pdo->prepare('SELECT * FROM respondents WHERE current_complete=0 AND email_cipher IS NOT NULL AND reminder_sent_at IS NULL AND last_activity_at<=? AND last_activity_at>?');
$reminders->execute([$reminderBefore, $purgeBefore]);
$sent = 0;
foreach ($reminders->fetchAll() as $row) {
    $email = se_decrypt_email($row['email_cipher'] ?? null);
    if (!$email) continue;
    if (se_send_auth_mail($email, 'reminder', [
        'resume_url' => 'https://sanseffort.lepotager.org/survey.html',
        'retention_days' => SE_INCOMPLETE_RETENTION_DAYS,
    ])) {
        $pdo->prepare('UPDATE respondents SET reminder_sent_at=? WHERE id=?')->execute([se_now(), (int)$row['id']]);
        $sent++;
    }
}

$delete = $pdo->prepare('DELETE FROM respondents WHERE current_complete=0 AND last_activity_at<=?');
$delete->execute([$purgeBefore]);
$purged = $delete->rowCount();

$pdo->prepare('DELETE FROM otp_codes WHERE expires_at<? OR consumed_at IS NOT NULL')->execute([se_now()]);
$pdo->prepare('DELETE FROM sessions WHERE expires_at<?')->execute([se_now()]);

echo json_encode(['ok' => true, 'reminders_sent' => $sent, 'incomplete_purged' => $purged], JSON_UNESCAPED_SLASHES) . PHP_EOL;
