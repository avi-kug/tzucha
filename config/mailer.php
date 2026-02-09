<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// Load .env if present (without external dependency)
$envPath = dirname(__DIR__) . '/.env';
if (is_readable($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        if ($key === '') {
            continue;
        }
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }
        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

/**
 * הגדרות מייל
 * חובה: App Password של גוגל (לא סיסמה רגילה!)
 */
$mailConfig = [
    'from_email'  => getenv('SMTP_FROM_EMAIL') ?: 'noreply@example.com',
    'from_name'   => getenv('SMTP_FROM_NAME') ?: 'Tzucha',
    'smtp_host'   => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    'smtp_port'   => (int)(getenv('SMTP_PORT') ?: 587),
    'smtp_user'   => getenv('SMTP_USER') ?: '',
    'smtp_pass'   => getenv('SMTP_PASS') ?: '',
    'smtp_secure' => getenv('SMTP_SECURE') ?: PHPMailer::ENCRYPTION_STARTTLS,
];

/**
 * שליחת מייל דרך Gmail SMTP
 */
function send_mail(string $to, string $subject, string $body): bool
{
    global $mailConfig;

    if (empty($mailConfig['smtp_user']) || empty($mailConfig['smtp_pass'])) {
        error_log('Mailer error: Missing SMTP credentials');
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        // מצב SMTP
        $mail->isSMTP();
        $mail->Host       = $mailConfig['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailConfig['smtp_user'];
        $mail->Password   = $mailConfig['smtp_pass'];
        $mail->SMTPSecure = $mailConfig['smtp_secure'];
        $mail->Port       = $mailConfig['smtp_port'];

        // קידוד
        $mail->CharSet = 'UTF-8';

        // שולח / נמען
        $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
        $mail->addAddress($to);

        // תוכן
        $mail->isHTML(false); // טקסט רגיל
        $mail->Subject = $subject;
        $mail->Body    = $body;

        // שליחה
        $mail->send();
        return true;

    } catch (Exception $e) {
        // לוג שגיאה (לא שקט!)
        error_log('Mailer error: ' . $mail->ErrorInfo);
        return false;
    }
}
