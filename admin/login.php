<?php
require __DIR__ . '/../lib/auth.php';
dtm_session();
if (!dtm_admins() && !dtm_google_ready()) { header('Location: setup.php'); exit; }
if (dtm_user()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  dtm_check_csrf();
  if (dtm_throttled()) {
    $error = 'Too many wrong tries. Wait 15 minutes.';
  } elseif (isset($_POST['credential'])) {
    $g = dtm_google_ready() ? dtm_google_verify((string)$_POST['credential']) : null;
    if ($g) { dtm_clear_fails(); dtm_login_as($g[0], $g[1], 'google'); header('Location: index.php'); exit; }
    dtm_fail(); $error = "That Google account isn't on the admin list.";
  } else {
    $a = dtm_find_admin((string)($_POST['login'] ?? ''));
    if ($a && password_verify((string)($_POST['password'] ?? ''), $a['hash'])) {
      dtm_clear_fails(); dtm_login_as($a['email'], $a['name'], 'password'); header('Location: index.php'); exit;
    }
    dtm_fail(); $error = 'Wrong email or password.';
  }
}
$reset = ($_GET['reset'] ?? '') === 'done';
dtm_page_start('Sign in');
?>
<section class="admin-card narrow">
  <h1 class="metal">Backstage</h1>
  <p>Admins only. Security is a guy named Gary and he takes this very seriously.</p>
  <?php if ($error): ?><p class="notice err bang" role="alert"><?= h($error) ?></p><?php endif; ?>
  <?php if ($reset): ?><p class="notice ok bang" role="status">Password changed. Sign in with the new one.</p><?php endif; ?>

  <?php if (dtm_google_ready()): ?>
    <div class="google-box">
      <div id="g_id_onload" data-client_id="<?= h(dtm_secrets()['google_client_id']) ?>" data-callback="dtmGoogle" data-auto_prompt="false"></div>
      <div class="g_id_signin" data-type="standard" data-size="large" data-theme="filled_black" data-text="signin_with" data-shape="rectangular"></div>
    </div>
    <form method="post" id="google-form"><?= dtm_csrf_field() ?><input type="hidden" name="credential" id="credential"></form>
    <script>function dtmGoogle(r){document.getElementById('credential').value=r.credential;document.getElementById('google-form').submit();}</script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <p class="or bang">or</p>
  <?php endif; ?>

  <?php if (dtm_admins()): ?>
  <form method="post" class="admin-form">
    <?= dtm_csrf_field() ?>
    <label for="login" class="bang">Email</label>
    <input id="login" name="login" type="text" autocomplete="username" required autocapitalize="none">
    <label for="password" class="bang">Password</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required>
    <button type="submit" class="pull-btn bang">Sign in</button>
  </form>
  <p class="small"><a href="forgot.php">Forgot your password?</a></p>
  <?php else: ?>
  <p class="small">No password account yet. <a href="setup.php">Set one up</a>.</p>
  <?php endif; ?>
</section>
<?php dtm_page_end();
