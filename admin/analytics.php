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
        try { $me = dtm_an_get(DTM_META_API . 'me?' . http_build_query(['fields' => 'id,name', 'access_token' => $t])); }
        catch (RuntimeException $e) { $err = 'Meta turned that token down: ' . $e->getMessage(); }
        if ($err === '' && ($err = dtm_an_save_secrets(['meta_page_token' => $t])) === '') { header('Location: analytics.php?saved=meta&page=' . rawurlencode((string)($me['name'] ?? ''))); exit; }
      }
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
$state = dtm_an_state();
$latest = dtm_an_latest();
$posts = $latest['posts'];
$groups = dtm_an_groups($posts);

// Filters.
$fPlat = (string)($_GET['platform'] ?? '');
if (!isset(DTM_AN_PLATFORMS[$fPlat])) $fPlat = '';
$fVideo = (string)($_GET['video'] ?? '');
if ($fVideo !== '' && !in_array($fVideo, $groups, true)) $fVideo = '';
$fDays = (string)($_GET['days'] ?? '28');
if (!in_array($fDays, ['7', '28', '90', 'all'], true)) $fDays = '28';
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
if ($fDays !== 'all') $gain = array_filter($gain, fn($day) => $day > date('Y-m-d', strtotime('-' . $fDays . ' days')), ARRAY_FILTER_USE_KEY);
$gainMax = max([1] + array_values($gain));

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

