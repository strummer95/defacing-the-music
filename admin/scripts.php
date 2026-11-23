<?php
// Scripts library: unnumbered scripts with a status. "Make it an episode" gives one the next episode number.
// Reader view lives in prompter.php; numbered episodes live in episodes.php.
require __DIR__ . '/../lib/cutaways.php';
$user = dtm_require_admin();
// The script list moved to Episodes and Side Stories (Duck, Nov 23); this page is the editor.
if (!isset($_GET['edit']) && !isset($_GET['new']) && $_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: episodes.php'); exit; }
$id = (string)($_GET['edit'] ?? '');
$isNew = isset($_GET['new']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  dtm_check_csrf();
  $action = (string)($_POST['action'] ?? '');
  $pid = (string)($_POST['id'] ?? '');
  if ($action === 'delete' && $pid !== '') {
    $sc = dtm_script($pid);
    if ($sc && (string)$sc['episode'] !== '') {
      $error = 'This script belongs to episode ' . $sc['episode'] . '. Remove it from the episode first.';
      $id = $pid;
    } else {
      dtm_json_update('scripts.json', fn($l) => array_values(array_filter($l, fn($s) => $s['id'] !== $pid)));
      header('Location: ' . (in_array($sc['kind'] ?? 'episode', DTM_SIDE_KINDS, true) ? 'side.php' : 'episodes.php') . '?deleted=1'); exit;
    }
  }
  if ($action === 'make_episode' && $pid !== '') {
    $eid = dtm_make_episode_from_script($pid);
    if ($eid) { header('Location: episodes.php?edit=' . urlencode($eid) . '&made=1'); exit; }
    $error = 'That script is already on an episode.'; $id = $pid;
  }
  if ($action === 'update_seed' && $pid !== '') {
    $sc = dtm_script($pid); $key = $sc ? dtm_seed_key($sc) : '';
    $fresh = $key !== '' ? (string)@file_get_contents(DTM_ROOT . "/lib/seed/$key.txt") : '';
    if ($fresh !== '' && isset(DTM_SEEDS[$key])) {
      dtm_json_update('scripts.json', function ($l) use ($pid, $key, $fresh) {
        foreach ($l as &$x) if ($x['id'] === $pid) {
          $x['body'] = $fresh; $x['title'] = DTM_SEEDS[$key][1]; $x['subtitle'] = DTM_SEEDS[$key][2]; $x['updated'] = date('c');
        }
        return $l;
      });
      header('Location: scripts.php?edit=' . urlencode($pid) . '&updated=1'); exit;
    }
    $error = 'There is no newer version of this script.'; $id = $pid;
  }
  if ($action === 'save') {
    $title = trim((string)($_POST['title'] ?? ''));
    $subtitle = trim((string)($_POST['subtitle'] ?? ''));
    $status = (string)($_POST['status'] ?? 'draft');
    if (!isset(DTM_SCRIPT_STATUSES[$status])) $status = 'draft';
    $body = str_replace("\r\n", "\n", (string)($_POST['body'] ?? ''));
    $sources = trim(str_replace("\r\n", "\n", (string)($_POST['sources'] ?? '')));
    $kind = (string)($_POST['kind'] ?? 'episode'); if (!isset(DTM_SCRIPT_KINDS[$kind])) $kind = 'episode';
    $yt = trim((string)($_POST['youtube'] ?? ''));
    $pdate = trim((string)($_POST['date'] ?? ''));
    $sdate = trim((string)($_POST['story_date'] ?? ''));
    $onEp = $pid !== '' && ($cur = dtm_script($pid)) && (string)$cur['episode'] !== '';
    if ($onEp) $kind = 'episode'; // a numbered episode stays a main episode
    if ($title === '' || mb_strlen($title) > 120) $error = 'Give it a title (120 characters max).';
    elseif (mb_strlen($subtitle) > 160) $error = 'Subtitle is too long (160 characters max).';
    elseif (strlen($body) > 200000) $error = 'That script is huge. Trim it a bit.';
    elseif (strlen($sources) > 20000) $error = 'That sources list is huge. Trim it a bit.';
    elseif ($yt !== '' && !preg_match('#^https://#i', $yt)) $error = 'The YouTube link should start with https://';
    elseif ($pdate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $pdate)) $error = 'Date should look like 2026-12-04.';
    elseif ($sdate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sdate)) $error = 'The "happened on" date should look like 2026-11-07.';
    elseif ($kind === 'week' && $sdate === '') $error = 'Fresh Tracks stories need the date it happened, so you know when it gets stale.';
    else {
      $saved = $pid !== '' ? $pid : bin2hex(random_bytes(6));
      dtm_json_update('scripts.json', function ($l) use ($saved, $title, $subtitle, $status, $body, $sources, $kind, $yt, $pdate, $sdate) {
        $fields = ['title' => $title, 'subtitle' => $subtitle, 'status' => $status, 'body' => $body, 'sources' => $sources, 'kind' => $kind, 'youtube' => $yt, 'date' => $pdate, 'story_date' => $sdate, 'updated' => date('c')];
        foreach ($l as &$s) if ($s['id'] === $saved) { $s = array_merge($s, $fields); return $l; } // keeps episode + seed
        $l[] = ['id' => $saved, 'episode' => ''] + $fields; return $l;
      });
      header('Location: scripts.php?edit=' . urlencode($saved) . '&saved=1'); exit;
    }
    $id = $pid; $isNew = $pid === '';
  }
}

