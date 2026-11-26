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
const DTM_AN_REFRESH_EVERY = 3600; // fetch at most hourly (on view, or from the hourly ping, admin/stats-tick.php)
const DTM_AN_META_EVERY = 3 * 3600; // Meta has tight rate limits: Facebook/Instagram at most every 3 hours unless Refresh is pressed
const DTM_AN_HOURLY_KEEP = 14 * 86400; // hourly history for the 48-hour chart

function dtm_an_cfg(): array {
  $s = dtm_secrets();
  return ['yt' => trim((string)($s['youtube_api_key'] ?? '')), 'meta' => trim((string)($s['meta_page_token'] ?? '')),
          'import' => trim((string)($s['analytics_import_key'] ?? '')), 'tick' => trim((string)($s['stats_tick_key'] ?? '')),
          'tt_key' => trim((string)($s['tiktok_client_key'] ?? '')), 'tt_secret' => trim((string)($s['tiktok_client_secret'] ?? '')),
          'tt_refresh' => trim((string)($s['tiktok_refresh_token'] ?? '')),
          'g_id' => trim((string)($s['google_client_id'] ?? '')), 'g_secret' => trim((string)($s['google_client_secret'] ?? '')),
          'g_refresh' => trim((string)($s['youtube_analytics_refresh_token'] ?? ''))];
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
function dtm_an_meta_insight(string $id, array $metrics, string $token, array $edges = ['insights']): ?int {
  // Meta renames metrics every so often, and some report 0 under an old name while the real number sits under a
  // new one. Try each edge and name; take the first number above zero, else 0 if one answered, else null.
  $best = null;
  foreach ($edges as $edge) foreach ($metrics as $m) {
    try {
      $r = dtm_an_meta($id . '/' . $edge, ['metric' => $m] + ($edge === 'insights' ? ['period' => 'lifetime'] : []), $token);
      foreach ($r['data'] ?? [] as $d) {
        $v = $d['values'][0]['value'] ?? ($d['total_value']['value'] ?? null);
        if (!is_numeric($v)) continue;
        if ((int)$v > 0) return (int)$v;
        $best = 0;
      }
    } catch (RuntimeException $e) { /* try the next one */ }
  }
  return $best;
}
// Facebook views, matching the "Views" number in Facebook's own dashboard (Nov 26: the site showed about 3,000 fewer).
// That number is the post-level "post_media_view" metric; older reel metrics count plays or 3-second views instead,
// so they're only a fallback.
function dtm_an_fb_views(string $pageId, string $postId, string $videoId, string $token): ?int {
  if ($postId !== '') {
    $pid = str_contains($postId, '_') ? $postId : $pageId . '_' . $postId;
    $v = dtm_an_meta_insight($pid, ['post_media_view'], $token);
    if ($v) return $v;
  }
  $v = dtm_an_meta_insight($pageId . '_' . $videoId, ['post_media_view'], $token);
  if ($v) return $v;
  return dtm_an_meta_insight($videoId, ['post_media_view', 'fb_reels_total_plays', 'blue_reels_play_count', 'post_video_views', 'total_video_views'], $token, ['insights', 'video_insights']);
}
// Facebook shares live on the post, not the video: ask the post (page_post id), then fall back to the reel's insights.
function dtm_an_fb_shares(string $pageId, string $postId, string $videoId, string $token): ?int {
  $ids = [];
  if ($postId !== '') $ids[] = str_contains($postId, '_') ? $postId : $pageId . '_' . $postId;
  $ids[] = $pageId . '_' . $videoId;
  foreach ($ids as $pid) {
    try { $r = dtm_an_meta($pid, ['fields' => 'shares'], $token); if (isset($r['shares']['count'])) return (int)$r['shares']['count']; if (array_key_exists('id', $r)) return 0; }
    catch (RuntimeException $e) { /* try the next */ }
  }
  try {
    $r = dtm_an_meta($videoId . '/video_insights', ['metric' => 'post_video_social_actions'], $token);
    foreach ($r['data'] ?? [] as $d) { $v = $d['values'][0]['value'] ?? null; if (is_array($v) && isset($v['SHARE'])) return (int)$v['SHARE']; if (is_array($v) && isset($v['share'])) return (int)$v['share']; }
  } catch (RuntimeException $e) {}
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
      $r = dtm_an_meta($pageId . '/' . $edge, ['fields' => 'id,title,description,created_time,permalink_url,post_id,length,likes.summary(true).limit(0),comments.summary(true).limit(0)', 'limit' => 50], $token);
      foreach ($r['data'] ?? [] as $v) {
        $id = (string)$v['id'];
        if (isset($seen[$id])) continue;
        $seen[$id] = true;
        $title = trim((string)($v['title'] ?? '')) ?: trim((string)($v['description'] ?? ''));
        $url = (string)($v['permalink_url'] ?? '');
        if ($url !== '' && $url[0] === '/') $url = 'https://www.facebook.com' . $url;
        $posts[] = ['platform' => 'facebook', 'id' => $id, 'title' => mb_substr($title, 0, 140), 'url' => $url,
          'published' => substr((string)($v['created_time'] ?? ''), 0, 10),
          'views' => dtm_an_fb_views($pageId, (string)($v['post_id'] ?? ''), $id, $token),
          'likes' => isset($v['likes']['summary']['total_count']) ? (int)$v['likes']['summary']['total_count'] : null,
          'comments' => isset($v['comments']['summary']['total_count']) ? (int)$v['comments']['summary']['total_count'] : null,
          'shares' => dtm_an_fb_shares($pageId, (string)($v['post_id'] ?? ''), $id, $token),
          'seconds' => isset($v['length']) ? (int)round((float)$v['length']) : null,
          'watch_minutes' => ($ms = dtm_an_meta_insight($id, ['post_video_view_time', 'fb_reels_total_view_time'], $token, ['video_insights', 'insights'])) !== null ? (int)round($ms / 60000) : null,
          'avg_seconds' => ($ms = dtm_an_meta_insight($id, ['post_video_avg_time_watched', 'fb_reels_avg_time_watched'], $token, ['video_insights', 'insights'])) !== null ? (int)round($ms / 1000) : null];
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
          'shares' => dtm_an_meta_insight($id, ['shares'], $token),
          'watch_minutes' => ($ms = dtm_an_meta_insight($id, ['ig_reels_video_view_total_time'], $token)) !== null ? (int)round($ms / 60000) : null,
          'avg_seconds' => ($ms = dtm_an_meta_insight($id, ['ig_reels_avg_watch_time'], $token)) !== null ? (int)round($ms / 1000) : null,
          'new_followers' => dtm_an_meta_insight($id, ['follows'], $token)];
      }
    } catch (RuntimeException $e) { $errors['instagram'] = $e->getMessage(); }
  }
  return [$posts, $followers];
}

