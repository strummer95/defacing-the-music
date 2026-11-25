<?php
// admin > Analytics (DTM-ANALYTICS-1): views, likes, comments, shares and followers from YouTube, Facebook,
// Instagram and TikTok in one place.
// - YouTube: Data API v3 with an API key (public numbers: views, likes, comments, subscribers).
// - Facebook + Instagram: Meta Graph API with a long-lived Page access token Duck pastes in the admin.
// - TikTok (and any platform as a fallback): CSV/JSON import, from the admin form or a key-protected POST
//   to admin/analytics-import.php (Defa posts TikTok numbers every 1 to 2 days).
// Keys live only in ../dtm-secrets.php: youtube_api_key, meta_page_token, analytics_import_key. Never printed.
// Storage: one snapshot per day in data/analytics/YYYY-MM-DD.json (numbers are lifetime totals as of that day,
// so trends are the difference between days), plus data/analytics-links.json (which posts are the same video)
// and data/analytics-state.json (last fetch time and the last error per platform).
require_once __DIR__ . '/auth.php';

const DTM_YT_CHANNEL = 'UCRXnCbaJExnAwNbeb1d5mZg';
const DTM_META_API = 'https://graph.facebook.com/v27.0/';
const DTM_AN_PLATFORMS = ['youtube' => 'YouTube', 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok'];
const DTM_AN_METRICS = ['views' => 'Views', 'likes' => 'Likes', 'comments' => 'Comments', 'shares' => 'Shares'];
const DTM_AN_REFRESH_EVERY = 3 * 3600; // fetch-on-view at most every 3 hours (no cron on shared hosting)

function dtm_an_cfg(): array {
  $s = dtm_secrets();
  return ['yt' => trim((string)($s['youtube_api_key'] ?? '')), 'meta' => trim((string)($s['meta_page_token'] ?? '')),
          'import' => trim((string)($s['analytics_import_key'] ?? ''))];
}

// Same safe write as the Guest List settings: temp file next to the secrets file, check it loads and kept every
// old setting, then swap it in. $set: key => value ('' removes the key). Returns '' or an error message.
function dtm_an_save_secrets(array $set): string {
  $f = dirname(__DIR__, 2) . '/dtm-secrets.php';
  if (!is_file($f)) return 'The secrets file (dtm-secrets.php, one folder above the site) is missing.';
  $old = require $f;
  if (!is_array($old)) return 'The secrets file looks broken, so I did not touch it.';
  $new = $old;
  foreach ($set as $k => $v) { if ($v === '') unset($new[$k]); else $new[$k] = $v; }
  $php = "<?php\n// Server secrets for Defacing the Music. Never put this file in git.\nreturn " . var_export($new, true) . ";\n";
  $tmp = dirname($f) . '/.dtm-secrets.tmp-' . bin2hex(random_bytes(4)) . '.php';
  if (@file_put_contents($tmp, $php) !== strlen($php)) { @unlink($tmp); return "The site can't write next to the secrets file, so nothing was saved."; }
  $check = (function ($p) { return require $p; })($tmp);
  $kept = array_diff_key($old, array_filter($set, fn($v) => $v === ''));
  if (!is_array($check) || array_diff_key($kept, $check)) { @unlink($tmp); return 'Something went wrong building the new secrets file, so nothing was changed.'; }
  @chmod($tmp, 0600);
  if (!@rename($tmp, $f)) { @unlink($tmp); return "The site can't replace the secrets file, so nothing was saved."; }
  if (function_exists('opcache_invalidate')) @opcache_invalidate($f, true);
  return '';
}

// ---------- storage ----------
function dtm_an_dir(): string {
  $d = dtm_data_dir() . '/analytics';
  if (!is_dir($d)) @mkdir($d, 0755, true);
  return $d;
}
function dtm_an_day_name(string $date): string { return 'analytics/' . $date . '.json'; }
function dtm_an_days(): array { // every snapshot date, oldest first
  $out = [];
  foreach (glob(dtm_an_dir() . '/*.json') ?: [] as $f) if (preg_match('/(\d{4}-\d{2}-\d{2})\.json$/', $f, $m)) $out[] = $m[1];
  sort($out);
  return $out;
}
function dtm_an_day(string $date): array {
  $d = dtm_json_read(dtm_an_day_name($date));
  return ['posts' => $d['posts'] ?? [], 'followers' => $d['followers'] ?? []];
}
// Merge posts/followers into one day's snapshot (latest numbers for that day win; nothing else is touched).
function dtm_an_store(string $date, array $posts, array $followers): void {
  dtm_an_dir();
  dtm_json_update(dtm_an_day_name($date), function ($d) use ($posts, $followers) {
    $d['posts'] = $d['posts'] ?? []; $d['followers'] = $d['followers'] ?? [];
    foreach ($posts as $p) {
      $k = $p['platform'] . ':' . $p['id'];
      $d['posts'][$k] = array_merge($d['posts'][$k] ?? [], array_filter($p, fn($v) => $v !== null));
    }
    foreach ($followers as $pl => $n) if ($n !== null) $d['followers'][$pl] = (int)$n;
    return $d;
  });
}
// The newest known numbers for every post and platform (a post missing from today keeps its last numbers).
function dtm_an_latest(): array {
  $posts = []; $followers = [];
  foreach (dtm_an_days() as $day) {
    $d = dtm_an_day($day);
    foreach ($d['posts'] as $k => $p) $posts[$k] = array_merge($posts[$k] ?? [], $p, ['_day' => $day]);
    foreach ($d['followers'] as $pl => $n) $followers[$pl] = $n;
  }
  return ['posts' => $posts, 'followers' => $followers];
}
function dtm_an_state(): array { return dtm_json_read('analytics-state.json'); }

// ---------- HTTP ----------
// GET a JSON API. Errors never include the URL (it carries the key or token).
function dtm_an_get(string $url): array {
  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 8,
      CURLOPT_USERAGENT => 'DefacingTheMusic/1.0 (analytics)', CURLOPT_HTTPHEADER => ['Accept: application/json']]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $cerr = curl_error($ch);
    curl_close($ch);
    if ($body === false) throw new RuntimeException('Could not reach the API (' . ($cerr !== '' ? $cerr : 'network error') . ').');
  } else {
    $ctx = stream_context_create(['http' => ['timeout' => 20, 'ignore_errors' => true, 'header' => "Accept: application/json\r\nUser-Agent: DefacingTheMusic/1.0 (analytics)\r\n"]]);
    $body = @file_get_contents($url, false, $ctx);
    $code = 0;
    foreach ($http_response_header ?? [] as $hl) if (preg_match('#^HTTP/\S+\s+(\d{3})#', $hl, $m)) $code = (int)$m[1];
    if ($body === false) throw new RuntimeException('Could not reach the API.');
  }
  $j = json_decode((string)$body, true);
  if (!is_array($j)) throw new RuntimeException("The API sent back something unreadable (HTTP $code).");
  if ($code >= 400 || isset($j['error'])) {
    $e = $j['error'] ?? [];
    $m = is_array($e) ? (string)($e['message'] ?? ($e['errors'][0]['message'] ?? '')) : (string)$e;
    throw new RuntimeException(trim("HTTP $code. " . preg_replace('/(key|access_token)=[^&\s]+/i', '$1=…', $m)));
  }
  return $j;
}
function dtm_an_iso_seconds(string $d): int { // PT1H2M3S -> seconds
  if (!preg_match('/^P(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/', $d, $m)) return 0;
  return ((int)($m[1] ?? 0)) * 86400 + ((int)($m[2] ?? 0)) * 3600 + ((int)($m[3] ?? 0)) * 60 + (int)($m[4] ?? 0);
}

