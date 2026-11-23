<?php
// Side Stories (Duck, Nov 23): everything that isn't a numbered main episode. Off The Record (legend or fact),
// Mic Swap, Fresh Tracks (quick news Shorts), Encore, and anything else. Same list and buttons as Episodes.
require __DIR__ . '/../lib/board.php';
$user = dtm_require_admin();
$want = (string)($_GET['kind'] ?? '');
$groups = dtm_board('side');
if ($want !== '' && in_array($want, DTM_SIDE_KINDS, true)) foreach ($groups as &$g) $g = array_values(array_filter($g, fn($r) => $r['kind'] === $want));
unset($g);
$news = glob(DTM_ROOT . '/lib/news/*.md') ?: [];
rsort($news);
dtm_page_start('Side Stories', $user, true);
?>
<section class="admin-card">
  <h1 class="metal">Side Stories</h1>
  <p class="small">Everything outside the numbered episodes. Same buttons: Prompter, Images, Transcript.</p>
  <p class="kind-tabs small">
    <a href="side.php"<?= $want === '' ? ' class="on"' : '' ?>>All</a>
    <?php foreach (DTM_SIDE_KINDS as $k): ?><a href="side.php?kind=<?= h($k) ?>"<?= $want === $k ? ' class="on"' : '' ?>><?= h(DTM_SCRIPT_KINDS[$k]) ?></a><?php endforeach; ?>
  </p>
  <p class="btn-row">
    <a class="pull-btn bang link-btn" href="scripts.php?new=1&amp;kind=<?= h($want !== '' ? $want : 'otr') ?>">New <?= h($want !== '' ? DTM_SCRIPT_KINDS[$want] : 'side story') ?></a>
    <?php if ($news && ($want === '' || $want === 'week')): ?><a class="pull-btn bang link-btn" href="news.php">This week's news picks</a><?php endif; ?>
  </p>
  <?= dtm_board_html($groups, ['published' => 'Nothing out yet.', 'ready' => 'Nothing ready.', 'draft' => 'No drafts yet. Hit New to start one.']) ?>
</section>
<?php dtm_page_end();
