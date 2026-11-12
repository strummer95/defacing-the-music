<?php
// Admin "Pull latest": signed-in admin + CSRF. Runs the same deploy as pull.php, server side, so the
// deploy key never reaches the browser.
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/deploy.php';
$user = dtm_require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
dtm_check_csrf();
@set_time_limit(180);
[$ok, $msg] = dtm_deploy();
dtm_json_update('deploys.json', function ($log) use ($ok, $msg, $user) {
  array_unshift($log, ['at' => date('Y-m-d H:i T'), 'by' => $user['name'], 'ok' => $ok, 'msg' => $msg]);
  return array_slice($log, 0, 20);
});
$_SESSION['flash'] = ['ok' => $ok, 'msg' => $msg];
header('Location: index.php');
