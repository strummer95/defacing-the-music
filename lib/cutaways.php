<?php
// Cutaway images (admin > Scripts > Images) and the data behind the public Sources & Credits pages (/ep/N).
//
// Shipped picks:  lib/cutaways/<seed>.json  Wikimedia Commons only, CC BY / BY-SA / CC0 / public domain, with credits.
//                 The pictures are not in git: the server downloads each one from Wikimedia the first time it's
//                 opened and keeps it in uploads/cutaways/lib/<seed>/ (never overwritten by a pull, no direct web access).
// Shipped sources: lib/sources/<seed>.json  {"sources": [{"title", "publisher", "url"}]}
// Duck's changes: data/cutaways.json, keyed by script id: {"hidden": [shipped file names], "added": [his own images],
//                 "labels": {"lib:<file>"|"own:<file>": {"label": "his name for it", "when": "where it goes"}}}.
//                 His own images live in uploads/cutaways/own/<script id>/. Extra sources are a field on the script.
require_once __DIR__ . '/uploads.php';
require_once __DIR__ . '/episodes.php';

const DTM_CUT_TYPES = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
const DTM_CUT_MAX_BYTES = 30 * 1048576;

function dtm_seed_key(array $s): string {
  $k = (string)($s['seed'] ?? '');
  return preg_match('/^[a-z0-9-]{1,60}$/', $k) ? $k : '';
}
function dtm_safe_id(string $id): string { return preg_match('/^[A-Za-z0-9_-]{1,40}$/', $id) ? $id : ''; }

function dtm_lib_json(string $dir, string $key): array {
  if ($key === '') return [];
  $f = DTM_ROOT . "/lib/$dir/$key.json";
  $j = is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
  return is_array($j) ? $j : [];
}

function dtm_cut_state(string $scriptId): array {
  $all = dtm_json_read('cutaways.json');
  $st = is_array($all[$scriptId] ?? null) ? $all[$scriptId] : [];
  return $st + ['hidden' => [], 'added' => [], 'labels' => []];
}
function dtm_cut_state_update(string $scriptId, callable $fn): void {
  dtm_json_update('cutaways.json', function ($all) use ($scriptId, $fn) {
    $st = (is_array($all[$scriptId] ?? null) ? $all[$scriptId] : []) + ['hidden' => [], 'added' => [], 'labels' => []];
    $all[$scriptId] = $fn($st);
    return $all;
  });
}

// Every image for a script, shipped ones first (story order), then Duck's own.
// Each row: file, title, author, license, license_url, source, kind ('lib'|'own'), key (lib:<file> / own:<file>), hidden.
function dtm_cutaways(array $script, bool $withHidden = false): array {
  $st = dtm_cut_state((string)$script['id']);
  $out = [];
  foreach ((dtm_lib_json('cutaways', dtm_seed_key($script))['images'] ?? []) as $im) {
    if (!is_array($im) || !isset($im['file'])) continue;
    $hidden = in_array($im['file'], $st['hidden'], true);
    if ($hidden && !$withHidden) continue;
    $out[] = $im + ['kind' => 'lib', 'key' => 'lib:' . $im['file'], 'hidden' => $hidden];
  }
  foreach ($st['added'] as $im) if (is_array($im) && isset($im['file'])) $out[] = $im + ['kind' => 'own', 'key' => 'own:' . $im['file'], 'hidden' => false];
  // Duck's labels (admin only): 'label' is his name for the picture, 'when' says where it goes in the episode.
  // The credit 'title' stays as it was, so the public Sources & Credits page keeps the real title.
  foreach ($out as &$im) { $lb = $st['labels'][$im['key']] ?? []; $im['label'] = trim((string)($lb['label'] ?? '')) ?: (string)$im['title']; $im['when'] = trim((string)($lb['when'] ?? '')); }
  return $out;
}

function dtm_cut_path(array $script, array $im): string {
  $f = basename((string)$im['file']);
  if ($im['kind'] === 'own') return dtm_uploads_dir() . '/cutaways/own/' . dtm_safe_id((string)$script['id']) . "/$f";
  return dtm_uploads_dir() . '/cutaways/lib/' . dtm_seed_key($script) . "/$f";
}