// Fetch everything that has a key. Returns the errors (platform => message). Uses a lock so two page views
// never fetch at once.
function dtm_an_refresh(bool $force = false): array {
  $cfg = dtm_an_cfg();
  if ($cfg['yt'] === '' && $cfg['meta'] === '' && $cfg['tt_refresh'] === '') return [];
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
  if ($cfg['g_refresh'] !== '') {
    try { dtm_an_store($today, dtm_an_fetch_yta(), []); }
    catch (RuntimeException $e) { $errors['youtube'] = trim(($errors['youtube'] ?? '') . ' YouTube Analytics: ' . $e->getMessage()); }
  }
  $metaDue = $force || time() - (int)($st['meta_at'] ?? 0) >= DTM_AN_META_EVERY;
  if ($cfg['meta'] !== '' && $metaDue) {
    try { [$p, $f] = dtm_an_fetch_meta($cfg['meta'], $errors); dtm_an_store($today, $p, $f); }
    catch (RuntimeException $e) { $errors['facebook'] = $errors['instagram'] = $e->getMessage(); }
  } elseif ($cfg['meta'] !== '') {
    foreach (['facebook', 'instagram'] as $pl) if (isset($st['errors'][$pl])) $errors[$pl] = $st['errors'][$pl];
  }
  if ($cfg['tt_refresh'] !== '') {
    try { [$p, $f] = dtm_an_fetch_tiktok(); dtm_an_store($today, $p, $f); }
    catch (RuntimeException $e) { $errors['tiktok'] = $e->getMessage(); }
  }
  if ($metaDue) { try { dtm_an_fetch_history(); } catch (Throwable $e) { /* the daily chart falls back to snapshots */ } }
  dtm_json_update('analytics-state.json', fn($s) => ['fetched' => time(), 'errors' => $errors] + ($metaDue ? ['meta_at' => time()] : []) + $s);
  dtm_an_record_hour();
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

// ---------- TikTok (Login Kit + Display API: user.info.basic, user.info.stats, video.list) ----------
// Duck makes an app at developers.tiktok.com, pastes its Client key and Client secret on the Analytics page, then
// clicks Connect TikTok (admin/analytics-tiktok.php) and logs in once. The refresh token (good for a year, renewed on
// every use) lives in ../dtm-secrets.php; the day-long access token is fetched fresh on each refresh.
const DTM_TT_SCOPES = 'user.info.basic,user.info.stats,video.list';
function dtm_an_tt_redirect(): string { return 'https://defacingthemusic.com/admin/analytics-tiktok.php'; }

// POST to TikTok. $form: form-encoded body, or $json: JSON body. Errors never include tokens.
function dtm_an_post(string $url, array $form = [], ?array $json = null, array $headers = []): array {
  $body = $json !== null ? json_encode($json) : http_build_query($form);
  $headers[] = $json !== null ? 'Content-Type: application/json' : 'Content-Type: application/x-www-form-urlencoded';
  $ch = curl_init($url);
  curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
    CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_HTTPHEADER => $headers, CURLOPT_USERAGENT => 'DefacingTheMusic/1.0 (analytics)']);
  $res = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE); $cerr = curl_error($ch); curl_close($ch);
  if ($res === false) throw new RuntimeException('Could not reach TikTok (' . ($cerr ?: 'network error') . ').');
  $j = json_decode((string)$res, true);
  if (!is_array($j)) throw new RuntimeException("TikTok sent back something unreadable (HTTP $code).");
  $e = $j['error'] ?? null;
  if (is_array($e) && ($e['code'] ?? 'ok') !== 'ok') throw new RuntimeException('TikTok: ' . ($e['message'] ?? $e['code']));
  if (is_string($e) && $e !== '') throw new RuntimeException('TikTok: ' . ($j['error_description'] ?? $e));
  if ($code >= 400) throw new RuntimeException("TikTok said no (HTTP $code).");
  return $j;
}
function dtm_an_tt_get(string $url, string $access): array {
  $ch = curl_init($url);
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $access]]);
  $res = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch);
  $j = json_decode((string)$res, true);
  if (!is_array($j)) throw new RuntimeException("TikTok sent back something unreadable (HTTP $code).");
  if (($j['error']['code'] ?? 'ok') !== 'ok') throw new RuntimeException('TikTok: ' . ($j['error']['message'] ?? $j['error']['code']));
  return $j;
}
// Trade a login code (or the saved refresh token) for tokens; saves the new refresh token. Returns the access token.
function dtm_an_tt_tokens(array $grant): string {
  $cfg = dtm_an_cfg();
  if ($cfg['tt_key'] === '' || $cfg['tt_secret'] === '') throw new RuntimeException('Paste the TikTok Client key and Client secret first.');
  $j = dtm_an_post('https://open.tiktokapis.com/v2/oauth/token/', ['client_key' => $cfg['tt_key'], 'client_secret' => $cfg['tt_secret']] + $grant);
  if (empty($j['access_token'])) throw new RuntimeException('TikTok did not send a login token back.');
  if (!empty($j['refresh_token']) && $j['refresh_token'] !== $cfg['tt_refresh']) {
    $err = dtm_an_save_secrets(['tiktok_refresh_token' => (string)$j['refresh_token']]);
    if ($err !== '') throw new RuntimeException($err);
  }
  return (string)$j['access_token'];
}
function dtm_an_fetch_tiktok(): array {
  $cfg = dtm_an_cfg();
  $access = dtm_an_tt_tokens(['grant_type' => 'refresh_token', 'refresh_token' => $cfg['tt_refresh']]);
  $u = dtm_an_tt_get('https://open.tiktokapis.com/v2/user/info/?fields=follower_count,display_name', $access);
  $followers = ['tiktok' => isset($u['data']['user']['follower_count']) ? (int)$u['data']['user']['follower_count'] : null];
  $posts = []; $cursor = null;
  for ($i = 0; $i < 10; $i++) {
    $j = dtm_an_post('https://open.tiktokapis.com/v2/video/list/?fields=id,title,video_description,create_time,share_url,duration,view_count,like_count,comment_count,share_count',
      [], ['max_count' => 20] + ($cursor !== null ? ['cursor' => $cursor] : []), ['Authorization: Bearer ' . $access]);
    foreach ($j['data']['videos'] ?? [] as $v) {
      $title = trim((string)($v['title'] ?? '')) ?: trim((string)($v['video_description'] ?? ''));
      $posts[] = ['platform' => 'tiktok', 'id' => (string)$v['id'], 'title' => mb_substr($title, 0, 140), 'url' => (string)($v['share_url'] ?? ''),
        'published' => !empty($v['create_time']) ? date('Y-m-d', (int)$v['create_time']) : null, 'seconds' => isset($v['duration']) ? (int)$v['duration'] : null,
        'views' => isset($v['view_count']) ? (int)$v['view_count'] : null, 'likes' => isset($v['like_count']) ? (int)$v['like_count'] : null,
        'comments' => isset($v['comment_count']) ? (int)$v['comment_count'] : null, 'shares' => isset($v['share_count']) ? (int)$v['share_count'] : null];
    }
    if (empty($j['data']['has_more'])) break;
    $cursor = $j['data']['cursor'] ?? null;
    if ($cursor === null) break;
  }
  return [$posts, $followers];
}