// ---------- YouTube (API key) ----------
function dtm_an_fetch_youtube(string $key): array {
  $q = fn(string $path, array $p) => 'https://www.googleapis.com/youtube/v3/' . $path . '?' . http_build_query($p + ['key' => $key]);
  $ch = dtm_an_get($q('channels', ['part' => 'statistics,contentDetails', 'id' => DTM_YT_CHANNEL]));
  $c = $ch['items'][0] ?? null;
  if (!$c) throw new RuntimeException('YouTube did not find the channel.');
  $subs = isset($c['statistics']['subscriberCount']) ? (int)$c['statistics']['subscriberCount'] : null;
  $uploads = (string)($c['contentDetails']['relatedPlaylists']['uploads'] ?? '');
  $ids = []; $page = '';
  for ($i = 0; $i < 6 && $uploads !== ''; $i++) {
    $pl = dtm_an_get($q('playlistItems', ['part' => 'contentDetails', 'playlistId' => $uploads, 'maxResults' => 50] + ($page !== '' ? ['pageToken' => $page] : [])));
    foreach ($pl['items'] ?? [] as $it) if (!empty($it['contentDetails']['videoId'])) $ids[] = $it['contentDetails']['videoId'];
    $page = (string)($pl['nextPageToken'] ?? '');
    if ($page === '') break;
  }
  $posts = [];
  foreach (array_chunk(array_unique($ids), 50) as $chunk) {
    $v = dtm_an_get($q('videos', ['part' => 'snippet,statistics,contentDetails', 'id' => implode(',', $chunk)]));
    foreach ($v['items'] ?? [] as $it) {
      $sec = dtm_an_iso_seconds((string)($it['contentDetails']['duration'] ?? ''));
      $st = $it['statistics'] ?? [];
      $posts[] = ['platform' => 'youtube', 'id' => (string)$it['id'], 'title' => (string)($it['snippet']['title'] ?? ''),
        'url' => ($sec > 0 && $sec <= 180 ? 'https://youtube.com/shorts/' : 'https://youtu.be/') . $it['id'],
        'published' => substr((string)($it['snippet']['publishedAt'] ?? ''), 0, 10), 'seconds' => $sec,
        'views' => isset($st['viewCount']) ? (int)$st['viewCount'] : null, 'likes' => isset($st['likeCount']) ? (int)$st['likeCount'] : null,
        'comments' => isset($st['commentCount']) ? (int)$st['commentCount'] : null, 'shares' => null];
    }
  }
  return [$posts, ['youtube' => $subs]];
}

