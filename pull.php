<?php
// Defacing The Music "Pull Latest": downloads strummer95/defacing-the-music@main (public repo, no token)
// and copies it into this folder. Holds no secret. The deploy key lives in ../dtm-secrets.php (server only, never in git):
//   <?php return ['deploy_key' => 'long-random-string', 'google_client_id' => '...', 'admin_emails' => ['...']];
// Call: POST pull.php with field key=<deploy_key>. The admin area (admin/) calls this after Google sign-in.
$secretsFile = dirname(__DIR__) . '/dtm-secrets.php';
$cfg = is_file($secretsFile) ? require $secretsFile : [];
$key = $cfg['deploy_key'] ?? '';
header('Content-Type: text/plain');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $key === '' || !hash_equals($key, (string)($_POST['key'] ?? ''))) { http_response_code(403); exit('nope'); }
$lock = fopen(sys_get_temp_dir() . '/dtm-pull.lock', 'c');
if (!flock($lock, LOCK_EX | LOCK_NB)) { http_response_code(429); exit('pull already running'); }

$repo = 'strummer95/defacing-the-music'; $branch = 'main';
$keep = ['.htaccess', 'data', 'uploads'];
$zipFile = sys_get_temp_dir() . '/dtm-' . time() . '.zip';
$ch = curl_init("https://api.github.com/repos/$repo/zipball/$branch");
$fp = fopen($zipFile, 'w');
curl_setopt_array($ch, [CURLOPT_FILE => $fp, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTPHEADER => ['User-Agent: dtm-pull', 'Accept: application/vnd.github+json']]);
curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); fclose($fp);
if ($code !== 200) { @unlink($zipFile); exit("GitHub download failed (HTTP $code)."); }
$zip = new ZipArchive();
if ($zip->open($zipFile) !== true) exit('Could not open zip.');
$tmp = sys_get_temp_dir() . '/dtm-x-' . time();
$zip->extractTo($tmp); $zip->close(); @unlink($zipFile);
$root = glob("$tmp/*", GLOB_ONLYDIR)[0] ?? null;
if (!$root) exit('Zip was empty.');
function dtm_copy($src, $dst, $keep, $top = true) {
  foreach (scandir($src) as $f) {
    if ($f === '.' || $f === '..' || $f === '.github') continue;
    if ($top && in_array($f, $keep, true)) continue;
    $s = "$src/$f"; $d = "$dst/$f";
    if (is_dir($s)) { if (!is_dir($d)) mkdir($d, 0755, true); dtm_copy($s, $d, $keep, false); } else copy($s, $d);
  }
}
function dtm_rm($p) { foreach (array_diff(scandir($p), ['.', '..']) as $f) { is_dir("$p/$f") ? dtm_rm("$p/$f") : unlink("$p/$f"); } rmdir($p); }
dtm_copy($root, __DIR__, $keep); dtm_rm($tmp);
echo "Pulled $repo@$branch at " . date('Y-m-d H:i:s T') . "\n";