// ---------- YouTube Analytics (Google sign-in): shares and watch time per video ----------
// Uses the shared Google OAuth client (google_client_id, plus google_client_secret pasted on the Stats page). Duck
// clicks Connect YouTube Analytics (admin/analytics-google.php) once; the refresh token lives in ../dtm-secrets.php.
const DTM_YTA_SCOPE = 'https://www.googleapis.com/auth/yt-analytics.readonly';
function dtm_an_g_redirect(): string { return 'https://defacingthemusic.com/admin/analytics-google.php'; }
function dtm_an_g_tokens(array $grant): string {
  $cfg = dtm_an_cfg();
  if ($cfg['g_id'] === '' || $cfg['g_secret'] === '') throw new RuntimeException('Paste the Google client secret first.');
  $j = dtm_an_post('https://oauth2.googleapis.com/token', ['client_id' => $cfg['g_id'], 'client_secret' => $cfg['g_secret']] + $grant);
  if (empty($j['access_token'])) throw new RuntimeException('Google did not send a login token back.');
  if (!empty($j['refresh_token']) && $j['refresh_token'] !== $cfg['g_refresh']) {
    $err = dtm_an_save_secrets(['youtube_analytics_refresh_token' => (string)$j['refresh_token']]);
    if ($err !== '') throw new RuntimeException($err);
  }
  return (string)$j['access_token'];
}
function dtm_an_fetch_yta(): array {
  $cfg = dtm_an_cfg();
  $access = dtm_an_g_tokens(['grant_type' => 'refresh_token', 'refresh_token' => $cfg['g_refresh']]);
  $j = dtm_an_tt_get('https://youtubeanalytics.googleapis.com/v2/reports?' . http_build_query([
    'ids' => 'channel==MINE', 'startDate' => '2026-01-01', 'endDate' => date('Y-m-d'),
    'metrics' => 'views,shares,estimatedMinutesWatched,averageViewDuration,subscribersGained', 'dimensions' => 'video', 'sort' => '-views', 'maxResults' => 200]), $access);
  $posts = [];
  foreach ($j['rows'] ?? [] as $r) {
    $posts[] = ['platform' => 'youtube', 'id' => (string)$r[0], 'shares' => (int)$r[2], 'watch_minutes' => (int)$r[3], 'avg_seconds' => (int)$r[4], 'new_followers' => (int)$r[5]];
  }
  return $posts;
}

