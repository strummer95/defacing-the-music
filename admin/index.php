<?php
require __DIR__ . '/../lib/auth.php';
$user = dtm_require_admin();
$subs = count(dtm_json_read('subscribers.json'));
$last = dtm_json_read('deploys.json')[0] ?? null;
dtm_page_start('Admin', $user, true);
?>
<section class="admin-card">
  <h1 class="metal">Backstage</h1>
  <p>Everything's in the menu up top. Hit <strong>Pull latest</strong> (top right) to copy what Claude made onto the live site.</p>
  <ul class="stats">
    <li><a href="guests.php"><span class="bang">Guest List</span> <?= (int)$subs ?> signed up</a></li>
    <li><span class="bang">Last pull</span> <?= $last ? h($last['at'] . ' by ' . $last['by'] . ($last['ok'] ? '' : ' (failed)')) : 'Never from here' ?></li>
  </ul>
</section>
<?php dtm_page_end();
