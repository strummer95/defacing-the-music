<?php
// Copies strummer95/defacing-the-music@main (public repo, no token) onto this site folder.
// Used by pull.php (deploy key) and admin/deploy.php (signed-in admin). Never overwrites .htaccess, data/, uploads/.
function dtm_deploy(): array {
  date_default_timezone_set('America/Chicago');
  $lock = fopen(sys_get_temp_dir() . '/dtm-pull.lock', 'c');
  if (!flock($lock, LOCK_EX | LOCK_NB)) return [false, 'A pull is already running. Give it a minute.'];

  $repo = 'strummer95/defacing-the-music'; $branch = 'main';
  $keep = ['.htaccess', 'data', 'uploads'];
  $site = dirname(__DIR__);
  $zipFile = sys_get_temp_dir() . '/dtm-' . bin2hex(random_bytes(4)) . '.zip';
  $ch = curl_init("https://api.github.com/repos/$repo/zipball/$branch");
  $fp = fopen($zipFile, 'w');
  curl_setopt_array($ch, [CURLOPT_FILE => $fp, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 120,
    CURLOPT_HTTPHEADER => ['User-Agent: dtm-pull', 'Accept: application/vnd.github+json']]);
  curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); fclose($fp);
  if ($code !== 200) { @unlink($zipFile); return [false, "GitHub download failed (HTTP $code)."]; }

  $zip = new ZipArchive();
  if ($zip->open($zipFile) !== true) { @unlink($zipFile); return [false, 'Could not open the zip from GitHub.']; }
  $tmp = sys_get_temp_dir() . '/dtm-x-' . bin2hex(random_bytes(4));
  $zip->extractTo($tmp); $zip->close(); @unlink($zipFile);
  $root = glob("$tmp/*", GLOB_ONLYDIR)[0] ?? null;
  if (!$root) { dtm_deploy_rm($tmp); return [false, 'The zip from GitHub was empty.']; }

  $count = 0;
  dtm_deploy_copy($root, $site, $keep, true, $count);
  dtm_deploy_rm($tmp);
  $commit = basename($root); // e.g. strummer95-defacing-the-music-4c17e5a
  $short = substr($commit, strrpos($commit, '-') + 1);
  return [true, "Pulled $repo@$branch ($short), $count files, at " . date('Y-m-d H:i:s T') . '.'];
}

function dtm_deploy_copy(string $src, string $dst, array $keep, bool $top, int &$count): void {
  foreach (scandir($src) as $f) {
    if ($f === '.' || $f === '..' || $f === '.github' || $f === '.claude') continue;
    if ($top && in_array($f, $keep, true)) continue;
    $s = "$src/$f"; $d = "$dst/$f";
    if (is_dir($s)) { if (!is_dir($d)) mkdir($d, 0755, true); dtm_deploy_copy($s, $d, $keep, false, $count); }
    else { copy($s, $d); $count++; }
  }
}

function dtm_deploy_rm(string $p): void {
  foreach (array_diff(scandir($p), ['.', '..']) as $f) { is_dir("$p/$f") ? dtm_deploy_rm("$p/$f") : unlink("$p/$f"); }
  rmdir($p);
}
