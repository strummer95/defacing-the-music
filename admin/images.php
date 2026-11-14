<?php
// Images for one script: cutaway pictures Duck can drop in over a cut. Pictures only here;
// who took each one and its license live on the public Sources & Credits page (/ep/N).
require __DIR__ . '/../lib/cutaways.php';
$user = dtm_require_admin();
$id = (string)($_GET['id'] ?? $_POST['id'] ?? '');
$script = $id !== '' ? dtm_script($id) : null;
if (!$script || dtm_safe_id($script['id']) === '') { http_response_code(404); dtm_page_start('Images', $user); echo '<section class="admin-card"><h1 class="metal">Images</h1><p>No such script. <a href="scripts.php">Back to scripts</a></p></section>'; dtm_page_end(); exit; }
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  dtm_check_csrf();
  $action = (string)($_POST['action'] ?? '');
  $file = basename((string)($_POST['file'] ?? ''));
  if ($action === 'hide' || $action === 'restore') {
    dtm_cut_state_update($script['id'], function ($st) use ($action, $file) {
      $st['hidden'] = array_values(array_diff($st['hidden'], [$file]));
      if ($action === 'hide') $st['hidden'][] = $file;
      return $st;
    });
    header('Location: images.php?id=' . urlencode($script['id']) . ($action === 'hide' ? '&hidden=1' : '')); exit;
  }
  if ($action === 'remove' && $file !== '') {
    $path = dtm_uploads_dir() . '/cutaways/own/' . $script['id'] . '/' . $file;
    dtm_cut_state_update($script['id'], function ($st) use ($file) {
      $st['added'] = array_values(array_filter($st['added'], fn($im) => ($im['file'] ?? '') !== $file));
      return $st;
    });
    if (is_file($path)) @unlink($path);
    header('Location: images.php?id=' . urlencode($script['id']) . '&removed=1'); exit;
  }
  if ($action === 'add') {
    $up = $_FILES['img'] ?? null;
    $ext = strtolower(pathinfo((string)($up['name'] ?? ''), PATHINFO_EXTENSION));
    $title = trim((string)($_POST['title'] ?? ''));
    $author = trim((string)($_POST['author'] ?? ''));
    $license = trim((string)($_POST['license'] ?? ''));
    $source = trim((string)($_POST['source'] ?? ''));
    if (!$up || ($up['error'] ?? 1) !== UPLOAD_ERR_OK) $error = ($up['error'] ?? 0) === UPLOAD_ERR_INI_SIZE || ($up['error'] ?? 0) === UPLOAD_ERR_FORM_SIZE ? 'That picture is too big for this server. Shrink it and try again.' : 'Pick a picture to upload.';
    elseif (!isset(DTM_CUT_TYPES[$ext]) || !@getimagesize($up['tmp_name'])) $error = 'That doesn\'t look like a picture (JPG, PNG, WebP or GIF).';
    elseif ($up['size'] > DTM_CUT_MAX_BYTES) $error = 'That picture is over 30 MB.';
    elseif ($source !== '' && !dtm_safe_url($source)) $error = 'The link has to start with https://';
    elseif (mb_strlen($title . $author . $license) > 600) $error = 'Keep the credit fields short.';
    else {
      $dir = dtm_uploads_dir() . '/cutaways/own/' . $script['id'];
      if (!is_dir($dir)) @mkdir($dir, 0755, true);
      $name = date('Ymd-His') . '-' . bin2hex(random_bytes(2)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
      [$w, $h] = getimagesize($up['tmp_name']);
      if (!@move_uploaded_file($up['tmp_name'], "$dir/$name")) $error = 'Couldn\'t save that picture. Try again.';
      else {
        $row = ['file' => $name, 'title' => $title !== '' ? $title : pathinfo((string)$up['name'], PATHINFO_FILENAME),
                'author' => $author, 'license' => $license, 'license_url' => '', 'source' => $source, 'width' => $w, 'height' => $h, 'added' => date('c')];
        dtm_cut_state_update($script['id'], function ($st) use ($row) { $st['added'][] = $row; return $st; });
        header('Location: images.php?id=' . urlencode($script['id']) . '&added=1'); exit;
      }
    }
  }
}