// Download a shipped picture from Wikimedia into the cache. Only Wikimedia hosts, only real images.
function dtm_cut_fetch(string $url, string $dest): bool {
  if (!preg_match('#^https://(upload|thumb)\.wikimedia\.org/#', $url)) return false;
  $dir = dirname($dest);
  if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return false;
  $lock = @fopen("$dest.lock", 'c');
  if ($lock) flock($lock, LOCK_EX);
  try {
    if (is_file($dest)) return true; // another request got it first
    $ua = 'DefacingTheMusic/1.0 (https://defacingthemusic.com/; cutaway image cache)';
    $data = false;
    if (function_exists('curl_init')) {
      $ch = curl_init($url);
      curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 45, CURLOPT_USERAGENT => $ua]);
      $data = curl_exec($ch);
      $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
      $final = (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
      curl_close($ch);
      if ($code !== 200 || !preg_match('#^https://[a-z]+\.wikimedia\.org/#', $final)) $data = false;
    } elseif (ini_get('allow_url_fopen')) {
      $ctx = stream_context_create(['http' => ['timeout' => 45, 'user_agent' => $ua, 'follow_location' => 1, 'max_redirects' => 3]]);
      $data = @file_get_contents($url, false, $ctx);
    }
    if (!is_string($data) || $data === '' || strlen($data) > DTM_CUT_MAX_BYTES || !@getimagesizefromstring($data)) return false;
    $tmp = "$dest.part";
    if (@file_put_contents($tmp, $data) === false) return false;
    return @rename($tmp, $dest);
  } finally {
    if ($lock) { flock($lock, LOCK_UN); fclose($lock); @unlink("$dest.lock"); }
  }
}

// ---------- sources ----------

// Duck's extra sources on a script: one per line, "Title | https://link" (or just a link).
function dtm_parse_sources(string $text): array {
  $out = [];
  foreach (preg_split('/\R/', $text) as $line) {
    $line = trim($line);
    if ($line === '') continue;
    $parts = array_map('trim', explode('|', $line));
    $url = ''; $title = [];
    foreach ($parts as $p) { if ($url === '' && preg_match('#^https?://\S+$#i', $p)) $url = $p; elseif ($p !== '') $title[] = $p; }
    $out[] = ['title' => $title ? implode(', ', $title) : $url, 'publisher' => '', 'url' => $url];
  }
  return $out;
}

function dtm_script_sources(array $script): array {
  $list = [];
  foreach ((dtm_lib_json('sources', dtm_seed_key($script))['sources'] ?? []) as $s) {
    if (is_array($s) && !empty($s['title'])) $list[] = $s + ['publisher' => '', 'url' => ''];
  }
  return array_merge($list, dtm_parse_sources((string)($script['sources'] ?? '')));
}

// ---------- public URLs ----------

function dtm_episode_page_path(string $number): string { return '/ep/' . rawurlencode($number); }
function dtm_episode_page_url(string $number): string { return 'https://defacingthemusic.com' . dtm_episode_page_path($number); }
function dtm_description_line(string $number): string { return 'Sources and credits: ' . dtm_episode_page_url($number); }

function dtm_episode_by_number(string $number): ?array {
  foreach (dtm_episodes() as $e) if ((string)$e['number'] === $number) return $e;
  return null;
}

// Safe link for the public page: http(s) only.
function dtm_safe_url(string $u): string { return preg_match('#^https?://#i', $u) ? $u : ''; }

// Admin: a read-only line with a one-click Copy button (used for the description line).
function dtm_copy_line(string $text, string $label = 'Copy'): string {
  return '<div class="copy-line"><input type="text" readonly value="' . h($text) . '" onfocus="this.select()" aria-label="Description line">'
    . '<button type="button" class="pull-btn bang" onclick="var i=this.previousElementSibling,b=this;i.select();'
    . '(navigator.clipboard?navigator.clipboard.writeText(i.value):Promise.reject()).catch(function(){document.execCommand(\'copy\')})'
    . '.then(function(){b.textContent=\'Copied\';setTimeout(function(){b.textContent=\'' . h($label) . '\'},1500)})">' . h($label) . '</button></div>';
}
