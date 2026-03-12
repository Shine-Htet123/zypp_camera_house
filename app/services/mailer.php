<?php

require_once __DIR__ . '/../../config/mail.php';

function mailer_bootstrap_phpmailer(): void
{
    if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        return;
    }

    $autoload = app_project_path('vendor/autoload.php');
    if (is_file($autoload)) {
        require_once $autoload;
    }

    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        $manualBase = app_project_path('config/PHPMailer-master/src');
        $exceptionFile = $manualBase . DIRECTORY_SEPARATOR . 'Exception.php';
        $phpmailerFile = $manualBase . DIRECTORY_SEPARATOR . 'PHPMailer.php';
        $smtpFile = $manualBase . DIRECTORY_SEPARATOR . 'SMTP.php';

        if (is_file($exceptionFile) && is_file($phpmailerFile) && is_file($smtpFile)) {
            require_once $exceptionFile;
            require_once $phpmailerFile;
            require_once $smtpFile;
        }
    }

    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        throw new RuntimeException('PHPMailer is not available. Install it with Composer or place PHPMailer-master under config/.');
    }
}

function mailer_validate_config(array $config): void
{
    if (trim((string) ($config['from_email'] ?? '')) === '') {
        throw new RuntimeException('Mail sender address is not configured.');
    }

    $smtp = $config['smtp'] ?? [];
    if (trim((string) ($smtp['host'] ?? '')) === '') {
        throw new RuntimeException('Mail SMTP host is not configured.');
    }

    if ((int) ($smtp['port'] ?? 0) <= 0) {
        throw new RuntimeException('Mail SMTP port is not configured.');
    }

    if (!empty($smtp['auth'])) {
        if (trim((string) ($smtp['username'] ?? '')) === '' || trim((string) ($smtp['password'] ?? '')) === '') {
            throw new RuntimeException('Mail SMTP username/password are not configured.');
        }
    }
}

function mailer_is_configured(): bool
{
    $config = mail_config();

    if (trim((string) ($config['from_email'] ?? '')) === '') {
        return false;
    }

    $smtp = $config['smtp'] ?? [];
    if (trim((string) ($smtp['host'] ?? '')) === '') {
        return false;
    }

    if ((int) ($smtp['port'] ?? 0) <= 0) {
        return false;
    }

    if (!empty($smtp['auth'])) {
        if (trim((string) ($smtp['username'] ?? '')) === '' || trim((string) ($smtp['password'] ?? '')) === '') {
            return false;
        }
    }

    return true;
}

function mailer_send(array $message): void
{
    mailer_bootstrap_phpmailer();

    $config = mail_config();
    mailer_validate_config($config);

    $smtp = $config['smtp'];

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = (string) $smtp['host'];
    $mail->Port = (int) $smtp['port'];
    $mail->SMTPAuth = !empty($smtp['auth']);
    $mail->Username = (string) ($smtp['username'] ?? '');
    $mail->Password = (string) ($smtp['password'] ?? '');
    $mail->CharSet = 'UTF-8';

    $encryption = strtolower(trim((string) ($smtp['encryption'] ?? '')));
    if ($encryption === 'ssl') {
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($encryption === 'tls') {
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    }

    $mail->setFrom((string) $config['from_email'], (string) ($config['from_name'] ?? 'ZYPP Camera House'));
    $mail->addAddress((string) ($message['to_email'] ?? ''), (string) ($message['to_name'] ?? ''));
    $mail->Subject = (string) ($message['subject'] ?? '');
    $mail->isHTML(true);
    $mail->Body = (string) ($message['html'] ?? '');
    $mail->AltBody = (string) ($message['text'] ?? strip_tags((string) ($message['html'] ?? '')));

    $mail->send();
}
