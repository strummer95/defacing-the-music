<?php
// Side Stories (Duck, Nov 23): everything that isn't a numbered main episode. Off The Record (legend or fact),
// Mic Swap, Fresh Tracks (quick news Shorts), Encore, and anything else. Same list and buttons as Episodes.
require __DIR__ . '/../lib/board.php';
require_once __DIR__ . '/../lib/bsides.php';
$user = dtm_require_admin();
$want = (string)($_GET['kind'] ?? '');
$bsideErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bside') {
  dtm_check_csrf();
  $new = dtm_bside_build((array)($_POST['ids'] ?? []), (string)($_POST['title'] ?? ''));
  if ($new !== '') { header('Location: scripts.php?edit=' . urlencode($new) . '&saved=1'); exit; }
  $bsideErr = 'Pick at least two stories.';
  $want = 'otr';
}
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
  </p>
  <?php if ($want === 'otr'): $pool = dtm_bside_pool(); ?>
  <details class="bside-build" open>
    <summary class="bang">Build a B-Side <span class="small">(tick the stories, in the order you want them)</span></summary>
    <?php if ($bsideErr): ?><p class="notice err bang" role="alert"><?= h($bsideErr) ?></p><?php endif; ?>
    <?php if (!$pool): ?><p class="small">No single stories yet.</p><?php else: ?>
    <form method="post" id="bside-form">
      <?= dtm_csrf_field() ?><input type="hidden" name="action" value="bside">
      <ul class="bside-pool">
        <?php foreach ($pool as $p): ?>
        <li><label><input type="checkbox" name="ids[]" value="<?= h($p['id']) ?>" data-min="<?= h((string)$p['min']) ?>"> <b><?= h($p['title']) ?></b> <span class="small"><?= h((string)$p['min']) ?> min<?= $p['status'] === 'idea' ? ' · idea' : '' ?></span></label> <a class="small" href="scripts.php?edit=<?= h(urlencode($p['id'])) ?>">Edit</a></li>
        <?php endforeach; ?>
      </ul>
      <p class="btn-row"><input type="text" name="title" maxlength="120" placeholder="Title (optional)" class="bside-title"> <button type="submit" class="pull-btn bang">Make it one B-Side</button> <span class="small" id="bside-total">Pick 2 or more.</span></p>
    </form>
    <script>
    (function(){var f=document.getElementById('bside-form');if(!f)return;var order=[];
    function upd(){var n=0,m=0;order.forEach(function(b){n++;m+=parseFloat(b.dataset.min)||0;});
      var t=document.getElementById('bside-total');
      t.textContent=n<2?'Pick 2 or more.':n+' stories, '+m.toFixed(1)+' min of script, about '+Math.round(m*2.7)+' to '+Math.round(m*3.6)+' min finished with Pat.';}
    f.addEventListener('change',function(e){var b=e.target;if(b.name!=='ids[]')return;order=order.filter(function(x){return x!==b;});if(b.checked)order.push(b);upd();});
    f.addEventListener('submit',function(){order.forEach(function(b){var h=document.createElement('input');h.type='hidden';h.name='ids[]';h.value=b.value;f.appendChild(h);b.disabled=true;});});
    })();
    </script>
    <?php endif; ?>
  </details>
  <?php endif; ?>
  <?= dtm_board_html($groups, ['published' => 'Nothing out yet.', 'ready' => 'Nothing ready.', 'draft' => 'No drafts yet. Hit New to start one.']) ?>
</section>
<?php dtm_page_end();
