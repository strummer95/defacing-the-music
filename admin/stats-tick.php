<?php
// Hourly ping for admin > Stats: a free scheduler (like cron-job.org) opens this URL every hour so the 48-hour chart
// fills in even when nobody has the Stats page open. Key: stats_tick_key in ../dtm-secrets.php (made on the Stats page).
// It only fetches numbers (throttled to once an hour; Facebook/Instagram every 3 hours) and never prints anything secret.
require __DIR__ . '/../lib/analytics.php';
header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');
$want = dtm_an_cfg()['tick'];
$got = (string)($_GET['key'] ?? $_POST['key'] ?? '');
if ($want === '' || $got === '' || !hash_equals($want, $got)) { http_response_code(403); echo json_encode(['ok' => false]), "\n"; exit; }
$errors = dtm_an_refresh(false);
try { require_once __DIR__ . '/../lib/episodes.php'; dtm_episodes_autopublish(); } catch (Throwable $t) {}
echo json_encode(['ok' => true, 'fetched' => date('c', (int)(dtm_an_state()['fetched'] ?? 0)), 'problems' => array_keys($errors)]), "\n";
