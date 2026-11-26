<?php
// admin > Analytics (DTM-ANALYTICS-1): every platform in one place, by platform and/or by video. Data: lib/analytics.php.
require __DIR__ . '/../lib/analytics.php';
$user = dtm_require_admin();
$err = ''; $msg = '';
$fmt = fn($n) => $n === null ? '–' : number_format((int)$n);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  dtm_check_csrf();
  $a = (string)($_POST['action'] ?? '');
  $back = 'analytics.php?' . http_build_query(array_intersect_key($_GET, array_flip(['platform', 'video', 'days'])));
  try {
    if ($a === 'refresh') { $e = dtm_an_refresh(true); header('Location: ' . $back . '&refreshed=' . ($e ? 'some' : '1')); exit; }
    if ($a === 'save_yt') {
      $k = trim((string)($_POST['yt_key'] ?? ''));
      if (!preg_match('/^AIza[0-9A-Za-z_-]{30,}$/', $k)) $err = "That doesn't look like a Google API key (they start with AIza).";
      else {
        try { dtm_an_get('https://www.googleapis.com/youtube/v3/channels?' . http_build_query(['part' => 'id', 'id' => DTM_YT_CHANNEL, 'key' => $k])); }
        catch (RuntimeException $e) { $err = 'YouTube turned that key down: ' . $e->getMessage() . ' Check that "YouTube Data API v3" is enabled in that Google Cloud project.'; }
        if ($err === '' && ($err = dtm_an_save_secrets(['youtube_api_key' => $k])) === '') { header('Location: analytics.php?saved=yt'); exit; }
      }
    }
    if ($a === 'save_meta') {
      $t = preg_replace('/\s+/', '', (string)($_POST['meta_token'] ?? ''));
      if (strlen($t) < 40) $err = "That's too short to be a Meta access token.";
      else {
        try {
          $me = dtm_an_get(DTM_META_API . 'me?' . http_build_query(['fields' => 'id,name', 'access_token' => $t]));
          // A personal (user) token: swap it for the Defacing The Music Page's own token, so Duck doesn't have to dig it out.
          $acc = dtm_an_get(DTM_META_API . 'me/accounts?' . http_build_query(['fields' => 'id,name,access_token', 'limit' => 100, 'access_token' => $t]));
          $pages = $acc['data'] ?? [];
          if ($pages) {
            $pick = null;
            foreach ($pages as $p) if (stripos((string)($p['name'] ?? ''), 'defacing') !== false) $pick = $p;
            if (!$pick && count($pages) === 1) $pick = $pages[0];
            if (!$pick || empty($pick['access_token'])) $err = "That's your personal token, and I couldn't find the Defacing The Music Page in it. Make a new token and tick that Page when Facebook asks which Pages.";
            else { $t = (string)$pick['access_token']; $me = $pick; }
          }
        } catch (RuntimeException $e) {
          if (!isset($me)) $err = 'Meta turned that token down: ' . $e->getMessage();
          // me/accounts fails on a Page token itself; that's fine, keep it as is.
        }
        $exp = 0;
        if ($err === '') {
          try { $dbg = dtm_an_get(DTM_META_API . 'debug_token?' . http_build_query(['input_token' => $t, 'access_token' => $t])); $exp = (int)($dbg['data']['expires_at'] ?? 0); }
          catch (RuntimeException $e) { /* can't tell; save it anyway */ }
        }
        if ($err === '' && ($err = dtm_an_save_secrets(['meta_page_token' => $t])) === '') { header('Location: analytics.php?saved=meta&page=' . rawurlencode((string)($me['name'] ?? '')) . ($exp > 0 ? '&expires=' . $exp : '')); exit; }
      }
    }
    if ($a === 'save_tt') {
      $k = trim((string)($_POST['tt_key'] ?? '')); $sec = trim((string)($_POST['tt_secret'] ?? ''));
      if (!preg_match('/^[A-Za-z0-9_-]{8,64}$/', $k)) $err = "That doesn't look like a TikTok Client key.";
      elseif ($sec === '' && dtm_an_cfg()['tt_secret'] === '') $err = 'Paste the Client secret too.';
      else { $set = ['tiktok_client_key' => $k]; if ($sec !== '') $set['tiktok_client_secret'] = $sec;
        if (($err = dtm_an_save_secrets($set)) === '') { header('Location: analytics.php?saved=ttkeys#settings'); exit; } }
    }
    if ($a === 'save_g') {
      $sec = trim((string)($_POST['g_secret'] ?? ''));
      if (!preg_match('/^[A-Za-z0-9_-]{16,80}$/', $sec)) $err = "That doesn't look like a Google client secret (it usually starts with GOCSPX-).";
      elseif (($err = dtm_an_save_secrets(['google_client_secret' => $sec])) === '') { header('Location: analytics.php?saved=gkeys#settings'); exit; }
    }
    if ($a === 'clear_g') {
      $err = dtm_an_save_secrets(['youtube_analytics_refresh_token' => '']);
      if ($err === '') { header('Location: analytics.php?cleared=1'); exit; }
    }
    if ($a === 'tick_key') {
      $err = dtm_an_save_secrets(['stats_tick_key' => bin2hex(random_bytes(16))]);
      if ($err === '') { header('Location: analytics.php#tick'); exit; }
    }
    if ($a === 'clear_tt') {
      $err = dtm_an_save_secrets(['tiktok_refresh_token' => '', 'tiktok_client_key' => '', 'tiktok_client_secret' => '']);
      if ($err === '') { header('Location: analytics.php?cleared=1'); exit; }
    }
    if ($a === 'clear_yt' || $a === 'clear_meta') {
      $err = dtm_an_save_secrets([$a === 'clear_yt' ? 'youtube_api_key' : 'meta_page_token' => '']);
      if ($err === '') { header('Location: analytics.php?cleared=1'); exit; }
    }
    if ($a === 'import_key') {
      $k = bin2hex(random_bytes(20));
      $err = dtm_an_save_secrets(['analytics_import_key' => $k]);
      if ($err === '') { dtm_session(); $_SESSION['an_import_key'] = $k; header('Location: analytics.php#import'); exit; }
    }
    if ($a === 'import') {
      [$n, $errs] = dtm_an_import((string)($_POST['data'] ?? ''), (string)($_POST['platform'] ?? 'tiktok'));
      $msg = "Imported $n row" . ($n === 1 ? '' : 's') . '.';
      if ($errs) $err = implode(' ', array_slice($errs, 0, 5)) . (count($errs) > 5 ? ' (and ' . (count($errs) - 5) . ' more)' : '');
    }
    if ($a === 'link') {
      $post = (string)($_POST['post'] ?? ''); $to = (string)($_POST['to'] ?? '');
      dtm_json_update('analytics-links.json', function ($l) use ($post, $to) { if ($to === '') unset($l[$post]); else $l[$post] = $to; return $l; });
      header('Location: ' . $back . '#videos'); exit;
    }
  } catch (RuntimeException $e) { $err = $e->getMessage(); }
}

