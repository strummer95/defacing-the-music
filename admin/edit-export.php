<?php
// The marked transcript of one edit, as plain text, for the editing session on Duck's PC.
// Allowed for a signed-in admin, or anyone holding the edit's secret link (?t=).
require __DIR__ . '/../lib/edits.php';
$p = dtm_edit(preg_replace('/[^a-f0-9]/', '', (string)($_GET['id'] ?? '')));
$tok = (string)($_GET['t'] ?? '');
if (!$p || !(dtm_user() || ($tok !== '' && hash_equals((string)$p['token'], $tok)))) { http_response_code(404); exit('Not found.'); }
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
if (isset($_GET['dl'])) header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9 _-]+/', '', $p['name']) . ' marked.md"');
echo dtm_edit_export($p);