// ---------- hourly history (the 48-hour chart) ----------
// Every fetch adds one point: each post's lifetime views at that moment. Kept for 14 days.
function dtm_an_record_hour(): void {
  $v = [];
  foreach (dtm_an_latest()['posts'] as $k => $p) if (isset($p['views']) && $p['views'] !== null) $v[$k] = (int)$p['views'];
  if (!$v) return;
  $now = time();
  dtm_json_update('analytics-hourly.json', function ($h) use ($v, $now) {
    $h = array_values(array_filter($h, fn($x) => ($x['t'] ?? 0) > $now - DTM_AN_HOURLY_KEEP));
    $h[] = ['t' => $now, 'v' => $v];
    return $h;
  });
}
// Views gained per hour for the last $hours hours, for the posts $keep() accepts. Returns [hour start ts => views].
// A gap between two fetches is spread evenly over the hours it covers.
function dtm_an_hourly(int $hours, callable $keep): array {
  $end = (int)(floor(time() / 3600) * 3600) + 3600; $start = $end - $hours * 3600;
  $b = []; for ($t = $start; $t < $end; $t += 3600) $b[$t] = 0;
  $h = dtm_json_read('analytics-hourly.json');
  usort($h, fn($a, $c) => $a['t'] <=> $c['t']);
  for ($i = 1; $i < count($h); $i++) {
    $a = $h[$i - 1]; $c = $h[$i];
    if ($c['t'] <= $start) continue;
    $g = 0;
    foreach ($c['v'] as $k => $n) if (isset($a['v'][$k]) && $keep($k)) $g += max(0, $n - $a['v'][$k]);
    if (!$g) continue;
    $from = max($a['t'], $start); $to = $c['t']; $span = max(1, $c['t'] - $a['t']);
    for ($t = (int)(floor($from / 3600) * 3600); $t < $to; $t += 3600) {
      if (!isset($b[$t])) continue;
      $part = (min($to, $t + 3600) - max($a['t'], $t)) / $span;
      if ($part > 0) $b[$t] += $g * $part;
    }
  }
  // Hours after the last check aren't zero, they're unknown: leave them off the end.
  // Same for the hours before the first check (Nov 26: the line sat on zero for the day and a half before tracking began).
  $last = $h ? (int)end($h)['t'] : 0;
  $first = $h ? (int)(floor((int)$h[0]['t'] / 3600) * 3600) : PHP_INT_MAX;
  // Keep all 48 hours on the axis; unknown hours are null (drawn as a gap, not as zero).
  foreach ($b as $t => $x) $b[$t] = ($t > $last || $t < $first) ? null : (int)round($x);
  return $b;
}

