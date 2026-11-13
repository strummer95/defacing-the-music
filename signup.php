<?php
require __DIR__ . '/lib/https.php';
// Guest List signup: appends an email to data/subscribers.json (flock-locked).
// data/ is never overwritten by pull.php, and .htaccess blocks direct access to .json files.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ./#list'); exit; }

$back = fn($s) => header('Location: ./?signup=' . $s . '#list');

// Honeypot: bots fill every field. Pretend it worked.
if (trim((string)($_POST['website'] ?? '')) !== '') { $back('ok'); exit; }

$email = strtolower(trim((string)($_POST['email'] ?? '')));
if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $back('bad'); exit; }

$dir = __DIR__ . '/data';
if (!is_dir($dir) && !@mkdir($dir, 0755, true)) { $back('err'); exit; }
$file = "$dir/subscribers.json";
$fp = @fopen($file, 'c+');
if (!$fp || !flock($fp, LOCK_EX)) { $back('err'); exit; }

$raw = stream_get_contents($fp);
$list = json_decode($raw ?: '[]', true);
if (!is_array($list)) $list = [];

$exists = false;
foreach ($list as $row) { if (($row['email'] ?? '') === $email) { $exists = true; break; } }
if (!$exists) {
  $list[] = ['email' => $email, 'at' => date('c')];
  ftruncate($fp, 0);
  rewind($fp);
  fwrite($fp, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  fflush($fp);
}
flock($fp, LOCK_UN);
fclose($fp);
$back('ok');
