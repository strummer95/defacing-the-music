<?php
// Serves one cutaway image to a signed-in admin: ?s=<script id>&k=<lib:file|own:file>[&dl=1].
// Shipped (lib) pictures are downloaded from Wikimedia on first use and cached in uploads/cutaways/.
require __DIR__ . '/../lib/cutaways.php';
dtm_require_admin();
session_write_close(); // don't block the other images on this page while one downloads
$script = dtm_script((string)($_GET['s'] ?? ''));
$want = (string)($_GET['k'] ?? '');
$im = null;
if ($script) foreach (dtm_cutaways($script, true) as $x) if ($x['key'] === $want) { $im = $x; break; }
if (!$im) { http_response_code(404); exit('Not found.'); }
$path = dtm_cut_path($script, $im);
if (!is_file($path) && ($im['kind'] !== 'lib' || !dtm_cut_fetch((string)($im['fetch'] ?? ''), $path))) {
  http_response_code(502); header('Retry-After: 30'); exit('Could not get that picture from Wikimedia right now. Reload in a minute.');
}
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
if (!isset(DTM_CUT_TYPES[$ext])) { http_response_code(404); exit('Not found.'); }
$name = preg_replace('/[^A-Za-z0-9._-]+/', '-', (dtm_seed_key($script) ?: 'script') . '-' . basename($path));
header('Content-Type: ' . DTM_CUT_TYPES[$ext]);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=86400');
header('Content-Disposition: ' . (isset($_GET['dl']) ? 'attachment' : 'inline') . '; filename="' . $name . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
