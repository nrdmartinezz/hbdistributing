<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

require_once dirname(__DIR__) . '/vendor/autoload.php';

function readMailFile(string $path): array
{
    if (!is_readable($path)) {
        return [];
    }

    $config = require $path;

    return is_array($config) ? $config : [];
}

function loadMailConfig(): array
{
    $config = readMailFile(__DIR__ . '/../config.php');

    foreach ([
        dirname(__DIR__, 3) . '/private/site-mail.php',
        __DIR__ . '/../config.local.php',
    ] as $path) {
        $overlay = readMailFile($path);
        if ($overlay !== []) {
            $config = array_replace($config, $overlay);
        }
    }

    return $config;
}

function renderTemplate(string $filename, array $vars): string
{
    $filename = sanitizeTemplateFilename($filename);
    $path = dirname(__DIR__) . '/templates/' . $filename;
    if (!is_readable($path)) {
        throw new RuntimeException('Email template not found: ' . $filename);
    }

    $html = file_get_contents($path);
    if ($html === false) {
        throw new RuntimeException('Could not read template: ' . $filename);
    }

    foreach ($vars as $key => $value) {
        $html = str_replace('{{' . $key . '}}', (string) $value, $html);
    }

    return $html;
}

function sanitizeTemplateFilename(string $filename): string
{
    $basename = basename($filename);
    if (!preg_match('/^[a-zA-Z0-9._-]+\.html$/', $basename)) {
        throw new RuntimeException('Invalid template filename.');
    }

    return $basename;
}

/**
 * Pick notification/autoreply template for a form.
 * 1. Explicit mapping in config forms[form_type][notification|autoreply]
 * 2. Convention: {kind}-{form_type}.html if the file exists
 * 3. Fallback: {kind}.html
 */
function resolveTemplateForForm(array $config, string $formType, string $kind): string
{
    if (!in_array($kind, ['notification', 'autoreply'], true)) {
        throw new RuntimeException('Unknown template kind.');
    }

    $forms = $config['forms'] ?? [];
    if (!empty($forms[$formType][$kind])) {
        return sanitizeTemplateFilename((string) $forms[$formType][$kind]);
    }

    $convention = sanitizeTemplateFilename("{$kind}-{$formType}.html");
    if (is_readable(dirname(__DIR__) . '/templates/' . $convention)) {
        return $convention;
    }

    return sanitizeTemplateFilename("{$kind}.html");
}

/** @return list<string> */
function allowedFormTypes(array $config): array
{
    $configured = array_keys($config['forms'] ?? []);

    return array_values(array_unique(array_merge(['contact', 'sourcing'], $configured)));
}

function getFormMailSettings(array $config, string $formType): array
{
    $fromName = (string) ($config['from_name'] ?? 'Site');

    $defaults = [
        'contact' => [
            'source_label' => 'Contact form',
            'subject' => "New enquiry — {$fromName}",
            'autoreply_subject' => "We received your message — {$fromName}",
            'send_autoreply' => false,
        ],
        'sourcing' => [
            'source_label' => 'Strategic sourcing request',
            'subject' => "Strategic sourcing request — {$fromName}",
            'autoreply_subject' => "We received your sourcing request — {$fromName}",
            'send_autoreply' => false,
        ],
    ];

    $base = $defaults[$formType] ?? [
        'source_label' => $formType,
        'subject' => "New submission — {$fromName}",
        'autoreply_subject' => "Thank you for contacting {$fromName}",
        'send_autoreply' => false,
    ];

    $custom = $config['forms'][$formType] ?? [];
    $merged = array_merge($base, array_intersect_key($custom, $base));

    if (array_key_exists('send_autoreply', $custom)) {
        $merged['send_autoreply'] = (bool) $custom['send_autoreply'];
    } elseif (array_key_exists('send_autoreply', $config)) {
        $merged['send_autoreply'] = (bool) $config['send_autoreply'];
    } else {
        $merged['send_autoreply'] = false;
    }

    return $merged;
}

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** SMTP when host, user, and password are set; otherwise PHP mail() (WordPress-style). */
function usesSmtp(array $config): bool
{
    return !empty($config['smtp_host'])
        && !empty($config['smtp_user'])
        && !empty($config['smtp_pass']);
}

function configureMailer(PHPMailer $mail, array $config): void
{
    $mail->CharSet = PHPMailer::CHARSET_UTF8;

    if (usesSmtp($config)) {
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_user'];
        $mail->Password = $config['smtp_pass'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) ($config['smtp_port'] ?? 587);
        return;
    }

    $mail->isMail();
}

/** @return list<string> */
function parseEmailList(string|array $value): array
{
    $raw = is_array($value) ? $value : preg_split('/\s*,\s*/', trim($value));

    $emails = [];
    foreach ($raw as $item) {
        $item = trim((string) $item);
        if ($item !== '' && filter_var($item, FILTER_VALIDATE_EMAIL)) {
            $emails[] = $item;
        }
    }

    return $emails;
}