$all = dtm_cutaways($script, true);
$shown = array_values(array_filter($all, fn($im) => !$im['hidden']));
$hidden = array_values(array_filter($all, fn($im) => $im['hidden']));
$src = fn($im, $dl = false) => 'cutaway.php?s=' . urlencode($script['id']) . '&k=' . urlencode($im['key']) . ($dl ? '&dl=1' : '');
$ep = (string)$script['episode'];
dtm_page_start('Images', $user, true);
?>
<section class="admin-card">
  <p class="small"><a href="scripts.php">All scripts</a> · <a href="prompter.php?id=<?= h(urlencode($script['id'])) ?>">Read</a> · <a href="scripts.php?edit=<?= h(urlencode($script['id'])) ?>">Edit</a></p>
  <h1 class="metal">Images</h1>
  <p class="cut-for bang"><?= $ep !== '' ? 'EP ' . h(str_pad($ep, 2, '0', STR_PAD_LEFT)) . ' · ' : '' ?><?= h($script['title']) ?></p>
  <?php if (isset($_GET['added'])): ?><p class="notice ok bang" role="status">Added.</p><?php endif; ?>
  <?php if (isset($_GET['hidden'])): ?><p class="notice ok bang" role="status">Hidden. It's in the hidden pile at the bottom if you want it back.</p><?php endif; ?>
  <?php if (isset($_GET['removed'])): ?><p class="notice ok bang" role="status">Removed.</p><?php endif; ?>
  <?php if ($error): ?><p class="notice err bang" role="alert"><?= h($error) ?></p><?php endif; ?>
  <?php if (!$shown): ?><p>No pictures for this one yet. Add your own below.</p><?php endif; ?>
  <div class="cut-grid">
    <?php foreach ($shown as $im): ?>
    <figure class="cut">
      <a href="<?= h($src($im)) ?>" target="_blank" rel="noopener"><img src="<?= h($src($im)) ?>" alt="<?= h($im['title']) ?>" loading="lazy"<?= !empty($im['width']) ? ' width="' . (int)$im['width'] . '" height="' . (int)$im['height'] . '"' : '' ?>></a>
      <figcaption>
        <span class="cut-ttl"><?= h($im['title']) ?></span>
        <span class="cut-acts">
          <a class="pull-btn bang link-btn" href="<?= h($src($im)) ?>" target="_blank" rel="noopener">Full size</a>
          <a class="pull-btn bang link-btn" href="<?= h($src($im, true)) ?>">Download</a>
          <form method="post" <?= $im['kind'] === 'own' ? 'onsubmit="return confirm(\'Delete this picture for good?\')"' : '' ?>>
            <?= dtm_csrf_field() ?><input type="hidden" name="id" value="<?= h($script['id']) ?>"><input type="hidden" name="file" value="<?= h($im['file']) ?>">
            <input type="hidden" name="action" value="<?= $im['kind'] === 'own' ? 'remove' : 'hide' ?>">
            <button type="submit" class="cut-x"><?= $im['kind'] === 'own' ? 'Delete' : 'Hide' ?></button>
          </form>
        </span>
      </figcaption>
    </figure>
    <?php endforeach; ?>
  </div>
  <p class="small">First time you open a script's images they take a few seconds to load (the server grabs them from Wikimedia once, then keeps them).</p>

  <details class="cut-more">
    <summary class="bang">Add your own picture</summary>
    <form method="post" enctype="multipart/form-data" class="admin-form">
      <?= dtm_csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="id" value="<?= h($script['id']) ?>">
      <label for="img" class="bang">Picture</label>
      <input id="img" name="img" type="file" accept="image/jpeg,image/png,image/webp,image/gif" required>
      <p class="small">Only pictures you're allowed to use commercially: your own photos, or Wikimedia Commons / Unsplash / Pexels with a CC BY, CC BY-SA, CC0 or public domain license. Not NC or ND, no Google Images grabs. The credit goes on the public sources page.</p>
      <label for="ctitle" class="bang">What is it</label>
      <input id="ctitle" name="title" type="text" maxlength="120" placeholder="Comiskey Park, 1979">
      <label for="cauthor" class="bang">Photo by</label>
      <input id="cauthor" name="author" type="text" maxlength="200" placeholder="Photographer's name, or Duck">
      <label for="clicense" class="bang">License</label>
      <input id="clicense" name="license" type="text" maxlength="120" placeholder="CC BY-SA 4.0 / Unsplash License / My own photo">
      <label for="csource" class="bang">Where it came from</label>
      <input id="csource" name="source" type="url" maxlength="400" placeholder="https://commons.wikimedia.org/wiki/File:...">
      <div class="btn-row"><button type="submit" class="pull-btn bang">Add picture</button></div>
    </form>
  </details>

  <?php if ($hidden): ?>
  <details class="cut-more">
    <summary class="bang">Hidden (<?= count($hidden) ?>)</summary>
    <div class="cut-grid small-grid">
      <?php foreach ($hidden as $im): ?>
      <figure class="cut">
        <img src="<?= h($src($im)) ?>" alt="<?= h($im['title']) ?>" loading="lazy">
        <figcaption>
          <span class="cut-ttl"><?= h($im['title']) ?></span>
          <form method="post"><?= dtm_csrf_field() ?><input type="hidden" name="id" value="<?= h($script['id']) ?>"><input type="hidden" name="file" value="<?= h($im['file']) ?>"><input type="hidden" name="action" value="restore">
            <button type="submit" class="cut-x">Bring it back</button></form>
        </figcaption>
      </figure>
      <?php endforeach; ?>
    </div>
  </details>
  <?php endif; ?>
</section>
<?php dtm_page_end();
