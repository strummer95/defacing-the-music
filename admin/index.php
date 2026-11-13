<?php
require __DIR__ . '/../lib/auth.php';
$user = dtm_require_admin();
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$subs = count(dtm_json_read('subscribers.json'));
$last = dtm_json_read('deploys.json')[0] ?? null;
dtm_page_start('Admin', $user, true);
?>
<?php if ($flash): ?>
  <section class="admin-card <?= $flash['ok'] ? 'ok' : 'err' ?>">
    <h2 class="bang"><?= $flash['ok'] ? 'Pulled. The site is up to date.' : 'Pull failed' ?></h2>
    <pre class="pull-output"><?= h($flash['msg']) ?></pre>
    <p class="small"><a href="../" target="_blank" rel="noopener">Open the site</a></p>
  </section>
<?php endif; ?>
<section class="admin-card">
  <h1 class="metal">Backstage</h1>
  <p>Hit <strong>Pull latest</strong> (top right) to copy what's on GitHub <code>main</code> onto the live site.</p>
  <p class="btn-row"><a class="pull-btn bang link-btn" href="episodes.php">Episodes</a> <a class="pull-btn bang link-btn" href="scripts.php">Scripts and teleprompter</a> <a class="pull-btn bang link-btn" href="uploads.php">Uploads</a></p>
  <ul class="stats">
    <li><span class="bang">Guest List</span> <?= (int)$subs ?> signed up</li>
    <li><span class="bang">Last pull</span> <?= $last ? h($last['at'] . ' by ' . $last['by'] . ($last['ok'] ? '' : ' (failed)')) : 'Never from here' ?></li>
  </ul>
</section>
<?php dtm_page_end();