dtm_page_start('Analytics', $user, true);
?>
<section class="admin-card an">
  <h1 class="metal">Analytics</h1>
  <p class="small">Lifetime numbers for every post, all platforms together. <?php if (!empty($state['fetched'])): ?>Last fetched <?= h(date('M j, g:ia', (int)$state['fetched'])) ?>; it fetches again by itself every few hours when you open this page.<?php endif; ?></p>
  <?php if (isset($_GET['saved'])): ?><p class="notice ok bang" role="status"><?= $_GET['saved'] === 'yt' ? 'YouTube key saved. Hit Refresh now.' : 'Meta token saved' . (($_GET['page'] ?? '') !== '' ? ' (Page: ' . h((string)$_GET['page']) . ')' : '') . '. Hit Refresh now.' ?></p><?php endif; ?>
  <?php if (isset($_GET['cleared'])): ?><p class="notice ok bang" role="status">Removed.</p><?php endif; ?>
  <?php if (($_GET['refreshed'] ?? '') === '1'): ?><p class="notice ok bang" role="status">Fresh numbers fetched.</p><?php endif; ?>
  <?php if ($msg): ?><p class="notice ok bang" role="status"><?= h($msg) ?></p><?php endif; ?>
  <?php if ($err): ?><p class="notice err bang" role="alert"><?= h($err) ?></p><?php endif; ?>
  <?php foreach ($fetchErrors as $pl => $e): ?><p class="notice err bang" role="alert"><?= h((DTM_AN_PLATFORMS[$pl] ?? $pl) . ': ' . $e) ?></p><?php endforeach; ?>

  <form method="get" class="an-filters">
    <label>Platform <select name="platform" class="admin-select" onchange="this.form.submit()">
      <option value="">All platforms</option>
      <?php foreach (DTM_AN_PLATFORMS as $k => $l): ?><option value="<?= h($k) ?>"<?= $fPlat === $k ? ' selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?>
    </select></label>
    <label>Video <select name="video" class="admin-select" onchange="this.form.submit()">
      <option value="">All videos</option>
      <?php foreach ($choices as $g => $l): ?><option value="<?= h($g) ?>"<?= $fVideo === $g ? ' selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?>
    </select></label>
    <label>Trend <select name="days" class="admin-select" onchange="this.form.submit()">
      <?php foreach (['7' => 'Last 7 days', '28' => 'Last 28 days', '90' => 'Last 90 days', 'all' => 'All time'] as $k => $l): ?><option value="<?= h($k) ?>"<?= $fDays === (string)$k ? ' selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?>
    </select></label>
    <noscript><button class="btn">Show</button></noscript>
  </form>
  <form method="post" action="<?= h($q([])) ?>" class="an-refresh"><?= dtm_csrf_field() ?><button name="action" value="refresh" class="btn btn-ghost bang" onclick="this.disabled=true;this.textContent='Fetching...';this.form.submit()">Refresh now</button></form>

  <div class="an-tiles">
    <?php foreach (DTM_AN_METRICS as $m => $l): ?>
    <div class="an-tile"><span class="an-lbl"><?= h($l) ?></span><b><?= $fmt($tot[$m]) ?></b><?= $delta($m) ?></div>
    <?php endforeach; ?>
    <?php if ($fVideo === ''): ?><div class="an-tile"><span class="an-lbl">Followers</span><b><?= $fmt($followers) ?></b><?= $delta('followers') ?></div><?php endif; ?>
  </div>
  <p class="small an-later">Watch time, impressions and click-through rate: coming later. They need YouTube Analytics sign-in (a Google OAuth connection), not just a key. Until then, check them in YouTube Studio.</p>

  <h2 class="bang">Views gained per day</h2>
  <?php if (count($gain) < 1): ?>
  <p class="small">The trend starts once there are two days of numbers. Come back tomorrow.</p>
  <?php else: ?>
  <div class="an-chart" role="img" aria-label="Views gained per day">
    <?php foreach ($gain as $day => $v): ?>
    <div class="an-col" tabindex="0"><i style="height:<?= max(2, round($v / $gainMax * 100, 1)) ?>%"></i><span class="an-tip"><?= h(date('M j', strtotime($day))) ?>: <?= number_format($v) ?> views</span></div>
    <?php endforeach; ?>
  </div>
  <div class="an-axis small"><span><?= h(date('M j', strtotime(array_key_first($gain)))) ?></span><span>Most in a day: <?= number_format($gainMax) ?></span><span><?= h(date('M j', strtotime(array_key_last($gain)))) ?></span></div>
  <?php endif; ?>

  <?php if ($fVideo === ''): ?>
  <h2 class="bang">By platform</h2>
  <table class="an-table">
    <thead><tr><th>Platform</th><th class="num">Followers</th><?php foreach (DTM_AN_METRICS as $l): ?><th class="num"><?= h($l) ?></th><?php endforeach; ?><th class="num">Posts</th></tr></thead>
    <tbody>
    <?php foreach (DTM_AN_PLATFORMS as $pl => $label): if ($fPlat !== '' && $pl !== $fPlat) continue;
      $pp = array_filter($posts, fn($p) => $p['platform'] === $pl); $t = array_fill_keys(array_keys(DTM_AN_METRICS), 0);
      foreach ($pp as $p) foreach ($t as $m => $_) $t[$m] += (int)($p[$m] ?? 0); ?>
      <tr><td><a href="<?= h($q(['platform' => $pl])) ?>"><?= h($label) ?></a></td><td class="num"><?= $fmt($latest['followers'][$pl] ?? null) ?></td>
        <?php foreach ($t as $v): ?><td class="num"><?= $pp ? $fmt($v) : '–' ?></td><?php endforeach; ?><td class="num"><?= count($pp) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <h2 class="bang" id="videos">By video</h2>
  <?php if (!$rows): ?><p class="small">No posts yet. Add the YouTube key below and hit Refresh now.</p><?php else: ?>
  <p class="small">The same clip on several platforms rolls up into one row (best guess by title and date). Open a row to see each platform and fix a wrong match. Click a column to sort.</p>
  <table class="an-table an-sort">
    <thead><tr><th data-k="t">Video</th><th data-k="d">Posted</th><?php foreach (DTM_AN_METRICS as $l): ?><th class="num" data-k="n"><?= h($l) ?></th><?php endforeach; ?></tr></thead>
    <tbody>
    <?php foreach ($rows as $g => $r): ?>
    <tr>
      <td data-v="<?= h(strtolower($r['title'])) ?>"><details><summary><b><?= h($r['title'] !== '' ? mb_strimwidth($r['title'], 0, 80, '…') : '(no title)') ?></b>
        <?php foreach ($r['posts'] as $p): ?><span class="an-chip"><?= h(DTM_AN_PLATFORMS[$p['platform']]) ?></span><?php endforeach; ?></summary>
        <ul class="an-posts">
          <?php foreach ($r['posts'] as $k => $p): ?>
          <li><span class="an-chip"><?= h(DTM_AN_PLATFORMS[$p['platform']]) ?></span>
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
  </table>
  <?php endif; ?>
</section>

<section class="admin-card an" id="settings">
  <h2 class="bang">Connections</h2>
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
</section>

<section class="admin-card an" id="import">
  <h2 class="bang">TikTok numbers (import)</h2>
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
</section>

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
