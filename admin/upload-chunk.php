<?php
// Receives one chunk of a file upload (JSON in/out). Chunks must arrive in order. Admin + CSRF only.
require __DIR__ . '/../lib/uploads.php';
header('Content-Type: application/json');
function out(int $code, array $d) { http_response_code($code); echo json_encode($d); exit; }

if (!dtm_user()) out(401, ['error' => 'Signed out. Reload and sign in again.']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, ['error' => 'POST only']);
dtm_session();
if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) out(400, ['error' => 'Form expired. Reload the page.']);
@set_time_limit(300);

$uid = (string)($_POST['upload_id'] ?? '');
$index = (int)($_POST['index'] ?? -1);
$total = (int)($_POST['total'] ?? 0);
$size = (int)($_POST['size'] ?? 0);
$name = dtm_safe_name((string)($_POST['name'] ?? ''));
$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
if (!preg_match('/^[a-f0-9]{24}$/', $uid)) out(400, ['error' => 'Bad upload id']);
if (!in_array($ext, DTM_UPLOAD_EXT, true)) out(400, ['error' => "Can't upload .$ext files here."]);
if ($total < 1 || $total > 100000 || $index < 0 || $index >= $total || $size < 0) out(400, ['error' => 'Bad chunk numbers']);
$f = $_FILES['chunk'] ?? null;
if (!$f || ($f['error'] ?? 1) !== UPLOAD_ERR_OK) out(400, ['error' => 'Chunk did not arrive (error ' . ($f['error'] ?? '?') . '). The server may limit upload size.']);

$dir = dtm_uploads_dir();
$part = "$dir/.tmp/$uid.part"; $metaF = "$dir/.tmp/$uid.json";
$meta = is_file($metaF) ? (json_decode((string)file_get_contents($metaF), true) ?: []) : [];
if ($index === 0) { $meta = ['name' => $name, 'total' => $total, 'size' => $size, 'next' => 0, 'started' => time()]; @unlink($part); }
if (!$meta || $meta['name'] !== $name || $meta['total'] !== $total) out(400, ['error' => 'Upload got out of sync. Start it again.']);
if ($index !== $meta['next']) {
  if ($index < $meta['next']) out(200, ['ok' => true, 'next' => $meta['next']]); // duplicate retry, already have it
  out(409, ['error' => 'Missing a chunk. Start it again.', 'next' => $meta['next']]);
}
$in = fopen($f['tmp_name'], 'rb'); $o = fopen($part, 'ab');
if (!$in || !$o || stream_copy_to_stream($in, $o) === false) out(500, ['error' => 'Could not save chunk. Is the disk full?']);
fclose($in); fclose($o);
$meta['next'] = $index + 1;
file_put_contents($metaF, json_encode($meta));

if ($meta['next'] < $total) out(200, ['ok' => true, 'next' => $meta['next']]);

// Last chunk: move into place and record it.
clearstatcache();
$got = filesize($part);
if ($size && $got !== $size) { @unlink($part); @unlink($metaF); out(400, ['error' => "Size mismatch ($got vs $size bytes). Try again."]); }
$file = $name; $i = 1;
while (file_exists("$dir/media/$file")) { $file = pathinfo($name, PATHINFO_FILENAME) . "-$i." . $ext; $i++; }
if (!rename($part, "$dir/media/$file")) out(500, ['error' => 'Could not finish the upload.']);
@unlink($metaF);
// clean up stale partial uploads older than a day
foreach (glob("$dir/.tmp/*") ?: [] as $t) if (filemtime($t) < time() - 86400) @unlink($t);
$u = dtm_user();
$rec = ['id' => bin2hex(random_bytes(8)), 'name' => $name, 'file' => $file, 'size' => $got, 'ext' => $ext,
        'uploaded' => date('c'), 'by' => $u['name'], 'token' => bin2hex(random_bytes(20)), 'note' => trim(mb_substr((string)($_POST['note'] ?? ''), 0, 200))];
dtm_json_update('uploads.json', function ($l) use ($rec) { $l[] = $rec; return $l; });
out(200, ['ok' => true, 'done' => true, 'id' => $rec['id']]);
