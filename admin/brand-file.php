<?php
// Serves files Claude saved in the repo (brand/photos, brand/thumbnails) to a signed-in admin.
// Those folders are blocked from direct web access by their .htaccess.
require __DIR__ . '/../lib/auth.php';
dtm_require_admin();
$dir = (string)($_GET['d'] ?? ''); $name = basename((string)($_GET['f'] ?? ''));
$ok = ['photos', 'thumbnails'];
$path = DTM_ROOT . "/brand/$dir/$name";
if (!in_array($dir, $ok, true) || $name === '' || $name[0] === '.' || !is_file($path)) { http_response_code(404); exit('Not found.'); }
$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
$types = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'gif' => 'image/gif'];
if (!isset($types[$ext])) { http_response_code(404); exit('Not found.'); }
header('Content-Type: ' . $types[$ext]); header('X-Content-Type-Options: nosniff'); header('Cache-Control: private, max-age=3600');
header('Content-Disposition: ' . (isset($_GET['dl']) ? 'attachment' : 'inline') . '; filename="' . str_replace('"', '', $name) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
