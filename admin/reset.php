<?php
require __DIR__ . '/../lib/auth.php';
dtm_session();
$token = (string)($_GET['t'] ?? $_POST['t'] ?? '');
$hash = hash('sha256', $token);
$row = null;
foreach (dtm_json_read('resets.json') as $r) if (hash_equals($r['hash'], $hash) && $r['exp'] > time()) $row = $r;
$error = '';
if ($row && $_SERVER['REQUEST_METHOD'] === 'POST') {
  dtm_check_csrf();
  $pw = (string)($_POST['password'] ?? '');
  if (strlen($pw) < 10) $error = 'Password needs at least 10 characters.';
  else {
    $email = $row['email'];
    dtm_json_update('admins.json', function ($admins) use ($email, $pw) {
      foreach ($admins as &$x) if ($x['email'] === $email) $x['hash'] = password_hash($pw, PASSWORD_DEFAULT);
      return $admins;
    });
    dtm_json_update('resets.json', fn($r) => array_values(array_filter($r, fn($x) => $x['email'] !== $email)));
    header('Location: login.php?reset=done'); exit;
  }
}
dtm_page_start('Reset password');
?>
<section class="admin-card narrow">
  <h1 class="metal">New password</h1>
  <?php if (!$row): ?>
    <p class="notice err bang">That link is expired or already used.</p>
    <p class="small"><a href="forgot.php">Send a new one</a></p>
  <?php else: ?>
    <?php if ($error): ?><p class="notice err bang" role="alert"><?= h($error) ?></p><?php endif; ?>
    <form method="post" class="admin-form">
      <?= dtm_csrf_field() ?><input type="hidden" name="t" value="<?= h($token) ?>">
      <label for="password" class="bang">New password (10+ characters)</label>
      <input id="password" name="password" type="password" required minlength="10" autocomplete="new-password">
      <button type="submit" class="pull-btn bang">Save it</button>
    </form>
  <?php endif; ?>
</section>
<?php dtm_page_end();