$cfg = dtm_an_cfg();
$fetchErrors = dtm_an_refresh(false);
try { require_once __DIR__ . '/../lib/episodes.php'; dtm_episodes_autopublish(); } catch (Throwable $t) {}
$state = dtm_an_state();
$latest = dtm_an_latest();
$posts = $latest['posts'];
$groups = dtm_an_groups($posts);

// Filters.
$fPlat = (string)($_GET['platform'] ?? '');
if (!isset(DTM_AN_PLATFORMS[$fPlat])) $fPlat = '';
$fVideo = (string)($_GET['video'] ?? '');
if ($fVideo !== '' && !in_array($fVideo, $groups, true)) $fVideo = '';
$fDays = (string)($_GET['days'] ?? '48h');
if (!in_array($fDays, ['48h', '7', '28', '90', 'all'], true)) $fDays = '48h';
$keep = fn(string $k, array $p) => ($fPlat === '' || ($p['platform'] ?? '') === $fPlat) && ($fVideo === '' || ($groups[$k] ?? $k) === $fVideo);
$sel = array_filter($posts, fn($p, $k) => $keep($k, $p), ARRAY_FILTER_USE_BOTH);

// Totals now.
$tot = array_fill_keys(array_keys(DTM_AN_METRICS), 0);
foreach ($sel as $p) foreach ($tot as $m => $_) $tot[$m] += (int)($p[$m] ?? 0);
$followers = 0;
foreach ($latest['followers'] as $pl => $n) if ($fPlat === '' || $pl === $fPlat) $followers += (int)$n;

// Daily series: carry each post's numbers forward day by day, sum the selected ones.
$days = dtm_an_days();
$series = []; $state_ = []; $fstate = [];
foreach ($days as $day) {
  $d = dtm_an_day($day);
  foreach ($d['posts'] as $k => $p) $state_[$k] = array_merge($state_[$k] ?? [], $p);
  foreach ($d['followers'] as $pl => $n) $fstate[$pl] = $n;
  $row = array_fill_keys(array_keys(DTM_AN_METRICS), 0);
  foreach ($state_ as $k => $p) if (isset($posts[$k]) && $keep($k, $posts[$k])) foreach ($row as $m => $_) $row[$m] += (int)($p[$m] ?? 0);
  $row['followers'] = 0;
  foreach ($fstate as $pl => $n) if ($fPlat === '' || $pl === $fPlat) $row['followers'] += (int)$n;
  $series[$day] = $row;
}
// Change over the last 7 days (against the newest snapshot at least 7 days old).
$weekAgo = null;
foreach ($series as $day => $row) if ($day <= date('Y-m-d', strtotime('-7 days'))) $weekAgo = $row;
// Views gained per day in the chosen range.
$gain = []; $prev = null;
foreach ($series as $day => $row) { if ($prev !== null) $gain[$day] = max(0, $row['views'] - $prev['views']); $prev = $row; }
if ($fDays !== 'all' && $fDays !== '48h') $gain = array_filter($gain, fn($day) => $day > date('Y-m-d', strtotime('-' . $fDays . ' days')), ARRAY_FILTER_USE_KEY);
$gainMax = max([1] + array_values($gain));
$hourly = $fDays === '48h' ? dtm_an_hourly(48, fn($k) => isset($posts[$k]) && $keep($k, $posts[$k])) : [];
$hourMax = max([1] + array_values($hourly));
$hourHas = (bool)array_filter($hourly);

