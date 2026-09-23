<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/mailer.php';

$token = (string) ($_GET['token'] ?? '');
if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
    http_response_code(404);
    exit('File not found.');
}

$directory = locateFormUpload($token) ?? '';
$names = [];
if (is_dir($directory)) {
    foreach (scandir($directory) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $names[] = $entry;
    }
}

if (count($names) !== 1) {
    http_response_code(404);
    exit('File not found.');
}

$name = $names[0];
$path = $directory . '/' . $name;
$extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
$allowed = ['csv', 'xlsx', 'pdf', 'dwg', 'dxf', 'step', 'stp'];
if (!in_array($extension, $allowed, true) || !is_file($path)) {
    http_response_code(404);
    exit('File not found.');
}

$safeName = str_replace(["\r", "\n", '"'], '', $name);

header('X-Content-Type-Options: nosniff');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $safeName . '"');
header('Content-Length: ' . (string) filesize($path));
header('Cache-Control: private, no-store');

readfile($path);
