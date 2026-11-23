<?php
// Images for one script: cutaway pictures Duck can drop in over a cut. Pictures only here;
// who took each one and its license live on the public Sources & Credits page (/ep/N).
require __DIR__ . '/../lib/cutaways.php';
$user = dtm_require_admin();
$id = (string)($_GET['id'] ?? $_POST['id'] ?? '');
$script = $id !== '' ? dtm_script($id) : null;
if (!$script || dtm_safe_id($script['id']) === '') { http_response_code(404); dtm_page_start('Images', $user); echo '<section class="admin-card"><h1 class="metal">Images</h1><p>No such script. <a href="episodes.php">Back to episodes</a></p></section>'; dtm_page_end(); exit; }
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
  if ($action === 'newshare') { dtm_cut_share_token($script['id'], true); header('Location: images.php?id=' . urlencode($script['id']) . '&newshare=1'); exit; }
  if ($action === 'label') {
    $key = (string)($_POST['key'] ?? '');
    $known = array_column(dtm_cutaways($script, true), 'key');
    if (in_array($key, $known, true)) {
      $label = trim(mb_substr((string)($_POST['label'] ?? ''), 0, 120));
      $when = trim(mb_substr((string)($_POST['when'] ?? ''), 0, 200));
      $credit = null;
      if (str_starts_with($key, 'own:') && isset($_POST['author'])) {
        $src = trim(mb_substr((string)($_POST['source'] ?? ''), 0, 400));
        if ($src !== '' && !dtm_safe_url($src)) { header('Location: images.php?id=' . urlencode($script['id']) . '&badlink=1#' . rawurlencode($key)); exit; }
        $credit = ['author' => trim(mb_substr((string)$_POST['author'], 0, 200)), 'license' => trim(mb_substr((string)($_POST['license'] ?? ''), 0, 120)), 'source' => $src];
      }
      dtm_cut_state_update($script['id'], function ($st) use ($key, $label, $when, $credit) {
        if ($label === '' && $when === '') unset($st['labels'][$key]); else $st['labels'][$key] = ['label' => $label, 'when' => $when];
        if ($credit) foreach ($st['added'] as &$a) if ('own:' . ($a['file'] ?? '') === $key) { $a = $credit + $a; }
        return $st;
      });
    }
    header('Location: images.php?id=' . urlencode($script['id']) . '&labeled=1#' . rawurlencode($key)); exit;
  }
  if ($action === 'add') {
    $files = $_FILES['img'] ?? null;
    $author = trim((string)($_POST['author'] ?? ''));
    $license = trim((string)($_POST['license'] ?? ''));
    $source = trim((string)($_POST['source'] ?? ''));
    $baseTitle = trim((string)($_POST['title'] ?? ''));
    $when = trim(mb_substr((string)($_POST['when'] ?? ''), 0, 200));
    $n = is_array($files['name'] ?? null) ? count($files['name']) : 0;
    if (!$n) $error = 'Pick a picture to upload.';
    elseif ($source !== '' && !dtm_safe_url($source)) $error = 'The link has to start with https://';
    elseif (mb_strlen($baseTitle . $author . $license) > 600) $error = 'Keep the credit fields short.';
    else {
      $dir = dtm_uploads_dir() . '/cutaways/own/' . $script['id'];
      if (!is_dir($dir)) @mkdir($dir, 0755, true);
      $rows = []; $bad = [];
      for ($k = 0; $k < $n; $k++) {
        $orig = (string)$files['name'][$k]; $tmp = (string)$files['tmp_name'][$k]; $err = (int)$files['error'][$k];
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if ($err !== UPLOAD_ERR_OK) { $bad[] = "$orig (" . ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE ? 'too big for this server' : 'did not arrive') . ')'; continue; }
        if (!isset(DTM_CUT_TYPES[$ext]) || !@getimagesize($tmp)) { $bad[] = "$orig (not a JPG, PNG, WebP or GIF)"; continue; }
        if ((int)$files['size'][$k] > DTM_CUT_MAX_BYTES) { $bad[] = "$orig (over 30 MB)"; continue; }
        $name = date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        [$w, $h] = getimagesize($tmp);
        if (!@move_uploaded_file($tmp, "$dir/$name")) { $bad[] = "$orig (couldn't save; is the web hosting full?)"; continue; }
        $title = $baseTitle !== '' ? ($n > 1 ? $baseTitle . ' ' . ($k + 1) : $baseTitle) : pathinfo($orig, PATHINFO_FILENAME);
        $rows[] = ['file' => $name, 'title' => $title, 'author' => $author, 'license' => $license, 'license_url' => '', 'source' => $source, 'width' => $w, 'height' => $h, 'added' => date('c')];
      }
      if ($rows) dtm_cut_state_update($script['id'], function ($st) use ($rows, $when) {
        foreach ($rows as $r) { $st['added'][] = $r; if ($when !== '') $st['labels']['own:' . $r['file']] = ['label' => '', 'when' => $when]; }
        return $st;
      });
      if ($rows && !$bad) { header('Location: images.php?id=' . urlencode($script['id']) . '&added=' . count($rows)); exit; }
      $error = ($rows ? count($rows) . ' added. ' : '') . 'These didn\'t work: ' . implode('; ', $bad) . '.';
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
  <p class="small"><a href="<?= ($script['kind'] ?? 'episode') === 'episode' ? 'episodes.php' : 'side.php' ?>">Back to the list</a> · <a href="prompter.php?id=<?= h(urlencode($script['id'])) ?>">Prompter</a> · <a href="edit.php?script=<?= h(urlencode($script['id'])) ?>">Transcript</a> · <a href="scripts.php?edit=<?= h(urlencode($script['id'])) ?>">Edit script</a></p>
  <h1 class="metal">Images</h1>
  <p class="cut-for bang"><?= $ep !== '' ? 'EP ' . h(str_pad($ep, 2, '0', STR_PAD_LEFT)) . ' · ' : '' ?><?= h($script['title']) ?></p>
  <?php if (isset($_GET['added'])): ?><p class="notice ok bang" role="status"><?= max(1, (int)$_GET['added']) > 1 ? (int)$_GET['added'] . ' pictures added.' : 'Added.' ?></p><?php endif; ?>
  <?php if (isset($_GET['badlink'])): ?><p class="notice err bang" role="alert">The "where it came from" link has to start with https://</p><?php endif; ?>
  <?php if (isset($_GET['labeled'])): ?><p class="notice ok bang" role="status">Label saved.</p><?php endif; ?>
  <?php if (isset($_GET['hidden'])): ?><p class="notice ok bang" role="status">Hidden. It's in the hidden pile at the bottom if you want it back.</p><?php endif; ?>
  <?php if (isset($_GET['removed'])): ?><p class="notice ok bang" role="status">Removed.</p><?php endif; ?>
  <?php if (isset($_GET['newshare'])): ?><p class="notice ok bang" role="status">New link made. The old one stopped working.</p><?php endif; ?>
  <?php $shareTok = dtm_cut_share_token($script['id']); if ($shareTok !== ''): ?>
  <div class="cut-share"><p class="small">Need Claude to look at these pictures (to find credits and so on)? Copy this and paste it to Claude. It's a private link to this episode's pictures only.</p>
    <?= dtm_copy_line('https://defacingthemusic.com/admin/images-share.php?id=' . rawurlencode($script['id']) . '&t=' . $shareTok, 'Copy link for Claude') ?>
    <form method="post" class="small"><?= dtm_csrf_field() ?><input type="hidden" name="id" value="<?= h($script['id']) ?>"><input type="hidden" name="action" value="newshare"><button type="submit" class="linkish">New link (turns the old one off)</button></form>
  </div>
  <?php endif; ?>
  <?php if ($error): ?><p class="notice err bang" role="alert"><?= h($error) ?></p><?php endif; ?>
  <details class="cut-more cut-add" open>
    <summary class="bang">Add pictures</summary>
    <form method="post" enctype="multipart/form-data" class="admin-form"><?= dtm_csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="id" value="<?= h($script['id']) ?>">
      <label for="img" class="bang">Pictures (pick one or several)</label>
      <input id="img" name="img[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
      <label for="ctitle" class="bang">Label</label>
      <input id="ctitle" name="title" type="text" maxlength="120" placeholder="Comiskey Park, 1979 (blank = use the file name)">
      <label for="cwhen" class="bang">Show when (optional)</label>
      <input id="cwhen" name="when" type="text" maxlength="200" placeholder="When Duck says &quot;Des Moines, January 1982&quot;">
      <details><summary class="small">Credit (needed for anything you didn't shoot yourself)</summary>
        <label for="cauthor" class="bang">Photo by</label>
        <input id="cauthor" name="author" type="text" maxlength="200" placeholder="Photographer's name, or Duck">
        <label for="clicense" class="bang">License</label>
        <input id="clicense" name="license" type="text" maxlength="120" placeholder="CC BY-SA 4.0 / Unsplash License / My own photo">
        <label for="csource" class="bang">Where it came from</label>
        <input id="csource" name="source" type="url" maxlength="400" placeholder="https://commons.wikimedia.org/wiki/File:...">
        <p class="small">Only pictures you're allowed to use: your own, or Wikimedia Commons / Unsplash / Pexels with CC BY, CC BY-SA, CC0 or public domain. Not NC or ND, no Google Images grabs. The credit goes on the public sources page.</p>
      </details>
      <div class="btn-row"><button type="submit" class="pull-btn bang">Upload</button></div>
    </form>
  </details>
  <?php if (!$shown): ?><p>No pictures for this one yet.</p><?php endif; ?>
  <div class="cut-grid">
    <?php foreach ($shown as $im): ?>
    <figure class="cut" id="<?= h($im['key']) ?>">
      <a href="<?= h($src($im)) ?>" target="_blank" rel="noopener"><img src="<?= h($src($im)) ?>" alt="<?= h($im['label']) ?>" loading="lazy"<?= !empty($im['width']) ? ' width="' . (int)$im['width'] . '" height="' . (int)$im['height'] . '"' : '' ?>></a>
      <figcaption>
        <span class="cut-ttl"><?= h($im['label']) ?></span>
        <?php if ($im['when'] !== ''): ?><span class="cut-when">Show when: <?= h($im['when']) ?></span><?php endif; ?>
        <?php if ($im['kind'] === 'own' && (trim((string)($im['author'] ?? '')) === '' || trim((string)($im['license'] ?? '')) === '')): ?><span class="cut-when">Needs a credit: open Edit label</span><?php endif; ?>
        <details class="cut-label"><summary class="small"><?= $im['kind'] === 'own' ? 'Edit label and credit' : 'Edit label' ?></summary>
          <form method="post"><?= dtm_csrf_field() ?><input type="hidden" name="id" value="<?= h($script['id']) ?>"><input type="hidden" name="action" value="label"><input type="hidden" name="key" value="<?= h($im['key']) ?>">
            <input name="label" type="text" maxlength="120" value="<?= h($im['label'] !== $im['title'] ? $im['label'] : '') ?>" placeholder="<?= h($im['title']) ?>" aria-label="Label">
            <input name="when" type="text" maxlength="200" value="<?= h($im['when']) ?>" placeholder="Show when... (optional)" aria-label="Show when">
            <?php if ($im['kind'] === 'own'): ?>
            <span class="small"><b>Credit</b> (shows on the public sources page)</span>
            <input name="author" type="text" maxlength="200" value="<?= h($im['author'] ?? '') ?>" placeholder="Photo by" aria-label="Photo by">
            <input name="license" type="text" maxlength="120" value="<?= h($im['license'] ?? '') ?>" placeholder="License (CC BY 2.0 / My own photo...)" aria-label="License">
            <input name="source" type="url" maxlength="400" value="<?= h($im['source'] ?? '') ?>" placeholder="https://commons.wikimedia.org/wiki/File:..." aria-label="Where it came from">
            <?php endif; ?>
            <button type="submit" class="pull-btn bang">Save</button>
          </form></details>
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