// Video rows (groups), with the per-platform posts inside.
$rows = [];
foreach ($sel as $k => $p) {
  $g = $groups[$k] ?? $k;
  $r = &$rows[$g];
  $r['posts'][$k] = $p;
  foreach (DTM_AN_METRICS as $m => $_) $r[$m] = ($r[$m] ?? 0) + (int)($p[$m] ?? 0);
  $lead = $posts[$g] ?? $p;
  $r['title'] = (string)(($lead['title'] ?? '') !== '' ? $lead['title'] : ($p['title'] ?? ''));
  $r['published'] = min(array_filter([$r['published'] ?? null, $p['published'] ?? null]) ?: ['']);
  unset($r);
}
uasort($rows, fn($a, $b) => $b['views'] <=> $a['views']);
// Video choices for the filter and the "Same video as" menus.
$choices = [];
foreach (array_unique(array_values($groups)) as $g) if (isset($posts[$g])) $choices[$g] = DTM_AN_PLATFORMS[$posts[$g]['platform']] . ': ' . mb_strimwidth((string)($posts[$g]['title'] ?: $posts[$g]['id']), 0, 60, '…');
asort($choices);

dtm_session();
$newImportKey = $_SESSION['an_import_key'] ?? '';
unset($_SESSION['an_import_key']);
$q = fn(array $over) => 'analytics.php?' . http_build_query(array_filter(array_merge(['platform' => $fPlat, 'video' => $fVideo, 'days' => $fDays], $over), fn($v) => $v !== ''));
$delta = function (string $m) use ($weekAgo, $series) {
  if ($weekAgo === null || !$series) return '';
  $now = end($series)[$m] ?? 0; $d = $now - ($weekAgo[$m] ?? 0);
  return '<span class="an-delta">' . ($d >= 0 ? '+' : '−') . number_format(abs($d)) . ' this week</span>';
};

// Line graph (Duck, Nov 26: "i didnt want a bar chart, i wanted a line graph"): an SVG line + soft fill, with
// invisible hover columns on top that show a dot and the exact number.
function dtm_an_line(array $pts, string $aria): string {
  $n = count($pts); if ($n < 1) return '';
  $max = max(1, max(array_column($pts, 1)));
  $x = fn($i) => $n === 1 ? 500 : round($i / ($n - 1) * 1000, 2);
  $y = fn($v) => round(100 - $v / $max * 92, 2);
  $line = ''; foreach (array_values($pts) as $i => [$l, $v]) $line .= ($i ? ' L' : 'M') . $x($i) . ' ' . $y($v);
  $area = $line . ' L' . $x($n - 1) . ' 100 L' . $x(0) . ' 100 Z';
  $o = '<div class="an-line" role="img" aria-label="' . h($aria) . '"><span class="an-ymax">' . number_format($max) . '</span>'
     . '<svg viewBox="0 0 1000 100" preserveAspectRatio="none" aria-hidden="true">'
     . '<line x1="0" y1="8" x2="1000" y2="8" class="an-grid"/><line x1="0" y1="54" x2="1000" y2="54" class="an-grid"/>'
     . '<path d="' . $area . '" class="an-area"/><path d="' . $line . '" class="an-stroke"/></svg><div class="an-hits">';
  foreach (array_values($pts) as $i => [$l, $v]) {
    $o .= '<div class="an-hit" tabindex="0" style="--x:' . ($n === 1 ? 50 : round($i / ($n - 1) * 100, 3)) . '%;--y:' . round($v / $max * 92, 2) . '%"><b></b><span class="an-tip">' . h($l) . ': ' . number_format($v) . ' views</span></div>';
  }
  return $o . '</div></div>';
}