// ---------- Facebook + Instagram (Meta Page token) ----------
function dtm_an_meta(string $path, array $p, string $token): array {
  return dtm_an_get(DTM_META_API . $path . '?' . http_build_query($p + ['access_token' => $token]));
}
// One lifetime insight number, trying metric names in order (Meta renames them every so often).
function dtm_an_meta_insight(string $id, array $metrics, string $token): ?int {
  foreach ($metrics as $m) {
    try {
      $r = dtm_an_meta($id . '/insights', ['metric' => $m, 'period' => 'lifetime'], $token);
      foreach ($r['data'] ?? [] as $d) { $v = $d['values'][0]['value'] ?? ($d['total_value']['value'] ?? null); if (is_numeric($v)) return (int)$v; }
    } catch (RuntimeException $e) { /* try the next name */ }
  }
  return null;
}
function dtm_an_fetch_meta(string $token, array &$errors): array {
  // A personal token saved by mistake: use the Defacing The Music Page's token from it.
  try {
    $acc = dtm_an_meta('me/accounts', ['fields' => 'name,access_token', 'limit' => 100], $token);
    foreach ($acc['data'] ?? [] as $p) if (stripos((string)($p['name'] ?? ''), 'defacing') !== false && !empty($p['access_token'])) { $token = (string)$p['access_token']; break; }
  } catch (RuntimeException $e) { /* already a Page token */ }
  $me = dtm_an_meta('me', ['fields' => 'id,name,followers_count,fan_count,instagram_business_account{id,username,followers_count}'], $token);
  $pageId = (string)($me['id'] ?? '');
  if ($pageId === '') throw new RuntimeException("That token isn't a Page token (Meta didn't say which Page it belongs to).");
  $followers = ['facebook' => isset($me['followers_count']) ? (int)$me['followers_count'] : (isset($me['fan_count']) ? (int)$me['fan_count'] : null)];
  $posts = [];
  // Facebook videos and reels.
  try {
    $seen = [];
    foreach (['videos', 'video_reels'] as $edge) {
      $r = dtm_an_meta($pageId . '/' . $edge, ['fields' => 'id,title,description,created_time,permalink_url,likes.summary(true).limit(0),comments.summary(true).limit(0)', 'limit' => 50], $token);
      foreach ($r['data'] ?? [] as $v) {
        $id = (string)$v['id'];
        if (isset($seen[$id])) continue;
        $seen[$id] = true;
        $title = trim((string)($v['title'] ?? '')) ?: trim((string)($v['description'] ?? ''));
        $url = (string)($v['permalink_url'] ?? '');
        if ($url !== '' && $url[0] === '/') $url = 'https://www.facebook.com' . $url;
        $posts[] = ['platform' => 'facebook', 'id' => $id, 'title' => mb_substr($title, 0, 140), 'url' => $url,
          'published' => substr((string)($v['created_time'] ?? ''), 0, 10),
          'views' => dtm_an_meta_insight($id, ['blue_reels_play_count', 'fb_reels_total_plays', 'total_video_views'], $token),
          'likes' => isset($v['likes']['summary']['total_count']) ? (int)$v['likes']['summary']['total_count'] : null,
          'comments' => isset($v['comments']['summary']['total_count']) ? (int)$v['comments']['summary']['total_count'] : null, 'shares' => null];
      }
    }
  } catch (RuntimeException $e) { $errors['facebook'] = $e->getMessage(); }
  // Instagram, through the Page's linked Instagram account.
  $ig = $me['instagram_business_account'] ?? null;
  if (!$ig) $errors['instagram'] = "This Page token can't see an Instagram account. Link @defacingthemusicpod to the Facebook Page (Instagram > Settings > Account type and tools, or the Page's Linked accounts), then make a new token.";
  else {
    $followers['instagram'] = isset($ig['followers_count']) ? (int)$ig['followers_count'] : null;
    try {
      $r = dtm_an_meta($ig['id'] . '/media', ['fields' => 'id,caption,media_type,media_product_type,permalink,timestamp,like_count,comments_count', 'limit' => 50], $token);
      foreach ($r['data'] ?? [] as $m) {
        $id = (string)$m['id'];
        $posts[] = ['platform' => 'instagram', 'id' => $id, 'title' => mb_substr(trim((string)($m['caption'] ?? '')), 0, 140), 'url' => (string)($m['permalink'] ?? ''),
          'published' => substr((string)($m['timestamp'] ?? ''), 0, 10),
          'views' => dtm_an_meta_insight($id, ['views', 'plays', 'impressions'], $token),
          'likes' => isset($m['like_count']) ? (int)$m['like_count'] : null, 'comments' => isset($m['comments_count']) ? (int)$m['comments_count'] : null,
          'shares' => dtm_an_meta_insight($id, ['shares'], $token)];
      }
    } catch (RuntimeException $e) { $errors['instagram'] = $e->getMessage(); }
  }
  return [$posts, $followers];
}

