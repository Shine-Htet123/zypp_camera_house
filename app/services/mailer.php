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

function mailer_logo_path(): ?string
{
    $fallback = app_project_path('assets/images/browser-icon.png');
    if (is_file($fallback)) {
        return $fallback;
    }

    return null;
}

function mailer_logo_url(): string
{
    return app_url('/assets/images/browser-icon.png');
}

function mailer_resolve_logo_source(\PHPMailer\PHPMailer\PHPMailer $mail): string
{
    $logoPath = mailer_logo_path();
    if ($logoPath === null) {
        return '';
    }

    $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
    $mimeType = match ($extension) {
        'jpg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        default => 'image/png',
    };

    $contentId = 'zypp-brand-logo';

    try {
        $mail->addEmbeddedImage($logoPath, $contentId, basename($logoPath), 'base64', $mimeType);
        return 'cid:' . $contentId;
    } catch (\Throwable $exception) {
        return mailer_logo_url();
    }
}

function mailer_wrap_html(\PHPMailer\PHPMailer\PHPMailer $mail, string $html, string $subject): string
{
    $logoSource = mailer_resolve_logo_source($mail);
    $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');

    $logoMarkup = '';
    if ($logoSource !== '') {
        $logoMarkup = sprintf(
            '<div style="text-align:center;margin:0 0 20px;"><img src="%s" alt="ZYPP Camera House" style="max-width:140px;width:100%%;height:auto;display:inline-block;"></div>',
            htmlspecialchars($logoSource, ENT_QUOTES, 'UTF-8')
        );
    }

    return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>' . $safeSubject . '</title>
    <style>
        :root {
            color-scheme: light dark;
            supported-color-schemes: light dark;
        }

        body,
        table,
        td,
        div,
        p,
        a {
            font-family: Arial, Helvetica, sans-serif !important;
        }

        .email-shell {
            background: #ffffff;
            border-radius: 20px;
            padding: 32px 28px;
            box-shadow: 0 10px 28px rgba(72,45,22,0.10);
        }

        .dm-bg {
            background-color: #1b1b20 !important;
            background: #1b1b20 !important;
        }

        .dm-surface {
            background-color: #121317 !important;
            background: #121317 !important;
        }

        .dm-badge {
            background-color: #6f594f !important;
            background: #6f594f !important;
        }

        .dm-button {
            background-color: #e0bb97 !important;
            background: #e0bb97 !important;
            color: #211814 !important;
            -webkit-text-fill-color: #211814 !important;
        }

        .force-white,
        .force-white * {
            color: #fff8f2 !important;
            -webkit-text-fill-color: #fff8f2 !important;
        }

        .force-muted,
        .force-muted * {
            color: #ead9cb !important;
            -webkit-text-fill-color: #ead9cb !important;
        }

        .force-soft,
        .force-soft * {
            color: #dbc8ba !important;
            -webkit-text-fill-color: #dbc8ba !important;
        }

        .force-accent,
        .force-accent * {
            color: #ffd7a8 !important;
            -webkit-text-fill-color: #ffd7a8 !important;
        }

        .force-label,
        .force-label * {
            color: #d8c0ab !important;
            -webkit-text-fill-color: #d8c0ab !important;
        }

        .force-dim,
        .force-dim * {
            color: #bda999 !important;
            -webkit-text-fill-color: #bda999 !important;
        }

        [data-ogsc] .email-shell,
        [data-ogsb] .email-shell {
            background: #ffffff !important;
        }

        [data-ogsc] .dm-bg,
        [data-ogsb] .dm-bg {
            background: #1b1b20 !important;
        }

        [data-ogsc] .dm-surface,
        [data-ogsb] .dm-surface {
            background: #121317 !important;
        }

        [data-ogsc] .dm-badge,
        [data-ogsb] .dm-badge {
            background: #6f594f !important;
        }

        [data-ogsc] .dm-button,
        [data-ogsb] .dm-button {
            background: #e0bb97 !important;
            color: #211814 !important;
        }
    </style>
</head>
<body bgcolor="#f4f1ed" style="margin:0;padding:32px 16px;background:#f4f1ed;font-family:Arial,Helvetica,sans-serif;color:#2d241d;">
    <div style="max-width:640px;margin:0 auto;">
        <div class="email-shell" bgcolor="#ffffff" style="background:#ffffff;border-radius:20px;padding:32px 28px;box-shadow:0 10px 28px rgba(72,45,22,0.10);">
            ' . $logoMarkup . '
            <div style="font-size:15px;line-height:1.65;color:#3d3128;">
                ' . $html . '
            </div>
        </div>
        <p style="margin:18px 0 0;text-align:center;font-size:12px;line-height:1.5;color:#8d7c6d;">
            ZYPP Camera House
        </p>
    </div>
</body>
</html>';
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
    $htmlBody = (string) ($message['html'] ?? '');
    $mail->Body = $htmlBody !== '' ? mailer_wrap_html($mail, $htmlBody, (string) ($message['subject'] ?? '')) : '';
    $mail->AltBody = (string) ($message['text'] ?? strip_tags((string) ($message['html'] ?? '')));

    foreach ((array) ($message['embedded_images'] ?? []) as $embeddedImage) {
        $data = (string) ($embeddedImage['data'] ?? '');
        $contentId = trim((string) ($embeddedImage['cid'] ?? ''));
        $name = trim((string) ($embeddedImage['name'] ?? 'embedded-image'));
        $mimeType = trim((string) ($embeddedImage['mime'] ?? 'application/octet-stream'));

        if ($data === '' || $contentId === '') {
            continue;
        }

        $mail->addStringEmbeddedImage($data, $contentId, $name, 'base64', $mimeType);
    }

    foreach ((array) ($message['attachments'] ?? []) as $attachment) {
        $data = (string) ($attachment['data'] ?? '');
        $name = trim((string) ($attachment['name'] ?? 'attachment'));
        $mimeType = trim((string) ($attachment['mime'] ?? 'application/octet-stream'));

        if ($data === '') {
            continue;
        }

        $mail->addStringAttachment($data, $name, 'base64', $mimeType);
    }

    $mail->send();
}
