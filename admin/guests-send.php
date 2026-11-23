<?php
// Sends a Guest List mailing in small batches (the page calls this over and over until it's done),
// so a big list never hits the hosting's time limit. Each address gets one copy per mailing, even on retries.
require __DIR__ . '/../lib/mailer.php';
$user = dtm_require_admin();
header('Content-Type: application/json');
$out = function (array $a) { echo json_encode($a); exit; };
if ($_SERVER['REQUEST_METHOD'] !== 'POST') $out(['error' => 'POST only']);
dtm_check_csrf();
session_write_close();
if (!dtm_mail_ready()) $out(['error' => 'Gmail is not set up yet.']);
@set_time_limit(120);
$id = preg_replace('/[^a-f0-9]/', '', (string)($_POST['id'] ?? ''));
$log = dtm_json_read('mail-log.json');
$m = $log['mailings'][$id] ?? null;
if (!$m) $out(['error' => 'No such mailing.']);
$subs = array_map(fn($r) => strtolower($r['email']), dtm_subscribers());
$todo = array_values(array_diff($subs, $m['sent'] ?? [], array_keys($m['failed'] ?? [])));
$room = DTM_MAIL_DAILY_CAP - dtm_mail_sent_today($log);
if ($todo && $room <= 0) $out(['error' => "Gmail's daily limit is close, so I stopped. Come back tomorrow and hit Send again: it picks up where it left off.", 'sent' => count($m['sent'] ?? []), 'total' => count($subs)]);
$batch = array_slice($todo, 0, min(10, max(0, $room)));
$sent = []; $failed = []; $fatal = '';
if ($batch) {
  $smtp = new DtmSmtp(dtm_mail_cfg());
  try {
    $smtp->open();
    foreach ($batch as $to) {
      [$text, $html, $unsub] = dtm_mail_bodies($m['body'], $to);
      try { $smtp->send($to, $m['subject'], $text, $html, $unsub); $sent[] = $to; }
      catch (RuntimeException $e) { $failed[$to] = $e->getMessage(); }
    }
  } catch (RuntimeException $e) { $fatal = $e->getMessage(); }
  $smtp->close();
}
$log = dtm_json_update('mail-log.json', function ($log) use ($id, $sent, $failed, $todo, $batch, $fatal) {
  $now = time();
  $log['mailings'][$id]['sent'] = array_values(array_unique(array_merge($log['mailings'][$id]['sent'] ?? [], $sent)));
  $log['mailings'][$id]['failed'] = ($log['mailings'][$id]['failed'] ?? []) + $failed;
  foreach ($sent as $_) $log['sends'][] = $now;
  $log['sends'] = array_values(array_filter($log['sends'] ?? [], fn($t) => $t >= $now - 86400));
  if ($fatal === '' && $batch && count($todo) === count($batch)) $log['mailings'][$id]['finished'] = date('c');
  return $log;
});
$m = $log['mailings'][$id];
$out(['sent' => count($m['sent']), 'failed' => count($m['failed'] ?? []), 'total' => count($subs), 'done' => !empty($m['finished']) || (!$todo && $fatal === ''), 'error' => $fatal]);