// ---------- daily history straight from the platforms (Nov 26, Duck: "why arent u tracking views right? youtube and
// the other platforms show the type of chart i want") ----------
// YouTube Analytics (views by day), the Facebook Page (page_media_view by day) and Instagram (views per day) keep their
// own day-by-day history back to the start, so the daily charts use that instead of the site's own snapshots.
// Stored in data/analytics-daily.json as platform => date => views. TikTok has no daily history in its API, so its
// days come from the site's snapshots. Hour-by-hour isn't offered by any of them; that line is the site's own checks.
function dtm_an_meta_page(string $token): array {
  try {
    $acc = dtm_an_meta('me/accounts', ['fields' => 'name,access_token', 'limit' => 100], $token);
    foreach ($acc['data'] ?? [] as $p) if (stripos((string)($p['name'] ?? ''), 'defacing') !== false && !empty($p['access_token'])) { $token = (string)$p['access_token']; break; }
  } catch (RuntimeException $e) {}
  $me = dtm_an_meta('me', ['fields' => 'id,name,instagram_business_account{id}'], $token);
  return [$token, (string)($me['id'] ?? ''), (string)($me['instagram_business_account']['id'] ?? '')];
}
function dtm_an_daily(): array { return dtm_json_read('analytics-daily.json'); }
function dtm_an_fetch_history(): void {
  $cfg = dtm_an_cfg(); $got = [];
  $since = date('Y-m-d', strtotime('-88 days')); $today = date('Y-m-d');
  if ($cfg['g_refresh'] !== '') {
    try {
      $access = dtm_an_g_tokens(['grant_type' => 'refresh_token', 'refresh_token' => $cfg['g_refresh']]);
      $j = dtm_an_tt_get('https://youtubeanalytics.googleapis.com/v2/reports?' . http_build_query(['ids' => 'channel==MINE', 'startDate' => '2026-01-01', 'endDate' => $today, 'metrics' => 'views', 'dimensions' => 'day', 'sort' => 'day']), $access);
      foreach ($j['rows'] ?? [] as $r) $got['youtube'][(string)$r[0]] = (int)$r[1];
    } catch (RuntimeException $e) {}
  }
  if ($cfg['meta'] !== '') {
    try {
      [$tok, $pageId, $igId] = dtm_an_meta_page($cfg['meta']);
      foreach (['page_media_view', 'page_video_views'] as $m) {
        try {
          $r = dtm_an_meta($pageId . '/insights', ['metric' => $m, 'period' => 'day', 'since' => $since, 'until' => date('Y-m-d', strtotime('+1 day'))], $tok);
          $rows = [];
          foreach ($r['data'][0]['values'] ?? [] as $v) if (is_numeric($v['value'] ?? null)) $rows[date('Y-m-d', strtotime((string)$v['end_time']) - 86400)] = (int)$v['value'];
          if (array_sum($rows) > 0) { $got['facebook'] = $rows; break; }
        } catch (RuntimeException $e) {}
      }
      if ($igId !== '') {
        // Instagram gives views per day only one day at a time; fetch the days we don't have yet (plus the last 2).
        $have = dtm_an_daily()['instagram'] ?? [];
        for ($d = strtotime('-29 days'); $d <= time(); $d += 86400) {
          $day = date('Y-m-d', $d);
          if (isset($have[$day]) && $day < date('Y-m-d', strtotime('-2 days'))) continue;
          try {
            $r = dtm_an_meta($igId . '/insights', ['metric' => 'views', 'period' => 'day', 'metric_type' => 'total_value', 'since' => $d, 'until' => $d + 86400], $tok);
            $v = $r['data'][0]['total_value']['value'] ?? null;
            if (is_numeric($v)) $got['instagram'][$day] = (int)$v;
          } catch (RuntimeException $e) { break; }
        }
      }
    } catch (RuntimeException $e) {}
  }
  if ($got) dtm_json_update('analytics-daily.json', function ($d) use ($got) { foreach ($got as $pl => $rows) $d[$pl] = $rows + ($d[$pl] ?? []); foreach ($d as &$r) ksort($r); return $d; });
}