// Fetch everything that has a key. Returns the errors (platform => message). Uses a lock so two page views
// never fetch at once.
function dtm_an_refresh(bool $force = false): array {
  $cfg = dtm_an_cfg();
  if ($cfg['yt'] === '' && $cfg['meta'] === '') return [];
  $st = dtm_an_state();
  if (!$force && time() - (int)($st['fetched'] ?? 0) < DTM_AN_REFRESH_EVERY) return $st['errors'] ?? [];
  $lock = @fopen(dtm_data_dir() . '/analytics-fetch.lock', 'c');
  if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) return $st['errors'] ?? [];
  @set_time_limit(120);
  $errors = []; $today = date('Y-m-d');
  if ($cfg['yt'] !== '') {
    try { [$p, $f] = dtm_an_fetch_youtube($cfg['yt']); dtm_an_store($today, $p, $f); }
    catch (RuntimeException $e) { $errors['youtube'] = $e->getMessage(); }
  }
  if ($cfg['meta'] !== '') {
    try { [$p, $f] = dtm_an_fetch_meta($cfg['meta'], $errors); dtm_an_store($today, $p, $f); }
    catch (RuntimeException $e) { $errors['facebook'] = $errors['instagram'] = $e->getMessage(); }
  }
  dtm_json_update('analytics-state.json', fn($s) => ['fetched' => time(), 'errors' => $errors] + $s);
  flock($lock, LOCK_UN); fclose($lock);
  return $errors;
}