$scripts = dtm_scripts();
$current = $id !== '' ? dtm_script($id) : null;
if ($error && ($_POST['action'] ?? '') === 'save') {
  $current = ['id' => $id, 'episode' => $current['episode'] ?? '', 'title' => $_POST['title'] ?? '', 'subtitle' => $_POST['subtitle'] ?? '',
              'status' => $_POST['status'] ?? 'draft', 'body' => $_POST['body'] ?? '', 'sources' => $_POST['sources'] ?? '', 'seed' => $current['seed'] ?? '',
              'kind' => $_POST['kind'] ?? 'episode', 'youtube' => $_POST['youtube'] ?? '', 'date' => $_POST['date'] ?? '', 'story_date' => $_POST['story_date'] ?? ''];
}
$library = array_values(array_filter($scripts, fn($s) => (string)$s['episode'] === ''));
$onEps = array_values(array_filter($scripts, fn($s) => (string)$s['episode'] !== ''));
$epFor = [];
foreach (dtm_episodes() as $e) if ($e['script_id'] !== '') $epFor[$e['script_id']] = $e;
$badge = fn($st) => '<span class="status st-' . h($st) . '">' . h(DTM_SCRIPT_STATUSES[$st] ?? 'Draft') . '</span>';
dtm_page_start('Scripts', $user, true);
?>
<?php if ($current || $isNew): $newKind = (string)($_GET['kind'] ?? 'episode'); if (!isset(DTM_SCRIPT_KINDS[$newKind])) $newKind = 'episode';
  $c = ($current ?? []) + ['id' => '', 'episode' => '', 'title' => '', 'subtitle' => '', 'status' => 'draft', 'body' => '', 'sources' => '', 'kind' => $newKind, 'youtube' => '', 'date' => '', 'story_date' => ''];
  $isSide = $c['kind'] !== 'episode'; ?>
