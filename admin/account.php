<?php
require __DIR__ . '/../lib/auth.php';
$user = dtm_require_admin();
$msg = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  dtm_check_csrf();
  if (($_POST['action'] ?? '') === 'logout') { dtm_logout(); header('Location: login.php'); exit; }
  if (($_POST['action'] ?? '') === 'password') {
    $a = dtm_find_admin($user['email']);
    $new = (string)($_POST['new'] ?? '');
    if (!$a) $error = 'You signed in with Google, so there is no password to change.';
    elseif (!password_verify((string)($_POST['current'] ?? ''), $a['hash'])) $error = 'Current password is wrong.';
    elseif (strlen($new) < 10) $error = 'New password needs at least 10 characters.';
    else {
      $email = $a['email'];
      dtm_json_update('admins.json', function ($admins) use ($email, $new) {
        foreach ($admins as &$x) if ($x['email'] === $email) $x['hash'] = password_hash($new, PASSWORD_DEFAULT);
        return $admins;
      });
      $msg = 'Password changed.';
    }
  }
}
$hasPassword = (bool)dtm_find_admin($user['email']);
dtm_page_start('Account', $user, true);
?>
<section class="admin-card narrow">
  <h1 class="metal"><?= h($user['name']) ?></h1>
  <p><?= h($user['email']) ?> · signed in with <?= $user['via'] === 'google' ? 'Google' : 'password' ?></p>
  <?php if ($msg): ?><p class="notice ok bang" role="status"><?= h($msg) ?></p><?php endif; ?>
  <?php if ($error): ?><p class="notice err bang" role="alert"><?= h($error) ?></p><?php endif; ?>
  <?php if ($hasPassword): ?>
  <form method="post" class="admin-form">
    <?= dtm_csrf_field() ?><input type="hidden" name="action" value="password">
    <h2 class="bang">Change password</h2>
    <label for="current" class="bang">Current password</label>
    <input id="current" name="current" type="password" required autocomplete="current-password">
    <label for="new" class="bang">New password (10+ characters)</label>
    <input id="new" name="new" type="password" required minlength="10" autocomplete="new-password">
    <button type="submit" class="pull-btn bang">Change it</button>
  </form>
  <?php endif; ?>
  <form method="post" class="admin-form">
    <?= dtm_csrf_field() ?><input type="hidden" name="action" value="logout">
    <button type="submit" class="signout-btn bang">Sign out</button>
  </form>
</section>
<?php dtm_page_end();
