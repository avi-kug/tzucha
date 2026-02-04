<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * הגדרות מייל
 * חובה: App Password של גוגל (לא סיסמה רגילה!)
 */
$mailConfig = [
    'from_email'  => 'abk18180@gmail.com',
    'from_name'   => 'Tzucha',
    'smtp_host'   => 'smtp.gmail.com',
    'smtp_port'   => 587,
    'smtp_user'   => 'abk18180@gmail.com',
    'smtp_pass'   => 'dsjl ofbq pocl pdwe
', // ← כאן App Password (16 תווים)
    'smtp_secure' => PHPMailer::ENCRYPTION_STARTTLS,
];

/**
 * שליחת מייל דרך Gmail SMTP
 */
function send_mail(string $to, string $subject, string $body): bool
{
    global $mailConfig;

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