// ---------- import (TikTok, or any platform as a fallback) ----------
// Accepts CSV (header row) or JSON (a list of rows, or {"rows": [...]}). Columns: date, platform, post (id or url),
// caption, views, likes, comments, shares, followers. A row with followers and no post just sets followers.
// Returns [rows saved, errors].
function dtm_an_import(string $raw, string $defaultPlatform = 'tiktok'): array {
  $raw = trim($raw);
  if ($raw === '') return [0, ['Nothing to import.']];
  $rows = [];
  if ($raw[0] === '[' || $raw[0] === '{') {
    $j = json_decode($raw, true);
    if (!is_array($j)) return [0, ["That JSON doesn't parse."]];
    $rows = isset($j['rows']) && is_array($j['rows']) ? $j['rows'] : (array_is_list($j) ? $j : [$j]);
  } else {
    $lines = preg_split('/\r\n|\r|\n/', $raw);
    $head = array_map(fn($h) => strtolower(trim($h)), str_getcsv((string)array_shift($lines)));
    foreach ($lines as $l) if (trim($l) !== '') { $c = str_getcsv($l); $rows[] = array_combine($head, array_pad(array_slice($c, 0, count($head)), count($head), '')); }
  }
  $alias = ['post' => ['post', 'post_id', 'id', 'url', 'post_url', 'link'], 'caption' => ['caption', 'title', 'description'],
            'views' => ['views', 'plays', 'video_views'], 'shares' => ['shares', 'share_count']];
  $byDay = []; $errors = []; $n = 0;
  foreach ($rows as $i => $r) {
    if (!is_array($r)) { $errors[] = 'Row ' . ($i + 1) . ' is not a row.'; continue; }
    $r = array_change_key_case($r, CASE_LOWER);
    $get = function (string $k) use ($r, $alias) { foreach ($alias[$k] ?? [$k] as $a) if (isset($r[$a]) && $r[$a] !== '') return $r[$a]; return null; };
    $date = (string)($get('date') ?? date('Y-m-d'));
    $ts = strtotime($date);
    if ($ts === false) { $errors[] = 'Row ' . ($i + 1) . ': bad date.'; continue; }
    $date = date('Y-m-d', $ts);
    $pl = strtolower(trim((string)($get('platform') ?? $defaultPlatform)));
    if (!isset(DTM_AN_PLATFORMS[$pl])) { $errors[] = 'Row ' . ($i + 1) . ": unknown platform \"$pl\"."; continue; }
    $num = function (string $k) use ($get) { $v = $get($k); if ($v === null) return null; $v = str_replace([',', ' '], '', (string)$v);
      if (preg_match('/^([\d.]+)\s*([km])$/i', $v, $m)) return (int)round((float)$m[1] * (strtolower($m[2]) === 'k' ? 1e3 : 1e6)); return is_numeric($v) ? (int)$v : null; };
    $byDay[$date] = $byDay[$date] ?? ['posts' => [], 'followers' => []];
    if (($f = $num('followers')) !== null) $byDay[$date]['followers'][$pl] = $f;
    $post = trim((string)($get('post') ?? ''));
    if ($post !== '') {
      $id = preg_match('#/video/(\d+)#', $post, $m) ? $m[1] : (preg_match('#^https?://#', $post) ? substr(sha1($post), 0, 16) : mb_substr($post, 0, 80));
      $byDay[$date]['posts'][] = ['platform' => $pl, 'id' => $id, 'url' => preg_match('#^https?://#', $post) ? $post : null,
        'title' => ($c = $get('caption')) !== null ? mb_substr(trim((string)$c), 0, 140) : null,
        'published' => ($p = $get('published')) !== null && strtotime((string)$p) ? date('Y-m-d', strtotime((string)$p)) : null,
        'views' => $num('views'), 'likes' => $num('likes'), 'comments' => $num('comments'), 'shares' => $num('shares')];
    }
    $n++;
  }
  foreach ($byDay as $date => $d) dtm_an_store($date, $d['posts'], $d['followers']);
  return [$n, $errors];
}

// ---------- grouping: the same clip on several platforms is one "video" ----------
function dtm_an_words(string $t): array {
  $t = strtolower(preg_replace('/#\w+|https?:\S+|[^a-z0-9 ]+/i', ' ', $t));
  $stop = array_flip(['the', 'a', 'an', 'and', 'of', 'to', 'in', 'on', 'is', 'it', 'for', 'this', 'that', 'with', 'you', 'your', 'defacing', 'music', 'ep', 'shorts', 'short']);
  return array_values(array_unique(array_filter(preg_split('/\s+/', $t), fn($w) => strlen($w) > 2 && !isset($stop[$w]))));
}
// Returns post key => group key. Manual links (data/analytics-links.json) win; otherwise a best guess: a non-YouTube
// post joins the YouTube video whose title shares the most words, posted within 4 days.
function dtm_an_groups(array $posts): array {
  $links = dtm_json_read('analytics-links.json');
  $g = [];
  $yt = array_filter($posts, fn($p) => ($p['platform'] ?? '') === 'youtube');
  foreach ($posts as $k => $p) {
    if (isset($links[$k]) && $links[$k] !== '') { $g[$k] = $links[$k] === 'self' ? $k : $links[$k]; continue; }
    if (($p['platform'] ?? '') === 'youtube') { $g[$k] = $k; continue; }
    $best = null; $bestScore = 0;
    $w = dtm_an_words((string)($p['title'] ?? ''));
    foreach ($yt as $yk => $y) {
      $pd = strtotime((string)($p['published'] ?? '')); $yd = strtotime((string)($y['published'] ?? ''));
      if ($pd && $yd && abs($pd - $yd) > 4 * 86400) continue;
      $yw = dtm_an_words((string)($y['title'] ?? ''));
      if (!$w || !$yw) continue;
      $score = count(array_intersect($w, $yw)) / min(count($w), count($yw));
      if ($score > $bestScore) { $bestScore = $score; $best = $yk; }
    }
    $g[$k] = ($best !== null && $bestScore >= 0.5) ? $best : $k;
  }
  foreach ($g as $k => $to) if (!isset($posts[$to])) $g[$k] = $k; // a link to a post we no longer have
  return $g;
}
