<?php
require __DIR__ . '/../lib/episodes.php';
$user = dtm_require_admin();
$error = ''; $msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  dtm_check_csrf();
  $action = (string)($_POST['action'] ?? '');
  if ($action === 'delete') {
    dtm_delete_episode((string)($_POST['id'] ?? ''));
    header('Location: episodes.php'); exit;
  } elseif ($action === 'save') {
    $id = ($_POST['id'] ?? '') !== '' ? (string)$_POST['id'] : null;
    [$ok, $err, $saved] = dtm_save_episode($_POST, $id);
    if ($ok) { header('Location: episodes.php?saved=' . urlencode($saved['slug'])); exit; }
    $error = $err;
  }
}

$editId = (string)($_GET['edit'] ?? '');
$editing = $editId !== '' ? dtm_episode_by_id($editId) : null;
if ($editId !== '' && !$editing) { $error = $error ?: 'That episode no longer exists.'; }
if (($_GET['saved'] ?? '') !== '') $msg = 'Episode saved.';

$f = fn($k, $d = '') => h($editing[$k] ?? $d);
dtm_page_start('Episodes', $user, true);
?>
<section class="admin-card">
  <h1 class="metal"><?= $editing ? 'Edit episode' : 'Add episode' ?></h1>
  <?php if ($error): ?><p class="notice err bang" role="alert"><?= h($error) ?></p><?php endif; ?>
  <?php if ($msg): ?><p class="notice ok bang" role="status"><?= h($msg) ?></p><?php endif; ?>
  <form method="post" class="admin-form" enctype="multipart/form-data">
    <?= dtm_csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= $editing ? h($editing['id']) : '' ?>">
    <label for="number" class="bang">Number</label>
    <input id="number" name="number" type="text" required maxlength="10" placeholder="01" value="<?= $f('number') ?>">
    <label for="title" class="bang">Title</label>
    <input id="title" name="title" type="text" required maxlength="200" placeholder="Due To ..." value="<?= $f('title') ?>">
    <label for="slug" class="bang">Slug (optional, auto from title)</label>
    <input id="slug" name="slug" type="text" maxlength="80" placeholder="due-to-..." value="<?= $f('slug') ?>">
    <label for="date" class="bang">Date</label>
    <input id="date" name="date" type="date" required value="<?= $f('date') ?>">
    <label for="subject" class="bang">Story subject (band/artist)</label>
    <input id="subject" name="subject" type="text" maxlength="120" placeholder="Keith Moon" value="<?= $f('subject') ?>">
    <label for="summary" class="bang">Summary</label>
    <textarea id="summary" name="summary" required maxlength="600" rows="4" placeholder="So wild it sounds fake, but it's real."><?= $f('summary') ?></textarea>
    <label for="embed" class="bang">Embed URL (Spotify/YouTube/Apple, optional)</label>
    <input id="embed" name="embed" type="url" maxlength="500" placeholder="https://..." value="<?= $f('embed') ?>">
    <label for="cover" class="bang">Cover image (JPEG/PNG/WEBP, under 5MB, optional)</label>
    <?php if (!empty($editing['cover'])): ?>
      <img src="../<?= h($editing['cover']) ?>" alt="" class="cover-preview">
      <label class="checkbox"><input type="checkbox" name="remove_cover" value="1"> Remove current cover</label>
    <?php endif; ?>
    <input id="cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp">
    <button type="submit" class="pull-btn bang"><?= $editing ? 'Save changes' : 'Add episode' ?></button>
  </form>
  <?php if ($editing): ?><p class="small"><a href="episodes.php">Cancel, add a new one instead</a></p><?php endif; ?>
</section>

<section class="admin-card">
  <h2 class="bang">All episodes</h2>
  <?php $all = dtm_episodes(); ?>
  <?php if (!$all): ?>
    <p>No episodes yet.</p>
  <?php else: ?>
  <ul class="episode-list">
    <?php foreach ($all as $e): ?>
    <li class="episode-row">
      <span class="episode-row-num bang">EP <?= h($e['number']) ?></span>
      <span class="episode-row-title"><?= h($e['title']) ?></span>
      <span class="episode-row-actions">
        <a href="episodes.php?edit=<?= urlencode($e['id']) ?>">Edit</a>
        <a href="../episode.php?slug=<?= urlencode($e['slug']) ?>" target="_blank" rel="noopener">View</a>
        <form method="post" onsubmit="return confirm('Delete this episode?')">
          <?= dtm_csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= h($e['id']) ?>">
          <button type="submit" class="link-btn">Delete</button>
        </form>
      </span>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</section>
<?php dtm_page_end();
