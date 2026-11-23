<?php
// News Shorts picks ("Fresh Tracks"): Claude drops a file in lib/news/ every Monday (docs/NEWS-SHORTS.md).
require __DIR__ . '/../lib/scripts.php';
$user = dtm_require_admin();

// The picks in one file: [label, title, happened, script text].
function dtm_news_picks(string $md): array {
  $out = [];
  foreach (preg_split('/^## /m', $md) as $sec) {
    if (!preg_match('/^(\d+)\.\s+(.+)$/m', $sec, $m)) continue;
    $label = trim($m[2]);
    $title = preg_match('/^\*\*Title:\*\*\s*(.+)$/m', $sec, $t) ? trim($t[1]) : $label;
    $happened = preg_match('/^\*\*Happened:\*\*\s*(\d{4}-\d{2}-\d{2})/m', $sec, $d) ? $d[1] : '';
    $parts = preg_split('/^\*\*Sources:\*\*.*$/m', $sec, 2);
    $script = trim($parts[1] ?? '');
    $out[] = [$label, $title, $happened, $script];
  }
  return $out;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  dtm_check_csrf();
  $f = basename((string)($_POST['f'] ?? ''));
  $n = (int)($_POST['n'] ?? -1);
  $path = DTM_ROOT . "/lib/news/$f";
  $picks = is_file($path) && str_ends_with($f, '.md') ? dtm_news_picks((string)file_get_contents($path)) : [];
  if (isset($picks[$n])) {
    [$label, $title, $happened, $script] = $picks[$n];
    $sid = bin2hex(random_bytes(6));
    dtm_json_update('scripts.json', function ($l) use ($sid, $title, $happened, $script) {
      $l[] = ['id' => $sid, 'episode' => '', 'kind' => 'week', 'status' => 'draft', 'title' => $title, 'subtitle' => '', 'body' => $script . "\n",
              'sources' => '', 'youtube' => '', 'date' => '', 'story_date' => $happened, 'updated' => date('c')];
      return $l;
    });
    header('Location: scripts.php?edit=' . $sid . '&saved=1'); exit;
  }
  header('Location: news.php'); exit;
}
$files = glob(DTM_ROOT . '/lib/news/*.md') ?: [];
rsort($files);
$want = basename((string)($_GET['f'] ?? ''));
$cur = $files ? ($want !== '' && in_array(DTM_ROOT . "/lib/news/$want", $files, true) ? DTM_ROOT . "/lib/news/$want" : $files[0]) : null;
// Just enough markdown: headings, bold, links, lists, paragraphs.
function dtm_news_md(string $md): string {
  $inline = function (string $s): string {
    $s = h($s);
    $s = preg_replace('/\*\*(.+?)\*\*/', '<b>$1</b>', $s);
    return preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/', fn($m) => '<a href="' . $m[2] . '" target="_blank" rel="noopener">' . $m[1] . '</a>', $s);
  };
  $out = ''; $list = false;
  foreach (preg_split('/\R/', $md) as $l) {
    $t = trim($l);
    if ($list && !str_starts_with($t, '- ')) { $out .= '</ul>'; $list = false; }
    if ($t === '') continue;
    if (preg_match('/^(#{1,3})\s+(.*)$/', $t, $m)) { $n = strlen($m[1]) + 1; $out .= "<h$n>" . $inline($m[2]) . "</h$n>"; continue; }
    if (str_starts_with($t, '- ')) { if (!$list) { $out .= '<ul>'; $list = true; } $out .= '<li>' . $inline(substr($t, 2)) . '</li>'; continue; }
    $out .= '<p>' . $inline($t) . '</p>';
  }
  return $out . ($list ? '</ul>' : '');
}
dtm_page_start('News', $user, true);
?>
<section class="admin-card news">
  <h1 class="metal">Fresh Tracks</h1>
  <p class="small"><a href="side.php?kind=week">Back to Side Stories</a></p>
  <p class="small">Claude digs up the week's wildest music stories (wild, not tragic, not political, interesting even if you've never heard of the band) and writes each one as a 45 to 90 second Short. Pick one and hit record. Hit Pull latest first if this week's isn't here yet.</p>
  <?php if (!$files): ?><p>No picks yet. None yet.</p><?php else: ?>
  <p class="small"><?php foreach ($files as $f): $b = basename($f); ?><a href="news.php?f=<?= h(urlencode($b)) ?>"<?= $f === $cur ? ' style="font-weight:800"' : '' ?>><?= h(substr($b, 0, -3)) ?></a> &nbsp; <?php endforeach; ?></p>
  <?php $picks = dtm_news_picks((string)file_get_contents($cur)); if ($picks): ?>
  <div class="news-start"><span class="bang">Make one a script</span>
    <?php foreach ($picks as $i => [$label, , $happened]): ?>
    <form method="post"><?= dtm_csrf_field() ?><input type="hidden" name="f" value="<?= h(basename($cur)) ?>"><input type="hidden" name="n" value="<?= $i ?>">
      <button type="submit" class="pull-btn bang"><?= h(($i + 1) . '. ' . $label) ?><?= $happened !== '' ? ' (' . h(date('M j', strtotime($happened))) . ')' : '' ?></button></form>
    <?php endforeach; ?>
    <p class="small">Turns that pick into a Fresh Tracks script in Side Stories, with the date it happened, so it gets a Prompter, Images and Transcript like everything else.</p>
  </div>
  <?php endif; ?>
  <div class="news-body"><?= dtm_news_md((string)file_get_contents($cur)) ?></div>
  <?php endif; ?>
</section>
<?php dtm_page_end();
