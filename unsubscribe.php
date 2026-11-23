<?php
// Guest List unsubscribe. GET shows a button (so link scanners can't unsubscribe people); POST removes.
// Gmail's one-click unsubscribe POSTs here too (List-Unsubscribe-Post).
require __DIR__ . '/lib/mailer.php';
$email = strtolower(trim((string)($_GET['e'] ?? $_POST['e'] ?? '')));
$tok = (string)($_GET['t'] ?? $_POST['t'] ?? '');
$valid = $email !== '' && dtm_mail_cfg()['key'] !== '' && hash_equals(dtm_unsub_token($email), $tok);
$done = false;
if ($valid && $_SERVER['REQUEST_METHOD'] === 'POST') { try { dtm_subscriber_remove($email); $done = true; } catch (Throwable $e) { $valid = false; } }
if ($done && isset($_POST['List-Unsubscribe'])) { http_response_code(200); exit('Unsubscribed.'); }
header('X-Robots-Tag: noindex');
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">
<title>Unsubscribe | Defacing the Music</title>
<link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png?v=2">
<link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&family=Oswald:wght@500;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/backstage.css?v=<?= @filemtime(__DIR__ . '/assets/backstage.css') ?>">
</head><body>
<main class="credits"><div class="wrap" style="max-width:640px;padding:80px 20px">
<?php if ($done): ?>
  <h1>You're off the list.</h1><p>No more emails from us. Security has escorted you out. <a href="/">Back to the site</a></p>
<?php elseif ($valid): ?>
  <h1>Leaving the guest list?</h1><p><?= htmlspecialchars($email) ?> will stop getting emails from Defacing the Music.</p>
  <form method="post"><input type="hidden" name="e" value="<?= htmlspecialchars($email) ?>"><input type="hidden" name="t" value="<?= htmlspecialchars($tok) ?>">
  <button type="submit" class="btn">Unsubscribe me</button></form>
<?php else: ?>
  <h1>That link didn't work.</h1><p>It may be old or cut off. Reply to any of our emails and we'll take you off by hand.</p>
<?php endif; ?>
</div></main></body></html>