dtm_page_start('Stats', $user, true);
?>
<section class="admin-card an">
  <div class="an-head"><h1 class="metal">Stats</h1>
    <span class="small an-when"><?= !empty($state['fetched']) ? 'Updated ' . h(date('M j, g:ia', (int)$state['fetched'])) : 'Not fetched yet' ?></span>
    <form method="post" action="<?= h($q([])) ?>" class="an-refresh"><?= dtm_csrf_field() ?><input type="hidden" name="action" value="refresh"><button class="an-btn" onclick="this.disabled=true;this.textContent='Fetching...';this.form.submit()">Refresh</button></form>
  </div>
  <?php if (isset($_GET['saved'])): ?><p class="notice ok bang" role="status"><?= $_GET['saved'] === 'yt' ? 'YouTube key saved. Hit Refresh.' : 'Meta token saved' . (($_GET['page'] ?? '') !== '' ? ' (Page: ' . h((string)$_GET['page']) . ')' : '') . '. Hit Refresh.' ?></p><?php endif; ?>
  <?php if (($_GET['saved'] ?? '') === 'meta' && (int)($_GET['expires'] ?? 0) > 0): ?><p class="notice err bang" role="alert">Heads up: that token stops working <?= h(date('M j, g:ia', (int)$_GET['expires'])) ?>. To make a permanent one: open Meta's Access Token Debugger, paste your token from Graph API Explorer, click Debug, then "Extend Access Token" at the bottom, and paste that new one here.</p><?php endif; ?>
  <?php if (($_GET['saved'] ?? '') === 'tt'): ?><p class="notice ok bang" role="status">TikTok connected. Hit Refresh.</p><?php endif; ?>
  <?php if (($_GET['saved'] ?? '') === 'ttkeys'): ?><p class="notice ok bang" role="status">TikTok keys saved. Now click Connect TikTok.</p><?php endif; ?>
  <?php if (($_GET['saved'] ?? '') === 'g'): ?><p class="notice ok bang" role="status">YouTube Analytics connected. Hit Refresh (YouTube's own numbers run about 2 days behind).</p><?php endif; ?>
  <?php if (($_GET['saved'] ?? '') === 'gkeys'): ?><p class="notice ok bang" role="status">Google secret saved. Now click Connect YouTube Analytics.</p><?php endif; ?>
  <?php if (($_GET['g_err'] ?? '') !== ''): ?><p class="notice err bang" role="alert"><?= h(mb_substr((string)$_GET['g_err'], 0, 300)) ?></p><?php endif; ?>
  <?php if (($_GET['tt_err'] ?? '') !== ''): ?><p class="notice err bang" role="alert"><?= h(mb_substr((string)$_GET['tt_err'], 0, 300)) ?></p><?php endif; ?>
  <?php if (isset($_GET['cleared'])): ?><p class="notice ok bang" role="status">Removed.</p><?php endif; ?>
  <?php if (($_GET['refreshed'] ?? '') === '1'): ?><p class="notice ok bang" role="status">Fresh numbers fetched.</p><?php endif; ?>
  <?php if ($msg): ?><p class="notice ok bang" role="status"><?= h($msg) ?></p><?php endif; ?>
  <?php if ($err): ?><p class="notice err bang" role="alert"><?= h($err) ?></p><?php endif; ?>
  <?php foreach ($fetchErrors as $pl => $e): ?><p class="notice err bang" role="alert"><?= h((DTM_AN_PLATFORMS[$pl] ?? $pl) . ': ' . $e) ?></p><?php endforeach; ?>

  <form method="get" class="an-filters">
    <label><span class="sr">Platform</span><select name="platform" class="admin-select" onchange="this.form.submit()">
      <option value="">All platforms</option>
      <?php foreach (DTM_AN_PLATFORMS as $k => $l): ?><option value="<?= h($k) ?>"<?= $fPlat === $k ? ' selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?>
    </select></label>
    <label><span class="sr">Video</span><select name="video" class="admin-select" onchange="this.form.submit()">
      <option value="">All videos</option>
      <?php foreach ($choices as $g => $l): ?><option value="<?= h($g) ?>"<?= $fVideo === $g ? ' selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?>
    </select></label>
    <label><span class="sr">Trend</span><select name="days" class="admin-select" onchange="this.form.submit()">
      <?php foreach (['48h' => 'Chart: 48 hours', '7' => 'Chart: 7 days', '28' => 'Chart: 28 days', '90' => 'Chart: 90 days', 'all' => 'Chart: all time'] as $k => $l): ?><option value="<?= h($k) ?>"<?= $fDays === (string)$k ? ' selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?>
    </select></label>
    <noscript><button class="btn">Show</button></noscript>
  </form>

  <div class="an-tiles">
    <?php foreach (DTM_AN_METRICS as $m => $l): ?>
    <div class="an-tile"><span class="an-lbl"><?= h($l) ?></span><b style="--n:<?= max(3, strlen($fmt($tot[$m]))) ?>"><?= $fmt($tot[$m]) ?></b><?= $delta($m) ?></div>
    <?php endforeach; ?>
    <?php $wm = array_sum(array_map(fn($p) => (int)($p['watch_minutes'] ?? 0), $sel)); if ($wm > 0): ?><div class="an-tile" title="YouTube only; Shorts and long videos together"><span class="an-lbl">YT watch hours</span><b style="--n:<?= max(3, strlen(number_format($wm / 60, 1))) ?>"><?= number_format($wm / 60, 1) ?></b></div><?php endif; ?>
    <?php if ($fVideo === ''): ?><div class="an-tile"><span class="an-lbl">Followers</span><b style="--n:<?= max(3, strlen($fmt($followers))) ?>"><?= $fmt($followers) ?></b><?= $delta('followers') ?></div><?php endif; ?>
  </div>

  <?php if ($fVideo === ''): ?>
  <h2 class="bang">By platform</h2>
  <div class="an-scroll"><table class="an-table an-plat">
    <thead><tr><th>Platform</th><th class="num">Followers</th><?php foreach (DTM_AN_METRICS as $l): ?><th class="num"><?= h($l) ?></th><?php endforeach; ?><th class="num">Posts</th></tr></thead>
    <tbody>
    <?php foreach (DTM_AN_PLATFORMS as $pl => $label): if ($fPlat !== '' && $pl !== $fPlat) continue;
      $pp = array_filter($posts, fn($p) => $p['platform'] === $pl); $t = array_fill_keys(array_keys(DTM_AN_METRICS), 0);
      foreach ($pp as $p) foreach ($t as $m => $_) $t[$m] += (int)($p[$m] ?? 0); ?>
      <tr><td><a href="stats-videos.php?p=<?= h($pl) ?>" title="See every <?= h($label) ?> video"><?= h($label) ?> <span class="an-go">›</span></a></td><td class="num"><?= $fmt($latest['followers'][$pl] ?? null) ?></td>
        <?php foreach ($t as $m => $v): $has = (bool)array_filter($pp, fn($p) => ($p[$m] ?? null) !== null); ?><td class="num"<?= !$has && $pp ? ' title="' . h($label) . ' doesn\'t give us this number"' : '' ?>><?= $has ? $fmt($v) : '–' ?></td><?php endforeach; ?><td class="num"><?= count($pp) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>

  <?php if ($fDays === '48h'): ?>
  <h2 class="bang">Views, last 48 hours <span class="small an-hint">per hour</span></h2>
  <?php if (!$hourHas): ?>
  <p class="small">The hourly chart fills in as the site checks your numbers each hour. Turn on the hourly ping (Connections) so it keeps going when this page isn't open.</p>
  <?php else: ?>
  <?= dtm_an_line(array_map(fn($ts, $v) => [date('D g a', $ts), $v], array_keys($hourly), $hourly), 'Views per hour, last 48 hours') ?>
  <div class="an-axis small"><span><?= h(date('D g a', array_key_first($hourly))) ?></span><span><?= number_format(array_sum($hourly)) ?> views in <?= count($hourly) < 48 ? count($hourly) . ' hours (since hourly tracking started)' : '48 hours' ?> · most in an hour: <?= number_format($hourMax) ?></span><span><?= h(date('D g a', array_key_last($hourly))) ?></span></div>
  <?php endif; ?>
  <?php else: ?>
  <h2 class="bang">Views gained per day</h2>
  <?php if (count($gain) < 1): ?>
  <p class="small">The trend starts once there are two days of numbers. Come back tomorrow.</p>
  <?php else: ?>
  <?= dtm_an_line(array_map(fn($d, $v) => [date('M j', strtotime($d)), $v], array_keys($gain), $gain), 'Views gained per day') ?>
  <div class="an-axis small"><span><?= h(date('M j', strtotime(array_key_first($gain)))) ?></span><span>Most in a day: <?= number_format($gainMax) ?></span><span><?= h(date('M j', strtotime(array_key_last($gain)))) ?></span></div>
  <?php endif; ?>
  <?php endif; ?>

  <h2 class="bang" id="videos">By video <span class="small an-hint">same clip on every platform = one row; open a row to fix a match; click a column to sort</span></h2>
  <?php if (!$rows): ?><p class="small">No posts yet. Add the YouTube key below and hit Refresh.</p><?php else: ?>
  <div class="an-scroll"><table class="an-table an-sort">
    <thead><tr><th data-k="t">Video</th><th data-k="d">Posted</th><?php foreach (DTM_AN_METRICS as $l): ?><th class="num" data-k="n"><?= h($l) ?></th><?php endforeach; ?></tr></thead>
    <tbody>
    <?php foreach ($rows as $g => $r): ?>
    <tr>
      <td data-v="<?= h(strtolower($r['title'])) ?>"><details><summary><b><?= h($r['title'] !== '' ? mb_strimwidth($r['title'], 0, 80, '…') : '(no title)') ?></b>
        <?php $cnt = array_count_values(array_column($r['posts'], 'platform')); $ab = ['youtube' => 'YT', 'facebook' => 'FB', 'instagram' => 'IG', 'tiktok' => 'TT']; ?>
        <span class="an-plats"><?= h(implode(' · ', array_map(fn($pl) => $ab[$pl] . ($cnt[$pl] > 1 ? '×' . $cnt[$pl] : ''), array_keys(array_intersect_key(array_flip(array_keys($ab)), $cnt))))) ?></span></summary>
        <ul class="an-posts">
          <?php foreach ($r['posts'] as $k => $p): ?>
          <li><span class="an-plats"><?= h(DTM_AN_PLATFORMS[$p['platform']]) ?>:</span>
            <?php if (!empty($p['url'])): ?><a href="<?= h($p['url']) ?>" target="_blank" rel="noopener"><?= h(mb_strimwidth((string)($p['title'] ?: $p['id']), 0, 70, '…')) ?></a><?php else: ?><?= h(mb_strimwidth((string)($p['title'] ?: $p['id']), 0, 70, '…')) ?><?php endif; ?>
            <span class="small"><?php foreach (DTM_AN_METRICS as $m => $l): if (($p[$m] ?? null) === null) continue; ?> · <?= $fmt($p[$m]) ?> <?= h(strtolower($l)) ?><?php endforeach; ?><?= !empty($p['seconds']) ? ' · ' . gmdate($p['seconds'] >= 3600 ? 'G:i:s' : 'i:s', (int)$p['seconds']) : '' ?></span>
            <form method="post" action="<?= h($q([])) ?>" class="an-link"><?= dtm_csrf_field() ?><input type="hidden" name="action" value="link"><input type="hidden" name="post" value="<?= h($k) ?>">
              <select name="to" class="admin-select" onchange="this.form.submit()" aria-label="Same video as">
                <option value="">Same video as: best guess</option>
                <option value="self">Its own video</option>
                <?php foreach ($choices as $cg => $cl): if ($cg === $k) continue; ?><option value="<?= h($cg) ?>"><?= h($cl) ?></option><?php endforeach; ?>
              </select></form>
          </li>
          <?php endforeach; ?>
        </ul></details></td>
      <td data-v="<?= h($r['published']) ?>"><?= $r['published'] !== '' ? h(date('M j', strtotime($r['published']))) : '' ?></td>
      <?php foreach (DTM_AN_METRICS as $m => $_): ?><td class="num" data-v="<?= (int)$r[$m] ?>"><?= $fmt($r[$m]) ?></td><?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>

<section class="admin-card an an-fold" id="settings"><details<?= ($cfg['yt'] === '' || $cfg['meta'] === '' || $cfg['tt_refresh'] === '' || $cfg['g_refresh'] === '' || isset($_GET['g_err']) || ($_GET['saved'] ?? '') === 'gkeys' || isset($_GET['tt_err']) || ($_GET['saved'] ?? '') === 'ttkeys') ? ' open' : '' ?>>
  <summary><h2 class="bang">Connections</h2> <span class="small">YouTube <?= $cfg['yt'] !== '' ? 'on' : 'off' ?> · YouTube shares <?= $cfg['g_refresh'] !== '' ? 'on' : 'off' ?> · Facebook + Instagram <?= $cfg['meta'] !== '' ? 'on' : 'off' ?> · TikTok <?= $cfg['tt_refresh'] !== '' ? 'on' : 'off' ?></span></summary>
  <p class="small">Keys and tokens are saved only in the secrets file on the server (one folder above the site), never shown here and never in git. Never paste them in a chat.</p>

  <h3 class="bang">YouTube <?= $cfg['yt'] !== '' ? '<span class="an-ok">connected</span>' : '<span class="an-no">not set</span>' ?></h3>
  <details<?= $cfg['yt'] === '' ? ' open' : '' ?>><summary class="small">How to get the key (about 5 minutes)</summary>
    <ol class="small">
      <li>Go to <a href="https://console.cloud.google.com/apis/library/youtube.googleapis.com" target="_blank" rel="noopener">console.cloud.google.com, YouTube Data API v3</a>, pick the <b>duck-andrabbit-co</b> project at the top, and click <b>Enable</b>.</li>
      <li>Go to <b>APIs &amp; Services &gt; Credentials &gt; Create credentials &gt; API key</b>.</li>
      <li>Click the new key, under <b>API restrictions</b> pick <b>Restrict key</b> and tick only <b>YouTube Data API v3</b>. Save.</li>
      <li>Copy the key (starts with AIza) and paste it below.</li>
    </ol></details>
  <form method="post" class="admin-form an-key"><?= dtm_csrf_field() ?>
    <input type="password" name="yt_key" autocomplete="off" placeholder="<?= $cfg['yt'] !== '' ? 'Paste a new key to replace it' : 'AIza...' ?>" aria-label="YouTube API key">
    <button name="action" value="save_yt" class="btn bang">Save</button>
    <?php if ($cfg['yt'] !== ''): ?><button name="action" value="clear_yt" class="linkish" onclick="return confirm('Remove the YouTube key?')">Remove</button><?php endif; ?>
  </form>

  <h3 class="bang">YouTube shares + watch time <?= $cfg['g_refresh'] !== '' ? '<span class="an-ok">connected</span>' : '<span class="an-no">not set</span>' ?></h3>
  <details<?= $cfg['g_refresh'] === '' ? ' open' : '' ?>><summary class="small">How to set it up (about 5 minutes)</summary>
    <ol class="small">
      <li>In <a href="https://console.cloud.google.com/apis/library/youtubeanalytics.googleapis.com" target="_blank" rel="noopener">Google Cloud, YouTube Analytics API</a> (duck-andrabbit-co project), click <b>Enable</b>.</li>
      <li><b>APIs &amp; Services &gt; Credentials</b>, open the OAuth client <b>dnr-sites-signin</b>. Under <b>Authorized redirect URIs</b> add <code class="an-secret">https://defacingthemusic.com/admin/analytics-google.php</code> and Save. Copy its <b>Client secret</b> (starts with GOCSPX-) and paste it below.</li>
      <li><b>Google Auth Platform &gt; Audience</b>: if it says <b>Testing</b>, click <b>Publish app</b> (otherwise Google logs us out every 7 days). It stays unverified; that's fine for our own channel.</li>
      <li>Click <b>Connect YouTube Analytics</b> and sign in with the Google account that owns the DTM channel. If Google warns the app isn't verified, click Advanced, then continue.</li>
    </ol></details>
  <form method="post" class="admin-form an-key"><?= dtm_csrf_field() ?>
    <input type="password" name="g_secret" autocomplete="off" placeholder="<?= $cfg['g_secret'] !== '' ? 'Google client secret (saved)' : 'Google client secret (GOCSPX-...)' ?>" aria-label="Google client secret">
    <button name="action" value="save_g" class="btn bang">Save</button>
    <?php if ($cfg['g_refresh'] !== ''): ?><button name="action" value="clear_g" class="linkish" onclick="return confirm('Disconnect YouTube Analytics?')">Disconnect</button><?php endif; ?>
  </form>
  <?php if ($cfg['g_secret'] !== '' && $cfg['g_id'] !== ''): ?>
  <form method="post" action="analytics-google.php"><?= dtm_csrf_field() ?><button class="an-btn"><?= $cfg['g_refresh'] !== '' ? 'Reconnect YouTube Analytics' : 'Connect YouTube Analytics' ?></button></form>
  <?php endif; ?>

  <h3 class="bang">Facebook + Instagram <?= $cfg['meta'] !== '' ? '<span class="an-ok">connected</span>' : '<span class="an-no">not set</span>' ?></h3>
  <details<?= $cfg['meta'] === '' ? ' open' : '' ?>><summary class="small">How to get the Page token (about 10 minutes)</summary>
    <ol class="small">
      <li>Instagram @defacingthemusicpod has to be a Professional (Creator or Business) account linked to the Defacing The Music Facebook Page.</li>
      <li>Go to <a href="https://developers.facebook.com/apps" target="_blank" rel="noopener">developers.facebook.com/apps</a>, <b>Create app</b>, type <b>Business</b> (or "Other", then Business), name it "DTM Analytics". It stays in development mode; that's fine.</li>
      <li>Open <a href="https://developers.facebook.com/tools/explorer/" target="_blank" rel="noopener">Graph API Explorer</a>, pick that app, then under <b>Permissions</b> add: <code>pages_show_list</code>, <code>pages_read_engagement</code>, <code>read_insights</code>, <code>instagram_basic</code>, <code>instagram_manage_insights</code>, <code>business_management</code>. Click <b>Generate Access Token</b> and allow it for the Defacing The Music Page and Instagram.</li>
      <li>Make it long-lived: open the <a href="https://developers.facebook.com/tools/debug/accesstoken/" target="_blank" rel="noopener">Access Token Debugger</a>, paste the token, click <b>Debug</b>, then <b>Extend Access Token</b> at the bottom. Copy the new one.</li>
      <li>Back in Graph API Explorer, paste that long-lived token in the token box, and in the query box run <code>me/accounts</code>. Find Defacing The Music in the result and copy its <b>access_token</b>. That's the Page token (it doesn't expire). Paste it below.</li>
    </ol></details>
  <form method="post" class="admin-form an-key"><?= dtm_csrf_field() ?>
    <input type="password" name="meta_token" autocomplete="off" placeholder="<?= $cfg['meta'] !== '' ? 'Paste a new token to replace it' : 'EAA...' ?>" aria-label="Meta Page access token">
    <button name="action" value="save_meta" class="btn bang">Save</button>
    <?php if ($cfg['meta'] !== ''): ?><button name="action" value="clear_meta" class="linkish" onclick="return confirm('Remove the Meta token?')">Remove</button><?php endif; ?>
  </form>

  <h3 class="bang">TikTok <?= $cfg['tt_refresh'] !== '' ? '<span class="an-ok">connected</span>' : ($cfg['tt_key'] !== '' ? '<span class="an-no">keys saved, not connected</span>' : '<span class="an-no">not set</span>') ?></h3>
  <details<?= $cfg['tt_refresh'] === '' ? ' open' : '' ?>><summary class="small">How to set it up (about 10 minutes)</summary>
    <ol class="small">
      <li>At <a href="https://developers.tiktok.com/" target="_blank" rel="noopener">developers.tiktok.com</a>, create an app of type <b>Other</b>.</li>
      <li>Add the products <b>Login Kit</b> and <b>Display API</b> (or whatever TikTok currently calls the "show your own videos" product) and the scopes <code>user.info.basic</code>, <code>user.info.stats</code>, <code>video.list</code>.</li>
      <li>Login Kit redirect URI (Web): <code class="an-secret">https://defacingthemusic.com/admin/analytics-tiktok.php</code></li>
      <li>Turn on <b>Sandbox</b> and add your TikTok account (@defacingthemusic) as a target user, so it works before TikTok reviews the app.</li>
      <li>Copy the app's <b>Client key</b> and <b>Client secret</b> (from the sandbox, if you use it) below, Save, then click <b>Connect TikTok</b> and log in as @defacingthemusic.</li>
    </ol></details>
  <form method="post" class="admin-form an-key"><?= dtm_csrf_field() ?>
    <input type="text" name="tt_key" autocomplete="off" placeholder="Client key" value="<?= h($cfg['tt_key']) ?>" aria-label="TikTok Client key">
    <input type="password" name="tt_secret" autocomplete="off" placeholder="<?= $cfg['tt_secret'] !== '' ? 'Client secret (saved)' : 'Client secret' ?>" aria-label="TikTok Client secret">
    <button name="action" value="save_tt" class="btn bang">Save</button>
    <?php if ($cfg['tt_key'] !== ''): ?><button name="action" value="clear_tt" class="linkish" onclick="return confirm('Disconnect TikTok and remove its keys?')">Remove</button><?php endif; ?>
  </form>
  <?php if ($cfg['tt_key'] !== '' && $cfg['tt_secret'] !== ''): ?>
  <form method="post" action="analytics-tiktok.php"><?= dtm_csrf_field() ?><button class="an-btn"><?= $cfg['tt_refresh'] !== '' ? 'Reconnect TikTok' : 'Connect TikTok' ?></button></form>
  <?php endif; ?>

  <h3 class="bang" id="tick">Hourly ping <?= $cfg['tick'] !== '' ? '<span class="an-ok">link made</span>' : '<span class="an-no">off</span>' ?></h3>
  <p class="small">Keeps the 48-hour chart filling in when nobody has this page open. A free scheduler opens a secret link every hour; the link only fetches numbers.</p>
  <?php if ($cfg['tick'] !== ''): ?>
  <p class="small"><code class="an-secret">https://defacingthemusic.com/admin/stats-tick.php?key=<?= h($cfg['tick']) ?></code></p>
  <details><summary class="small">Set it up on cron-job.org (about 3 minutes)</summary><ol class="small">
    <li>Make a free account at <a href="https://cron-job.org" target="_blank" rel="noopener">cron-job.org</a>.</li>
    <li><b>Create cronjob</b>: title "DTM stats", paste the link above as the URL, schedule <b>Every hour</b>, Save.</li>
    <li>Done. Its history page shows a green check each hour.</li>
  </ol></details>
  <?php endif; ?>
  <form method="post"><?= dtm_csrf_field() ?><button name="action" value="tick_key" class="an-btn"<?= $cfg['tick'] !== '' ? ' onclick="return confirm(\'Make a new link? The old one stops working.\')"' : '' ?>><?= $cfg['tick'] !== '' ? 'Make a new link' : 'Make the hourly ping link' ?></button></form>
