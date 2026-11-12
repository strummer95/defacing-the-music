<?php
// First-run setup: creates the first password admin. Locked once any admin exists.
// Proof you own the server: the deploy_key from ../dtm-secrets.php.
require __DIR__ . '/../lib/auth.php';
dtm_session();
if (dtm_admins()) { header('Location: login.php'); exit; }

$error = '';
$key = (string)(dtm_secrets()['deploy_key'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  dtm_check_csrf();
  $name = trim((string)($_POST['name'] ?? ''));
  $email = strtolower(trim((string)($_POST['email'] ?? '')));
  $pw = (string)($_POST['password'] ?? '');
  if (dtm_throttled()) $error = 'Too many wrong tries. Wait 15 minutes.';
  elseif ($key === '' || !hash_equals($key, (string)($_POST['key'] ?? ''))) { dtm_fail(); $error = "That's not the deploy key."; }
  elseif ($name === '' || mb_strlen($name) > 60) $error = 'Give yourself a name (60 characters max).';
  elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'That email looks fake.';
  elseif (strlen($pw) < 10) $error = 'Password needs at least 10 characters.';
  else {
    $created = false;
    dtm_json_update('admins.json', function ($admins) use ($name, $email, $pw, &$created) {
      if ($admins) return null; // someone beat us to it
      $created = true;
      return [['name' => $name, 'email' => $email, 'hash' => password_hash($pw, PASSWORD_DEFAULT), 'created' => date('c')]];
    });
    if ($created) { dtm_clear_fails(); dtm_login_as($email, $name, 'password'); header('Location: index.php'); exit; }
    header('Location: login.php'); exit;
  }
}
dtm_page_start('First-time setup');
?>
<section class="admin-card narrow">
  <h1 class="metal">First-time setup</h1>
  <?php if ($key === ''): ?>
    <p class="notice err bang">The server is missing its secrets file.</p>
    <p>Create <code>dtm-secrets.php</code> one folder above the site (next to the <code>dtm</code> folder) with a <code>deploy_key</code> in it, then reload this page. The README in the repo shows the exact format.</p>
  <?php else: ?>
    <p>Make the admin account. This page locks itself as soon as one exists.</p>
    <?php if ($error): ?><p class="notice err bang" role="alert"><?= h($error) ?></p><?php endif; ?>
    <form method="post" class="admin-form">
      <?= dtm_csrf_field() ?>
      <label for="key" class="bang">Deploy key (from dtm-secrets.php)</label>
      <input id="key" name="key" type="password" required autocomplete="off">
      <label for="name" class="bang">Your name</label>
      <input id="name" name="name" type="text" required maxlength="60" value="<?= h($_POST['name'] ?? '') ?>">
      <label for="email" class="bang">Email</label>
      <input id="email" name="email" type="email" required autocomplete="username" value="<?= h($_POST['email'] ?? '') ?>">
      <label for="password" class="bang">Password (10+ characters)</label>
      <input id="password" name="password" type="password" required minlength="10" autocomplete="new-password">
      <button type="submit" class="pull-btn bang">Create admin</button>
    </form>
  <?php endif; ?>
</section>
<?php dtm_page_end();
