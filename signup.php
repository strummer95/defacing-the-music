<?php
require __DIR__ . '/lib/auth.php';
// Guest List signup: appends an email to data/subscribers.json (flock-locked, via dtm_json_update).
// data/ is never overwritten by pull.php, and .htaccess blocks direct access to .json files.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ./#list'); exit; }

$back = fn($s) => header('Location: ./?signup=' . $s . '#list');

// Honeypot: bots fill every field. Pretend it worked.
if (trim((string)($_POST['website'] ?? '')) !== '') { $back('ok'); exit; }

$email = strtolower(trim((string)($_POST['email'] ?? '')));
if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $back('bad'); exit; }

try {
  dtm_json_update('subscribers.json', function ($list) use ($email) {
    foreach ($list as $row) if (strtolower((string)($row['email'] ?? '')) === $email) return null; // already on it
    $list[] = ['email' => $email, 'at' => date('c')];
    return $list;
  });
} catch (Throwable $e) { $back('err'); exit; }
$back('ok');
