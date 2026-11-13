<?php
// Admin file uploads (raw episode audio/video, images, docs). Stored in uploads/media/ (never overwritten by a pull,
// blocked from direct web access). Downloads go through admin/file.php: signed-in admin, or a per-file secret link.
require_once __DIR__ . '/auth.php';

const DTM_UPLOAD_EXT = ['mp4','mov','m4v','mkv','webm','avi','mp3','m4a','wav','aac','flac','ogg','aif','aiff','png','jpg','jpeg','gif','webp','pdf','txt','srt','vtt','zip'];

function dtm_uploads_dir(): string {
  $d = DTM_ROOT . '/uploads';
  foreach (["$d", "$d/media", "$d/.tmp"] as $x) if (!is_dir($x)) @mkdir($x, 0755, true);
  if (!is_file("$d/.htaccess")) @file_put_contents("$d/.htaccess", "Require all denied\n");
  return $d;
}

function dtm_ini_bytes(string $v): int {
  $v = trim($v); if ($v === '') return 0;
  $n = (int)$v; $u = strtolower(substr($v, -1));
  return $u === 'g' ? $n * 1073741824 : ($u === 'm' ? $n * 1048576 : ($u === 'k' ? $n * 1024 : $n));
}

// Chunk size that fits under this server's PHP limits (leaves room for form overhead), between 1 MB and 16 MB.
function dtm_chunk_size(): int {
  $lim = [16 * 1048576];
  foreach (['upload_max_filesize', 'post_max_size'] as $k) { $b = dtm_ini_bytes((string)ini_get($k)); if ($b > 0) $lim[] = $b; }
  return max(1048576, min($lim) - 262144);
}

function dtm_safe_name(string $name): string {
  $name = basename(str_replace('\\', '/', $name));
  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  $base = pathinfo($name, PATHINFO_FILENAME);
  $base = trim(preg_replace('/[^A-Za-z0-9._ -]+/', '-', $base), " .-_");
  if ($base === '') $base = 'file';
  return substr($base, 0, 80) . ($ext !== '' ? ".$ext" : '');
}

function dtm_uploads(): array {
  $l = dtm_json_read('uploads.json');
  usort($l, fn($a, $b) => strcmp($b['uploaded'], $a['uploaded']));
  return $l;
}
function dtm_upload(string $id): ?array {
  foreach (dtm_uploads() as $u) if (hash_equals($u['id'], $id)) return $u;
  return null;
}

function dtm_human_size(int $b): string {
  foreach (['GB' => 1073741824, 'MB' => 1048576, 'KB' => 1024] as $u => $n) if ($b >= $n) return round($b / $n, 1) . " $u";
  return "$b B";
}

function dtm_site_base(): string {
  return rtrim((string)(dtm_secrets()['site_url'] ?? 'https://defacingthemusic.com'), '/');
}
