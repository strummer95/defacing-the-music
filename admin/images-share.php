<?php
// One script's pictures as plain text (label, credit, picture link) for Claude, who can't sign in to the admin.
// Allowed for a signed-in admin, or anyone holding the private link from admin > Images (?t=).
require __DIR__ . '/../lib/cutaways.php';
$script = dtm_script((string)($_GET['id'] ?? ''));
$tok = (string)($_GET['t'] ?? '');
if (!$script || !(dtm_user() || ($tok !== '' && dtm_cut_share_ok((string)$script['id'], $tok)))) { http_response_code(404); exit('Not found.'); }
$tok = $tok !== '' ? $tok : dtm_cut_share_token((string)$script['id']);
$base = (dtm_is_https() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'defacingthemusic.com') . '/admin/cutaway.php';
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex');
echo '# Pictures for ' . $script['title'] . "\n\n";
foreach (dtm_cutaways($script, true) as $n => $im) {
  echo '## ' . ($n + 1) . '. ' . $im['label'] . ($im['kind'] === 'own' ? ' (Duck added this one)' : ' (shipped)') . ($im['hidden'] ? ' [hidden]' : '') . "\n";
  foreach (['title' => 'Title', 'author' => 'By', 'license' => 'License', 'source' => 'Source', 'when' => 'Show when'] as $k => $lbl) {
    $v = trim((string)($im[$k] ?? ''));
    echo $lbl . ': ' . ($v !== '' ? $v : '(none)') . "\n";
  }
  echo 'Picture: ' . $base . '?s=' . rawurlencode((string)$script['id']) . '&k=' . rawurlencode($im['key']) . '&t=' . rawurlencode($tok) . "\n\n";
}
