<?php
// Episodes: numbered list with status, publish date, links, and the script behind each one.
require __DIR__ . '/../lib/episodes.php';
$user = dtm_require_admin();
$id = (string)($_GET['edit'] ?? '');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  dtm_check_csrf();
  $action = (string)($_POST['action'] ?? '');
  $pid = (string)($_POST['id'] ?? '');
  if ($action === 'delete' && $pid !== '') {
    dtm_delete_episode($pid);
    header('Location: episodes.php?deleted=1'); exit;
  }
  if ($action === 'make' && ($sid = (string)($_POST['script_id'] ?? '')) !== '') {
    $eid = dtm_make_episode_from_script($sid);
    if ($eid) { header('Location: episodes.php?edit=' . urlencode($eid) . '&made=1'); exit; }
    $error = 'Pick a script from the library.';
  }
  if ($action === 'save' && $pid !== '') {
    [$ok, $err] = dtm_save_episode($_POST, $pid);
    if ($ok) { header('Location: episodes.php?edit=' . urlencode($pid) . '&saved=1'); exit; }
    $error = $err; $id = $pid;
  }
}

$episodes = dtm_episodes();
$scripts = dtm_scripts();
$byId = [];
foreach ($scripts as $s) $byId[$s['id']] = $s;
$library = array_values(array_filter($scripts, fn($s) => (string)$s['episode'] === ''));
$current = $id !== '' ? dtm_episode($id) : null;
if ($current && $error) $current = array_merge($current, array_intersect_key($_POST, $current));
$badge = fn($st) => '<span class="status st-' . h($st) . '">' . h(DTM_EPISODE_STATUSES[$st] ?? 'Planned') . '</span>';
dtm_page_start('Episodes', $user, true);
?>
<?php if ($current): $c = $current; ?>
<section class="admin-card">
  <p class="small"><a href="episodes.php">All episodes</a></p>
  <h1 class="metal">Episode <?= h($c['number']) ?></h1>
  <?php if (isset($_GET['made'])): ?><p class="notice ok bang" role="status">It's an episode now. Fill in the rest when it's recorded and out.</p><?php endif; ?>
  <?php if (isset($_GET['saved'])): ?><p class="notice ok bang" role="status">Saved.</p><?php endif; ?>
  <?php if ($error): ?><p class="notice err bang" role="alert"><?= h($error) ?></p><?php endif; ?>
  <form method="post" class="admin-form">
    <?= dtm_csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= h($c['id']) ?>">
    <label for="number" class="bang">Episode #</label>
    <input id="number" name="number" type="text" inputmode="numeric" required maxlength="10" value="<?= h($c['number']) ?>">
    <label for="title" class="bang">Title</label>
    <input id="title" name="title" type="text" required maxlength="160" value="<?= h($c['title']) ?>">
    <label for="subtitle" class="bang">Subtitle</label>
    <input id="subtitle" name="subtitle" type="text" maxlength="160" value="<?= h($c['subtitle']) ?>">
    <label for="status" class="bang">Status</label>
    <select id="status" name="status" class="admin-select">
      <?php foreach (DTM_EPISODE_STATUSES as $k => $label): ?><option value="<?= h($k) ?>"<?= $c['status'] === $k ? ' selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?>
    </select>
    <label for="date" class="bang">Publish date</label>
    <input id="date" name="date" type="date" value="<?= h($c['date']) ?>">
    <label for="youtube" class="bang">YouTube link</label>
    <input id="youtube" name="youtube" type="url" placeholder="https://youtu.be/..." value="<?= h($c['youtube']) ?>">
    <label for="spotify" class="bang">Spotify link</label>
    <input id="spotify" name="spotify" type="url" placeholder="https://open.spotify.com/episode/..." value="<?= h($c['spotify']) ?>">
    <label for="script_id" class="bang">Script</label>
    <select id="script_id" name="script_id" class="admin-select">
      <option value="">(none)</option>
      <?php foreach ($scripts as $s): if ((string)$s['episode'] !== '' && $s['id'] !== $c['script_id']) continue; ?>
        <option value="<?= h($s['id']) ?>"<?= $s['id'] === $c['script_id'] ? ' selected' : '' ?>><?= h($s['title']) ?></option>
      <?php endforeach; ?>
    </select>
    <label for="notes" class="bang">Notes</label>
    <textarea id="notes" name="notes" rows="4" class="script-box" placeholder="Recording notes, shirt/light used, clip ideas..."><?= h($c['notes']) ?></textarea>
    <div class="btn-row">
      <button type="submit" class="pull-btn bang">Save</button>
      <?php if ($c['script_id'] !== '' && isset($byId[$c['script_id']])): ?>
        <a class="pull-btn bang link-btn" href="prompter.php?id=<?= h(urlencode($c['script_id'])) ?>">Read script</a>
      <?php endif; ?>
    </div>
  </form>
  <form method="post" onsubmit="return confirm('Remove Episode <?= h($c['number']) ?>? Its script goes back to the library.')">
    <?= dtm_csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= h($c['id']) ?>">
    <button type="submit" class="signout-btn bang">Remove episode</button>
  </form>
</section>
<?php else: ?>
<section class="admin-card">
  <h1 class="metal">Episodes</h1>
  <?php if (isset($_GET['deleted'])): ?><p class="notice ok bang" role="status">Removed. Its script is back in the library.</p><?php endif; ?>
  <?php if ($error): ?><p class="notice err bang" role="alert"><?= h($error) ?></p><?php endif; ?>
  <?php if (!$episodes): ?><p>No episodes yet.</p><?php endif; ?>
  <ul class="script-list">
    <?php foreach ($episodes as $e): $s = $byId[$e['script_id']] ?? null; ?>
    <li>
      <span class="ep bang">EP <?= h(str_pad((string)$e['number'], 2, '0', STR_PAD_LEFT)) ?></span>
      <span class="ttl"><?= h($e['title']) ?><?php if ($e['subtitle'] !== ''): ?><em class="sub"><?= h($e['subtitle']) ?></em><?php endif; ?>
        <em class="meta"><?= $badge($e['status']) ?><?= $e['date'] !== '' ? ' ' . h(date('M j, Y', strtotime($e['date']))) : '' ?></em></span>
      <span class="acts">
        <?php if ($s): ?><a class="pull-btn bang link-btn" href="prompter.php?id=<?= h(urlencode($s['id'])) ?>">Read</a><?php endif; ?>
        <?php if ($e['youtube'] !== ''): ?><a href="<?= h($e['youtube']) ?>" target="_blank" rel="noopener">YouTube</a><?php endif; ?>
        <a href="episodes.php?edit=<?= h(urlencode($e['id'])) ?>">Edit</a>
      </span>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php if ($library): ?>
  <form method="post" class="admin-form make-ep">
    <?= dtm_csrf_field() ?><input type="hidden" name="action" value="make">
    <label for="script_id" class="bang">Next up: Episode <?= h(dtm_next_episode_number()) ?></label>
    <div class="btn-row">
      <select id="script_id" name="script_id" class="admin-select" required>
        <option value="">Pick a script from the library...</option>
        <?php foreach ($library as $s): ?><option value="<?= h($s['id']) ?>"><?= h($s['title']) ?> (<?= h(DTM_SCRIPT_STATUSES[$s['status']] ?? 'Draft') ?>)</option><?php endforeach; ?>
      </select>
      <button type="submit" class="pull-btn bang">Make it an episode</button>
    </div>
  </form>
  <?php endif; ?>
</section>
<?php endif; ?>
<?php dtm_page_end();