<section class="admin-card">
  <p class="small"><a href="<?= $isSide ? 'side.php' : 'episodes.php' ?>"><?= $isSide ? 'All side stories' : 'All episodes' ?></a><?php if ($c['id']): ?> · <a href="prompter.php?id=<?= h(urlencode($c['id'])) ?>">Prompter</a> · <a href="images.php?id=<?= h(urlencode($c['id'])) ?>">Images</a> · <a href="edit.php?script=<?= h(urlencode($c['id'])) ?>">Transcript</a><?php endif; ?></p>
  <h1 class="metal"><?= $c['id'] ? 'Edit script' : 'New ' . ($isSide ? h(DTM_SCRIPT_KINDS[$c['kind']]) : 'script') ?></h1>
  <?php if (isset($_GET['saved'])): ?><p class="notice ok bang" role="status">Saved.</p><?php endif; ?>
  <?php if (isset($_GET['updated'])): ?><p class="notice ok bang" role="status">Updated to the latest version.</p><?php endif; ?>
  <?php $seedKey = $c['id'] ? dtm_seed_key($c) : ''; $seedFile = $seedKey !== '' ? DTM_ROOT . "/lib/seed/$seedKey.txt" : '';
    if ($seedFile !== '' && is_file($seedFile) && !isset($_GET['saved']) && rtrim((string)file_get_contents($seedFile)) !== rtrim(str_replace("\r\n", "\n", (string)$c['body']))): ?>
  <form method="post" class="seed-update" onsubmit="return confirm('Replace this script with the latest version Claude wrote? Any changes you made to it here will be lost.')">
    <?= dtm_csrf_field() ?><input type="hidden" name="action" value="update_seed"><input type="hidden" name="id" value="<?= h($c['id']) ?>">
    <p class="notice ok bang">This copy is different from the latest version Claude shipped (newer edits, or your own changes).
      <button type="submit" class="pull-btn bang">Update to latest version</button></p>
  </form>
  <?php endif; ?>
  <?php if ($error): ?><p class="notice err bang" role="alert"><?= h($error) ?></p><?php endif; ?>
  <?php if ((string)$c['episode'] !== ''): $e = $epFor[$c['id']] ?? null; ?>
    <p class="notice ok bang">This is Episode <?= h($c['episode']) ?><?php if ($e): ?> · <a href="episodes.php?edit=<?= h(urlencode($e['id'])) ?>">open the episode</a><?php endif; ?></p>
  <?php endif; ?>
  <form method="post" class="admin-form">
    <?= dtm_csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= h($c['id']) ?>">
    <label for="kind" class="bang">Type</label>
    <select id="kind" name="kind" class="admin-select"<?= (string)$c['episode'] !== '' ? ' disabled' : '' ?>>
      <?php foreach (DTM_SCRIPT_KINDS as $k => $label): ?><option value="<?= h($k) ?>"<?= $c['kind'] === $k ? ' selected' : '' ?>><?= h($label) ?><?= $k === 'episode' ? ' (goes in Episodes)' : ' (Side Stories)' ?></option><?php endforeach; ?>
    </select>
    <?php if ((string)$c['episode'] !== ''): ?><input type="hidden" name="kind" value="episode"><?php endif; ?>
    <label for="title" class="bang">Title</label>
    <input id="title" name="title" type="text" required maxlength="120" placeholder="<?= $c['kind'] === 'otr' ? 'Off The Record: ...' : 'Due To ...' ?>" value="<?= h($c['title']) ?>">
    <label for="subtitle" class="bang">Subtitle</label>
    <input id="subtitle" name="subtitle" type="text" maxlength="160" placeholder="Who ...?" value="<?= h($c['subtitle']) ?>">
    <label for="status" class="bang">Status</label>
    <select id="status" name="status" class="admin-select">
      <?php foreach (DTM_SCRIPT_STATUSES as $k => $label): ?><option value="<?= h($k) ?>"<?= $c['status'] === $k ? ' selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?>
    </select>
    <?php if ((string)$c['episode'] === ''): ?>
    <label for="youtube" class="bang">YouTube link (once it's out)</label>
    <input id="youtube" name="youtube" type="url" placeholder="https://youtu.be/..." value="<?= h($c['youtube']) ?>">
    <label for="hdate" class="bang">Happened on<?= $c['kind'] === 'week' ? ' (needed: news goes stale fast)' : ' (optional)' ?></label>
    <input id="hdate" name="story_date" type="date" value="<?= h($c['story_date']) ?>">
    <label for="sdate" class="bang">Publish date</label>
    <input id="sdate" name="date" type="date" value="<?= h($c['date']) ?>">
    <?php else: ?><input type="hidden" name="youtube" value="<?= h($c['youtube']) ?>"><input type="hidden" name="date" value="<?= h($c['date']) ?>"><input type="hidden" name="story_date" value="<?= h($c['story_date']) ?>"><?php endif; ?>
    <label for="body" class="bang">Script</label>
    <p class="small">Start a line with <code>## </code> for a section heading. Cues in brackets, like <code>[RIFF: ...]</code> or <code>[THEME SONG]</code>, show up in yellow.</p>
    <textarea id="body" name="body" rows="22" class="script-box"><?= h($c['body']) ?></textarea>
    <label for="sources" class="bang">Extra sources</label>
    <p class="small">For the public Sources &amp; Credits page. One per line: <code>Title | https://link</code>.<?php $shipped = count(dtm_lib_json('sources', dtm_seed_key($c))['sources'] ?? []); if ($shipped): ?> This script already ships with <?= $shipped ?> sources, so only add new ones.<?php endif; ?></p>
    <textarea id="sources" name="sources" rows="4" class="script-box" placeholder="Rolling Stone: the night it all went wrong | https://..."><?= h($c['sources']) ?></textarea>
    <div class="btn-row">
      <button type="submit" class="pull-btn bang">Save</button>
      <?php if ($c['id']): ?><a class="pull-btn bang link-btn" href="prompter.php?id=<?= h(urlencode($c['id'])) ?>">Open teleprompter</a>
      <a class="pull-btn bang link-btn" href="images.php?id=<?= h(urlencode($c['id'])) ?>">Images</a><?php endif; ?>
    </div>
  </form>
  <?php if ($c['id']): ?>
  <h2 class="bang saved-h">Sources &amp; Credits page</h2>
  <?php if ((string)$c['episode'] !== ''): ?>
    <p>Public page: <a href="<?= h(dtm_episode_page_path((string)$c['episode'])) ?>" target="_blank" rel="noopener"><?= h(dtm_episode_page_url((string)$c['episode'])) ?></a>. Paste this line in the YouTube and Spotify descriptions:</p>
    <?= dtm_copy_line(dtm_description_line((string)$c['episode'])) ?>
  <?php else: ?>
    <p>Goes public at <code>defacingthemusic.com/ep/N</code> once this script is an episode. <a href="/ep/?preview=<?= h(urlencode($c['id'])) ?>" target="_blank" rel="noopener">Preview it</a>.</p>
  <?php endif; ?>
  <?php endif; ?>
  <?php if ($c['id'] && (string)$c['episode'] === '' && !$isSide): ?>
  <form method="post" onsubmit="return confirm('Make this Episode <?= h(dtm_next_episode_number()) ?>?')">
    <?= dtm_csrf_field() ?><input type="hidden" name="action" value="make_episode"><input type="hidden" name="id" value="<?= h($c['id']) ?>">
    <button type="submit" class="pull-btn bang">Make it Episode <?= h(dtm_next_episode_number()) ?></button>
  </form>
  <?php endif; ?>
  <?php if ($c['id'] && (string)$c['episode'] === ''): ?>
  <form method="post" onsubmit="return confirm('Delete this script for good?')">
    <?= dtm_csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= h($c['id']) ?>">
    <button type="submit" class="signout-btn bang">Delete script</button>
  </form>
  <?php endif; ?>
</section>
<?php else: ?>
<section class="admin-card">
  <h1 class="metal">Scripts</h1>
  <?php if (isset($_GET['deleted'])): ?><p class="notice ok bang" role="status">Deleted.</p><?php endif; ?>
  <p>Your script library. No numbers until you pick one: open a script and hit <strong>Make it an episode</strong>. <strong>Read</strong> opens the teleprompter. <strong>Images</strong> has cutaway pictures you can use.</p>
  <p><a class="pull-btn bang link-btn" href="scripts.php?new=1">New script</a></p>
  <h2 class="bang saved-h">Library</h2>
  <?php if (!$library): ?><p>Empty. Every script is on an episode.</p><?php endif; ?>
  <ul class="script-list">
    <?php foreach ($library as $s): ?>
    <li>
      <?= $badge($s['status']) ?>
      <span class="ttl"><?= h($s['title']) ?><?php if (!empty($s['subtitle'])): ?><em class="sub"><?= h($s['subtitle']) ?></em><?php endif; ?></span>
      <span class="acts"><a class="pull-btn bang link-btn" href="prompter.php?id=<?= h(urlencode($s['id'])) ?>">Read</a> <a class="pull-btn bang link-btn" href="images.php?id=<?= h(urlencode($s['id'])) ?>">Images</a> <a href="scripts.php?edit=<?= h(urlencode($s['id'])) ?>">Edit</a></span>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php if ($onEps): ?>
  <h2 class="bang saved-h">On episodes</h2>
  <ul class="script-list">
    <?php foreach ($onEps as $s): ?>
    <li>
      <span class="ep bang">EP <?= h(str_pad((string)$s['episode'], 2, '0', STR_PAD_LEFT)) ?></span>
      <span class="ttl"><?= h($s['title']) ?><?php if (!empty($s['subtitle'])): ?><em class="sub"><?= h($s['subtitle']) ?></em><?php endif; ?></span>
      <span class="acts"><a class="pull-btn bang link-btn" href="prompter.php?id=<?= h(urlencode($s['id'])) ?>">Read</a> <a class="pull-btn bang link-btn" href="images.php?id=<?= h(urlencode($s['id'])) ?>">Images</a> <a href="scripts.php?edit=<?= h(urlencode($s['id'])) ?>">Edit</a></span>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</section>
<?php endif; ?>
<?php dtm_page_end();
