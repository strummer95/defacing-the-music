<?php
// Scripts library: unnumbered scripts with a status. "Make it an episode" gives one the next episode number.
// Reader view lives in prompter.php; numbered episodes live in episodes.php.
require __DIR__ . '/../lib/episodes.php';
$user = dtm_require_admin();
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
      header('Location: scripts.php?deleted=1'); exit;
    }
  }
  if ($action === 'make_episode' && $pid !== '') {
    $eid = dtm_make_episode_from_script($pid);
    if ($eid) { header('Location: episodes.php?edit=' . urlencode($eid) . '&made=1'); exit; }
    $error = 'That script is already on an episode.'; $id = $pid;
  }
  if ($action === 'save') {
    $title = trim((string)($_POST['title'] ?? ''));
    $subtitle = trim((string)($_POST['subtitle'] ?? ''));
    $status = (string)($_POST['status'] ?? 'draft');
    if (!isset(DTM_SCRIPT_STATUSES[$status])) $status = 'draft';
    $body = str_replace("\r\n", "\n", (string)($_POST['body'] ?? ''));
    if ($title === '' || mb_strlen($title) > 120) $error = 'Give it a title (120 characters max).';
    elseif (mb_strlen($subtitle) > 160) $error = 'Subtitle is too long (160 characters max).';
    elseif (strlen($body) > 200000) $error = 'That script is huge. Trim it a bit.';
    else {
      $saved = $pid !== '' ? $pid : bin2hex(random_bytes(6));
      dtm_json_update('scripts.json', function ($l) use ($saved, $title, $subtitle, $status, $body) {
        $fields = ['title' => $title, 'subtitle' => $subtitle, 'status' => $status, 'body' => $body, 'updated' => date('c')];
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
              'status' => $_POST['status'] ?? 'draft', 'body' => $_POST['body'] ?? ''];
}
$library = array_values(array_filter($scripts, fn($s) => (string)$s['episode'] === ''));
$onEps = array_values(array_filter($scripts, fn($s) => (string)$s['episode'] !== ''));
$epFor = [];
foreach (dtm_episodes() as $e) if ($e['script_id'] !== '') $epFor[$e['script_id']] = $e;
$badge = fn($st) => '<span class="status st-' . h($st) . '">' . h(DTM_SCRIPT_STATUSES[$st] ?? 'Draft') . '</span>';
dtm_page_start('Scripts', $user, true);
?>
<?php if ($current || $isNew): $c = ($current ?? []) + ['id' => '', 'episode' => '', 'title' => '', 'subtitle' => '', 'status' => 'draft', 'body' => '']; ?>
<section class="admin-card">
  <p class="small"><a href="scripts.php">All scripts</a></p>
  <h1 class="metal"><?= $c['id'] ? 'Edit script' : 'New script' ?></h1>
  <?php if (isset($_GET['saved'])): ?><p class="notice ok bang" role="status">Saved.</p><?php endif; ?>
  <?php if ($error): ?><p class="notice err bang" role="alert"><?= h($error) ?></p><?php endif; ?>
  <?php if ((string)$c['episode'] !== ''): $e = $epFor[$c['id']] ?? null; ?>
    <p class="notice ok bang">This is Episode <?= h($c['episode']) ?><?php if ($e): ?> · <a href="episodes.php?edit=<?= h(urlencode($e['id'])) ?>">open the episode</a><?php endif; ?></p>
  <?php endif; ?>
  <form method="post" class="admin-form">
    <?= dtm_csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= h($c['id']) ?>">
    <label for="title" class="bang">Title</label>
    <input id="title" name="title" type="text" required maxlength="120" placeholder="Due To ..." value="<?= h($c['title']) ?>">
    <label for="subtitle" class="bang">Subtitle</label>
    <input id="subtitle" name="subtitle" type="text" maxlength="160" placeholder="Who ...?" value="<?= h($c['subtitle']) ?>">
    <label for="status" class="bang">Status</label>
    <select id="status" name="status" class="admin-select">
      <?php foreach (DTM_SCRIPT_STATUSES as $k => $label): ?><option value="<?= h($k) ?>"<?= $c['status'] === $k ? ' selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?>
    </select>
    <label for="body" class="bang">Script</label>
    <p class="small">Start a line with <code>## </code> for a section heading. Cues in brackets, like <code>[RIFF: ...]</code> or <code>[THEME SONG]</code>, show up in yellow.</p>
    <textarea id="body" name="body" rows="22" class="script-box"><?= h($c['body']) ?></textarea>
    <div class="btn-row">
      <button type="submit" class="pull-btn bang">Save</button>
      <?php if ($c['id']): ?><a class="pull-btn bang link-btn" href="prompter.php?id=<?= h(urlencode($c['id'])) ?>">Open teleprompter</a><?php endif; ?>
    </div>
  </form>
  <?php if ($c['id'] && (string)$c['episode'] === ''): ?>
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
  <p>Your script library. No numbers until you pick one: open a script and hit <strong>Make it an episode</strong>. <strong>Read</strong> opens the teleprompter.</p>
  <p><a class="pull-btn bang link-btn" href="scripts.php?new=1">New script</a></p>
  <h2 class="bang saved-h">Library</h2>
  <?php if (!$library): ?><p>Empty. Every script is on an episode.</p><?php endif; ?>
  <ul class="script-list">
    <?php foreach ($library as $s): ?>
    <li>
      <?= $badge($s['status']) ?>
      <span class="ttl"><?= h($s['title']) ?><?php if (!empty($s['subtitle'])): ?><em class="sub"><?= h($s['subtitle']) ?></em><?php endif; ?></span>
      <span class="acts"><a class="pull-btn bang link-btn" href="prompter.php?id=<?= h(urlencode($s['id'])) ?>">Read</a> <a href="scripts.php?edit=<?= h(urlencode($s['id'])) ?>">Edit</a></span>
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
      <span class="acts"><a class="pull-btn bang link-btn" href="prompter.php?id=<?= h(urlencode($s['id'])) ?>">Read</a> <a href="scripts.php?edit=<?= h(urlencode($s['id'])) ?>">Edit</a></span>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</section>
<?php endif; ?>
<?php dtm_page_end();
