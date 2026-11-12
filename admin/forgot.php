<?php
// Forgot password: emails a one-hour reset link to a password admin.
require __DIR__ . '/../lib/auth.php';
dtm_session();
$sent = false; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  dtm_check_csrf();
  if (dtm_throttled()) { $error = 'Too many tries. Wait 15 minutes.'; }
  else {
    dtm_fail(); // every request counts toward the per-IP limit
    $a = dtm_find_admin((string)($_POST['email'] ?? ''));
    if ($a) {
      $token = bin2hex(random_bytes(32));
      $email = $a['email'];
      dtm_json_update('resets.json', function ($r) use ($token, $email) {
        $now = time();
        $r = array_values(array_filter($r, fn($x) => $x['exp'] > $now && $x['email'] !== $email));
        $r[] = ['hash' => hash('sha256', $token), 'email' => $email, 'exp' => $now + 3600];
        return $r;
      });
      $base = rtrim((string)(dtm_secrets()['site_url'] ?? 'http://defacingthemusic.com'), '/');
      $link = "$base/admin/reset.php?t=$token";
      $body = "Someone (hopefully you) asked to reset your Defacing the Music admin password.\n\n$link\n\nThe link works for one hour. If this wasn't you, ignore this email.\n";
      @mail($email, 'Reset your Defacing the Music admin password', $body, "From: Defacing the Music <noreply@defacingthemusic.com>\r\nContent-Type: text/plain; charset=UTF-8");
    }
    $sent = true; // same answer either way so nobody can fish for admin emails
  }
}
dtm_page_start('Forgot password');
?>
<section class="admin-card narrow">
  <h1 class="metal">Forgot it?</h1>
  <?php if ($sent): ?>
    <p class="notice ok bang" role="status">If that's an admin email, a reset link is on its way. Check spam too.</p>
    <p class="small"><a href="login.php">Back to sign in</a></p>
  <?php else: ?>
    <?php if ($error): ?><p class="notice err bang" role="alert"><?= h($error) ?></p><?php endif; ?>
    <form method="post" class="admin-form">
      <?= dtm_csrf_field() ?>
      <label for="email" class="bang">Admin email</label>
      <input id="email" name="email" type="email" required autocomplete="username">
      <button type="submit" class="pull-btn bang">Send reset link</button>
    </form>
    <p class="small"><a href="login.php">Back to sign in</a></p>
  <?php endif; ?>
</section>
<?php dtm_page_end();
