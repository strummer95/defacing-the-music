<?php
// Key-protected import for admin > Analytics (TikTok numbers from Defa, or any platform as a fallback).
// POST only. Key: header X-Import-Key or form field "key" (analytics_import_key in ../dtm-secrets.php, made on the
// Analytics page). Data: the request body, or form field "data". CSV with a header row, or JSON. See lib/analytics.php.
require __DIR__ . '/../lib/analytics.php';
header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');
$out = function (int $code, array $j) { http_response_code($code); echo json_encode($j, JSON_UNESCAPED_SLASHES), "\n"; exit; };
if ($_SERVER['REQUEST_METHOD'] !== 'POST') $out(405, ['ok' => false, 'error' => 'POST only.']);
$want = dtm_an_cfg()['import'];
$got = (string)($_SERVER['HTTP_X_IMPORT_KEY'] ?? ($_POST['key'] ?? ''));
if ($want === '' || $got === '' || !hash_equals($want, $got)) $out(403, ['ok' => false, 'error' => 'Bad or missing import key.']);
$data = isset($_POST['data']) ? (string)$_POST['data'] : (string)file_get_contents('php://input', false, null, 0, 1000000);
try {
  [$n, $errors] = dtm_an_import($data, (string)($_POST['platform'] ?? $_GET['platform'] ?? 'tiktok'));
} catch (RuntimeException $e) { $out(500, ['ok' => false, 'error' => $e->getMessage()]); }
$out($n > 0 ? 200 : 400, ['ok' => $n > 0, 'imported' => $n, 'errors' => $errors]);
