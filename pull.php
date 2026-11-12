<?php
// Defacing The Music "Pull latest" for scripts: POST pull.php with field key=<deploy_key>.
// Holds no secret. The deploy key lives in ../dtm-secrets.php (server only, never in git).
// The admin's "Pull latest" button uses admin/deploy.php instead, so the key never reaches a browser.
require __DIR__ . '/lib/deploy.php';
$secretsFile = dirname(__DIR__) . '/dtm-secrets.php';
$cfg = is_file($secretsFile) ? require $secretsFile : [];
$key = (string)($cfg['deploy_key'] ?? '');
header('Content-Type: text/plain');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $key === '' || !hash_equals($key, (string)($_POST['key'] ?? ''))) { http_response_code(403); exit('nope'); }
[$ok, $msg] = dtm_deploy();
if (!$ok) http_response_code(500);
echo $msg, "\n";