</details></section>

<section class="admin-card an an-fold" id="import"><details<?= $newImportKey !== '' || $msg !== '' ? ' open' : '' ?>>
  <summary><h2 class="bang">TikTok numbers (import)</h2> <span class="small">paste numbers or set up Defa</span></summary>
  <p class="small">TikTok has no API we can use yet, so its numbers come in here: paste them below, or Defa posts them automatically every day or two. Works for any platform as a backup. One row per post; <b>date</b> is the day the numbers were taken. A row with only <b>followers</b> sets the follower count.</p>
  <pre class="an-example">date,platform,post,caption,views,likes,comments,shares,followers
2026-11-25,tiktok,https://www.tiktok.com/@defacingthemusic/video/1234567890,Slayer fans vs the seats,5400,320,41,12,
2026-11-25,tiktok,,,,,,,212</pre>
  <form method="post" class="admin-form"><?= dtm_csrf_field() ?>
    <textarea name="data" rows="6" class="script-box" placeholder="Paste CSV (with that header row) or JSON"></textarea>
    <label class="small">If a row has no platform, use <select name="platform" class="admin-select"><?php foreach (DTM_AN_PLATFORMS as $k => $l): ?><option value="<?= h($k) ?>"<?= $k === 'tiktok' ? ' selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?></select></label>
    <button name="action" value="import" class="btn bang">Import</button>
  </form>

  <h3 class="bang">Automatic import (for Defa)</h3>
  <?php if ($newImportKey !== ''): ?>
  <p class="notice ok bang" role="status">New import key (shown this one time only, copy it now): <code class="an-secret"><?= h($newImportKey) ?></code></p>
  <?php endif; ?>
  <p class="small">POST the same CSV or JSON to <code>https://defacingthemusic.com/admin/analytics-import.php</code> with the header <code>X-Import-Key: &lt;key&gt;</code> (or a <code>key</code> form field) and the data as the body (or a <code>data</code> form field). <?= $cfg['import'] !== '' ? 'An import key is set.' : 'No import key yet.' ?></p>
  <form method="post"><?= dtm_csrf_field() ?><button name="action" value="import_key" class="btn btn-ghost bang"<?= $cfg['import'] !== '' ? ' onclick="return confirm(\'Make a new key? The old one stops working.\')"' : '' ?>><?= $cfg['import'] !== '' ? 'Make a new import key' : 'Make an import key' ?></button></form>
</details></section>

<script>
// Click a column header to sort the By video table.
document.querySelectorAll('.an-sort th').forEach((th, i) => th.addEventListener('click', () => {
  const tb = th.closest('table').tBodies[0], num = th.dataset.k === 'n', asc = th.dataset.dir !== 'asc';
  th.closest('tr').querySelectorAll('th').forEach(x => delete x.dataset.dir); th.dataset.dir = asc ? 'asc' : 'desc';
  [...tb.rows].sort((a, b) => { let x = a.cells[i].dataset.v || '', y = b.cells[i].dataset.v || '';
    if (num) { x = +x; y = +y; } return (x > y ? 1 : x < y ? -1 : 0) * (asc ? 1 : -1) * (num ? -1 : 1); }).forEach(r => tb.appendChild(r));
}));
</script>
<?php dtm_page_end();
