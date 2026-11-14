<?php
// Guest List: everyone who signed up on the home page (data/subscribers.json). CSV export, remove.
require __DIR__ . '/../lib/subscribers.php';
$user = dtm_require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  dtm_check_csrf();
  $action = (string)($_POST['action'] ?? '');
  $email = (string)($_POST['email'] ?? '');
  if ($action === 'remove' && $email !== '') {
    dtm_remove_subscriber($email);
    header('Location: subscribers.php?removed=1'); exit;
  }
}

if (($_GET['csv'] ?? '') === '1') {
  $list = dtm_subscribers();
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="guest-list.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['email', 'signed up']);
  foreach ($list as $row) fputcsv($out, [(string)($row['email'] ?? ''), (string)($row['at'] ?? '')]);
  fclose($out);
  exit;
}

$list = dtm_subscribers();
dtm_page_start('Guest List', $user, true);
?>
<section class="admin-card">
  <h1 class="metal">Guest List</h1>
  <?php if (isset($_GET['removed'])): ?><p class="notice ok bang" role="status">Removed.</p><?php endif; ?>
  <p><?= count($list) ?> signed up. <a class="pull-btn bang link-btn" href="subscribers.php?csv=1">Download CSV</a></p>
  <?php if (!$list): ?><p>No signups yet.</p><?php endif; ?>
  <ul class="script-list">
    <?php foreach ($list as $row): ?>
    <li>
      <span class="ttl"><?= h($row['email'] ?? '') ?><em class="meta"><?= h(!empty($row['at']) ? date('M j, Y g:ia', strtotime($row['at'])) : '') ?></em></span>
      <span class="acts">
        <form method="post" onsubmit="return confirm('Remove <?= h($row['email'] ?? '') ?> from the Guest List?')">
          <?= dtm_csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="email" value="<?= h($row['email'] ?? '') ?>">
          <button type="submit" class="signout-btn bang">Remove</button>
        </form>
      </span>
    </li>
    <?php endforeach; ?>
  </ul>
</section>
<?php dtm_page_end();
