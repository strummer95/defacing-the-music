<?php
// News Shorts picks ("Due To This Week"): Claude drops a file in lib/news/ every Monday (docs/NEWS-SHORTS.md).
require __DIR__ . '/../lib/auth.php';
$user = dtm_require_admin();
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
  <h1 class="metal">Due To This Week</h1>
  <p class="small">Claude digs up the week's wildest music stories (wild, not tragic, not political, interesting even if you've never heard of the band) and writes each one as a 45 to 90 second Short. Pick one and hit record. Hit Pull latest first if this week's isn't here yet.</p>
  <?php if (!$files): ?><p>No picks yet. None yet.</p><?php else: ?>
  <p class="small"><?php foreach ($files as $f): $b = basename($f); ?><a href="news.php?f=<?= h(urlencode($b)) ?>"<?= $f === $cur ? ' style="font-weight:800"' : '' ?>><?= h(substr($b, 0, -3)) ?></a> &nbsp; <?php endforeach; ?></p>
  <div class="news-body"><?= dtm_news_md((string)file_get_contents($cur)) ?></div>
  <?php endif; ?>
</section>
<?php dtm_page_end();
