<?php
// Streams an uploaded file. Allowed for a signed-in admin, or anyone holding the file's secret link (?t=).
// Supports Range requests so video and audio can be played and resumed.
require __DIR__ . '/../lib/uploads.php';
$u = dtm_upload((string)($_GET['id'] ?? ''));
$tok = (string)($_GET['t'] ?? '');
if (!$u || !(dtm_user() || ($tok !== '' && hash_equals($u['token'], $tok)))) { http_response_code(404); exit('Not found.'); }
$path = dtm_uploads_dir() . '/media/' . $u['file'];
if (!is_file($path)) { http_response_code(404); exit('File is missing on the server.'); }
@set_time_limit(0);
while (ob_get_level()) ob_end_clean();
$size = filesize($path);
$types = ['mp4' => 'video/mp4', 'm4v' => 'video/mp4', 'mov' => 'video/quicktime', 'webm' => 'video/webm', 'mkv' => 'video/x-matroska',
          'mp3' => 'audio/mpeg', 'm4a' => 'audio/mp4', 'wav' => 'audio/wav', 'aac' => 'audio/aac', 'flac' => 'audio/flac', 'ogg' => 'audio/ogg',
          'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp', 'pdf' => 'application/pdf', 'txt' => 'text/plain'];
header('Content-Type: ' . ($types[$u['ext']] ?? 'application/octet-stream'));
header('X-Content-Type-Options: nosniff');
header('Accept-Ranges: bytes');
header('Cache-Control: private, no-store');
$disp = isset($_GET['dl']) ? 'attachment' : 'inline';
header("Content-Disposition: $disp; filename=\"" . str_replace('"', '', $u['name']) . '"');
$start = 0; $end = $size - 1;
if (preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'] ?? '', $m) && ($m[1] !== '' || $m[2] !== '')) {
  if ($m[1] === '') { $start = max(0, $size - (int)$m[2]); } else { $start = (int)$m[1]; if ($m[2] !== '') $end = min($end, (int)$m[2]); }
  if ($start > $end || $start >= $size) { http_response_code(416); header("Content-Range: bytes */$size"); exit; }
  http_response_code(206);
  header("Content-Range: bytes $start-$end/$size");
}
header('Content-Length: ' . ($end - $start + 1));
$fp = fopen($path, 'rb'); fseek($fp, $start); $left = $end - $start + 1;
while ($left > 0 && !feof($fp) && !connection_aborted()) { $buf = fread($fp, min(1048576, $left)); echo $buf; flush(); $left -= strlen($buf); }
fclose($fp);