function sendMail(array $config, string|array $to, string $toName, string $subject, string $htmlBody, ?string $replyTo = null, ?string $replyToName = null, string|array|null $bcc = null): void
{
    $mail = new PHPMailer(true);
    $recipients = parseEmailList($to);
    $bccRecipients = parseEmailList($bcc ?? []);

    if ($recipients === []) {
        throw new RuntimeException('No valid recipient addresses.');
    }

    try {
        configureMailer($mail, $config);

        $mail->setFrom($config['from_email'], $config['from_name']);
        foreach ($recipients as $index => $address) {
            $mail->addAddress($address, $index === 0 ? $toName : '');
        }
        foreach ($bccRecipients as $address) {
            if (!in_array($address, $recipients, true)) {
                $mail->addBCC($address);
            }
        }
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        if ($replyTo) {
            $mail->addReplyTo($replyTo, $replyToName ?? '');
        }

        $mail->send();
    } catch (MailException $e) {
        error_log('PHPMailer error: ' . $mail->ErrorInfo);
        throw new RuntimeException('Failed to send email.');
    }
}

/** @return list<string> */
function formUploadRoots(): array
{
    return [
        dirname(__DIR__, 3) . '/private/form-uploads',
        dirname(__DIR__) . '/storage',
    ];
}

function locateFormUpload(string $token): ?string
{
    foreach (formUploadRoots() as $root) {
        $directory = $root . '/' . $token;
        if (is_dir($directory)) {
            return $directory;
        }
    }

    return null;
}

/**
 * Store one uploaded BOM outside the document root.
 * Returns null when the field was left empty.
 *
 * @param array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int} $file
 * @return array{token: string, name: string}|null
 */
function storeFormUpload(array $file): ?array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('That file is too large for the server to accept.');
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The file could not be uploaded. Please try again.');
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('The file could not be uploaded. Please try again.');
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 50 * 1024 * 1024) {
        throw new RuntimeException('That file is too large. The maximum size is 50MB.');
    }

    $original = basename(str_replace('\\', '/', (string) ($file['name'] ?? 'upload')));
    $original = preg_replace('/[^A-Za-z0-9._-]+/', '-', $original) ?? 'upload';
    $original = trim($original, '.-');
    if ($original === '') {
        $original = 'upload';
    }

    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $allowed = ['csv', 'xlsx', 'pdf', 'dwg', 'dxf', 'step', 'stp'];
    if (!in_array($extension, $allowed, true)) {
        throw new RuntimeException('Upload a CSV, XLSX, PDF, or CAD file.');
    }

    $token = bin2hex(random_bytes(16));
    $directory = null;
    foreach (formUploadRoots() as $root) {
        $candidate = $root . '/' . $token;
        if (!is_dir($candidate) && !mkdir($candidate, 0700, true) && !is_dir($candidate)) {
            continue;
        }
        $directory = $candidate;
        break;
    }
    if ($directory === null) {
        throw new RuntimeException('The file could not be saved. Please try again.');
    }

    $destination = $directory . '/' . $original;
    if (!move_uploaded_file($tmp, $destination)) {
        throw new RuntimeException('The file could not be saved. Please try again.');
    }

    return ['token' => $token, 'name' => $original];
}

function getClientIp(): string
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($parts[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }

    return 'unknown';
}

function checkRateLimit(string $ip, array $config): bool
{
    $window = (int) ($config['rate_limit_seconds'] ?? 60);
    $max = (int) ($config['rate_limit_max'] ?? 5);
    $dir = sys_get_temp_dir() . '/site-forms-rate-limit';

    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        return true;
    }

    $file = $dir . '/' . hash('sha256', $ip);
    $now = time();
    $hits = [];

    if (is_readable($file)) {
        $raw = file_get_contents($file);
        if ($raw !== false) {
            $hits = array_filter(
                array_map('intval', explode("\n", trim($raw))),
                static fn(int $ts) => ($now - $ts) < $window,
            );
        }
    }

    if (count($hits) >= $max) {
        return false;
    }

    $hits[] = $now;
    file_put_contents($file, implode("\n", $hits), LOCK_EX);

    return true;
}

function verifyRecaptcha(string $token, string $secret, float $minScore, string $remoteIp): bool
{
    if ($token === '') {
        return false;
    }

    $response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?' . http_build_query([
        'secret' => $secret,
        'response' => $token,
        'remoteip' => $remoteIp,
    ]));

    if ($response === false) {
        return false;
    }

    $result = json_decode($response, true);
    if (!is_array($result) || empty($result['success'])) {
        return false;
    }

    $action = (string) ($result['action'] ?? '');
    if ($action !== '' && $action !== 'submit') {
        return false;
    }

    return (float) ($result['score'] ?? 0) >= $minScore;
}

function verifyRecaptchaEnterprise(string $token, string $projectId, string $apiKey, string $siteKey, float $minScore, string $remoteIp): bool
{
    if ($token === '' || $projectId === '' || $apiKey === '' || $siteKey === '') {
        return false;
    }

    $payload = json_encode([
        'event' => [
            'token' => $token,
            'siteKey' => $siteKey,
            'expectedAction' => 'submit',
            'userIpAddress' => $remoteIp,
        ],
    ]);
    if ($payload === false) {
        return false;
    }

    $url = 'https://recaptchaenterprise.googleapis.com/v1/projects/'
        . rawurlencode($projectId)
        . '/assessments?key='
        . rawurlencode($apiKey);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 8,
            'ignore_errors' => true,
        ],
    ]);
    $response = file_get_contents($url, false, $context);
    if ($response === false) {
        return false;
    }

    $result = json_decode($response, true);
    if (!is_array($result)) {
        return false;
    }

    $properties = $result['tokenProperties'] ?? null;
    if (!is_array($properties) || empty($properties['valid'])) {
        return false;
    }
    if (($properties['action'] ?? '') !== 'submit') {
        return false;
    }

    $score = (float) ($result['riskAnalysis']['score'] ?? 0);

    return $score >= $minScore;
}
