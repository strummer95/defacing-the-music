<?php
// Scripts: list, create, edit, delete. Reader view lives in prompter.php.
require __DIR__ . '/../lib/scripts.php';
$user = dtm_require_admin();
$id = (string)($_GET['edit'] ?? '');
$isNew = isset($_GET['new']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  dtm_check_csrf();
  $action = (string)($_POST['action'] ?? '');
  $pid = (string)($_POST['id'] ?? '');
  if ($action === 'delete' && $pid !== '') {
    dtm_json_update('scripts.json', fn($l) => array_values(array_filter($l, fn($s) => $s['id'] !== $pid)));
    header('Location: scripts.php?deleted=1'); exit;
  }
  if ($action === 'save') {
    $ep = trim((string)($_POST['episode'] ?? ''));
    $title = trim((string)($_POST['title'] ?? ''));
    $body = str_replace("\r\n", "\n", (string)($_POST['body'] ?? ''));
    if ($title === '' || mb_strlen($title) > 120) $error = 'Give it a title (120 characters max).';
    elseif (mb_strlen($ep) > 10) $error = 'Episode number is too long.';
    elseif (strlen($body) > 200000) $error = 'That script is huge. Trim it a bit.';
    else {
      $saved = $pid !== '' ? $pid : bin2hex(random_bytes(6));
      dtm_json_update('scripts.json', function ($l) use ($saved, $ep, $title, $body) {
        $row = ['id' => $saved, 'episode' => $ep, 'title' => $title, 'body' => $body, 'updated' => date('c')];
        foreach ($l as &$s) if ($s['id'] === $saved) { $s = $row; return $l; }
        $l[] = $row; return $l;
      });
      header('Location: scripts.php?edit=' . urlencode($saved) . '&saved=1'); exit;
    }
    $id = $pid; $isNew = $pid === '';
  }
}

$scripts = dtm_scripts();
$current = $id !== '' ? dtm_script($id) : null;
if ($error) $current = ['id' => $id, 'episode' => $_POST['episode'] ?? '', 'title' => $_POST['title'] ?? '', 'body' => $_POST['body'] ?? ''];
dtm_page_start('Scripts', $user, true);
?>
<?php if ($current || $isNew): $c = $current ?? ['id' => '', 'episode' => '', 'title' => '', 'body' => '']; ?>
<section class="admin-card">
  <p class="small"><a href="scripts.php">All scripts</a></p>
  <h1 class="metal"><?= $c['id'] ? 'Edit script' : 'New script' ?></h1>
  <?php if (isset($_GET['saved'])): ?><p class="notice ok bang" role="status">Saved.</p><?php endif; ?>
  <?php if ($error): ?><p class="notice err bang" role="alert"><?= h($error) ?></p><?php endif; ?>
  <form method="post" class="admin-form">
    <?= dtm_csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= h($c['id']) ?>">
    <label for="episode" class="bang">Episode #</label>
    <input id="episode" name="episode" type="text" inputmode="numeric" maxlength="10" value="<?= h($c['episode']) ?>">
    <label for="title" class="bang">Title</label>
    <input id="title" name="title" type="text" required maxlength="120" value="<?= h($c['title']) ?>">
    <label for="body" class="bang">Script</label>
    <p class="small">Start a line with <code>## </code> for a section heading. Cues in brackets, like <code>[RIFF: ...]</code> or <code>[THEME SONG]</code>, show up in yellow.</p>
    <textarea id="body" name="body" rows="22" class="script-box"><?= h($c['body']) ?></textarea>
    <div class="btn-row">
      <button type="submit" class="pull-btn bang">Save</button>
      <?php if ($c['id']): ?><a class="pull-btn bang link-btn" href="prompter.php?id=<?= h(urlencode($c['id'])) ?>">Open teleprompter</a><?php endif; ?>
    </div>
  </form>
  <?php if ($c['id']): ?>
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
  <p>Tap <strong>Read</strong> to open the teleprompter: black screen, big text, auto-scroll.</p>
  <?php if (!$scripts): ?><p>No scripts yet.</p><?php endif; ?>
  <ul class="script-list">
    <?php foreach ($scripts as $s): ?>
    <li>
      <span class="ep bang">EP <?= h($s['episode'] === '' ? '?' : str_pad($s['episode'], 2, '0', STR_PAD_LEFT)) ?></span>
      <span class="ttl"><?= h($s['title']) ?></span>
      <span class="acts"><a class="pull-btn bang link-btn" href="prompter.php?id=<?= h(urlencode($s['id'])) ?>">Read</a> <a href="scripts.php?edit=<?= h(urlencode($s['id'])) ?>">Edit</a></span>
    </li>
    <?php endforeach; ?>
  </ul>
  <p><a class="pull-btn bang link-btn" href="scripts.php?new=1">New script</a></p>
</section>
<?php endif; ?>
<?php dtm_page_end();
