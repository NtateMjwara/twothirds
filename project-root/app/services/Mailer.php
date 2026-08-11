<?php
namespace app\services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

class Mailer
{
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $config = require __DIR__ . '/../config/config.php';
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $config['mail']['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['mail']['username'];
            $mail->Password   = $config['mail']['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $config['mail']['port'];

            $mail->setFrom($config['mail']['from_address'], $config['mail']['from_name']);
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;

            return $mail->send();
        } catch (PHPMailerException $e) {
            error_log('Mailer failed: ' . $mail->ErrorInfo);
            return false;
        }
    }
}
