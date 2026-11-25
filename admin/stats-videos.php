<?php
// admin > Stats > one platform's videos (Duck, Nov 25): every video with all its numbers, sortable.
// Columns a platform never gives us are hidden. Data comes from the same snapshots as Stats (lib/analytics.php).
require __DIR__ . '/../lib/analytics.php';
$user = dtm_require_admin();
$p = (string)($_GET['p'] ?? 'youtube');
if ($p !== 'all' && !isset(DTM_AN_PLATFORMS[$p])) $p = 'youtube';
$latest = dtm_an_latest();
$posts = array_filter($latest['posts'], fn($x) => $p === 'all' || ($x['platform'] ?? '') === $p);
uasort($posts, fn($a, $b) => strcmp((string)($b['published'] ?? ''), (string)($a['published'] ?? '')));

$dur = function ($s) { if ($s === null || $s === '') return null; $s = (int)$s; return $s >= 3600 ? gmdate('G:i:s', $s) : gmdate('i:s', $s); };
$num = fn($v) => $v === null ? null : number_format((int)$v);
$cols = [ // key => [label, how to show, sort value]
  'published' => ['Posted', fn($x) => !empty($x['published']) ? date(substr($x['published'], 0, 4) === date('Y') ? 'M j' : 'M j, Y', strtotime($x['published'])) : null, fn($x) => (string)($x['published'] ?? '')],
  'seconds' => ['Length', fn($x) => $dur($x['seconds'] ?? null), fn($x) => (int)($x['seconds'] ?? 0)],
  'views' => ['Views', fn($x) => $num($x['views'] ?? null), fn($x) => (int)($x['views'] ?? 0)],
  'watch_minutes' => ['Watch time', fn($x) => isset($x['watch_minutes']) ? number_format($x['watch_minutes'] / 60, 1) . ' h' : null, fn($x) => (int)($x['watch_minutes'] ?? 0)],
  'avg_seconds' => ['Avg watched', fn($x) => $dur($x['avg_seconds'] ?? null), fn($x) => (int)($x['avg_seconds'] ?? 0)],
  'likes' => ['Likes', fn($x) => $num($x['likes'] ?? null), fn($x) => (int)($x['likes'] ?? 0)],
  'comments' => ['Comments', fn($x) => $num($x['comments'] ?? null), fn($x) => (int)($x['comments'] ?? 0)],
  'shares' => ['Shares', fn($x) => $num($x['shares'] ?? null), fn($x) => (int)($x['shares'] ?? 0)],
  'new_followers' => [$p === 'youtube' ? 'New subs' : 'New follows', fn($x) => $num($x['new_followers'] ?? null), fn($x) => (int)($x['new_followers'] ?? 0)],
];
// Hide columns this platform never fills in.
$cols = array_filter($cols, fn($c, $k) => (bool)array_filter($posts, fn($x) => ($x[$k] ?? null) !== null), ARRAY_FILTER_USE_BOTH);
$tot = [];
foreach (['views', 'watch_minutes', 'likes', 'comments', 'shares', 'new_followers'] as $k) if (isset($cols[$k])) $tot[$k] = array_sum(array_map(fn($x) => (int)($x[$k] ?? 0), $posts));
$label = $p === 'all' ? 'All platforms' : DTM_AN_PLATFORMS[$p];
$state = dtm_an_state();

dtm_page_start('Stats: ' . $label, $user, true);
?>
<section class="admin-card an">
  <div class="an-head"><h1 class="metal"><?= h($label) ?></h1>
    <span class="small an-when"><?= count($posts) ?> video<?= count($posts) === 1 ? '' : 's' ?><?= !empty($state['fetched']) ? ' · updated ' . h(date('M j, g:ia', (int)$state['fetched'])) : '' ?></span>
    <a href="analytics.php" class="an-btn an-back">‹ Stats</a>
  </div>
  <nav class="an-tabs" aria-label="Platform">
    <?php foreach (DTM_AN_PLATFORMS + ['all' => 'All'] as $k => $l): ?><a href="stats-videos.php?p=<?= h($k) ?>"<?= $p === $k ? ' class="on" aria-current="page"' : '' ?>><?= h($l) ?></a><?php endforeach; ?>
  </nav>
  <?php if (!$posts): ?><p class="small">No <?= h($label) ?> videos yet. Hit Refresh on Stats, or connect it under Connections.</p><?php else: ?>
  <div class="an-scroll"><table class="an-table an-sort an-vids">
    <thead><tr><th data-k="t">Video</th><?php foreach ($cols as $k => [$l]): ?><th class="<?= $k === 'published' ? '' : 'num' ?>" data-k="<?= $k === 'published' ? 'd' : 'n' ?>"><?= h($l) ?></th><?php endforeach; ?></tr></thead>
    <tbody>
    <?php foreach ($posts as $x): $t = trim((string)($x['title'] ?? '')) ?: (string)$x['id']; ?>
      <tr>
        <td data-v="<?= h(strtolower($t)) ?>"><?php if ($p === 'all'): ?><span class="an-plats"><?= h(DTM_AN_PLATFORMS[$x['platform']] ?? '') ?>:</span> <?php endif; ?><?php if (!empty($x['url'])): ?><a href="<?= h($x['url']) ?>" target="_blank" rel="noopener"><?= h(mb_strimwidth($t, 0, 90, '…')) ?></a><?php else: ?><?= h(mb_strimwidth($t, 0, 90, '…')) ?><?php endif; ?></td>
        <?php foreach ($cols as $k => [$l, $show, $sort]): $v = $show($x); ?><td class="<?= $k === 'published' ? 'an-date' : 'num' ?>" data-v="<?= h((string)$sort($x)) ?>"><?= $v === null ? '–' : h($v) ?></td><?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr><td>Total</td><?php foreach ($cols as $k => $_): ?><td class="num"><?= isset($tot[$k]) ? h($k === 'watch_minutes' ? number_format($tot[$k] / 60, 1) . ' h' : number_format($tot[$k])) : '' ?></td><?php endforeach; ?></tr></tfoot>
  </table></div>
  <p class="small an-hint-line">Click a column to sort. Lifetime numbers. <?php if ($p === 'youtube'): ?>Watch time, Avg watched, Shares and New subs come from YouTube Analytics (about 2 days behind; connect it on Stats if they're missing).<?php elseif ($p === 'tiktok'): ?>TikTok doesn't give out watch time or new follows per video.<?php else: ?>Missing columns are numbers this platform doesn't give out.<?php endif; ?></p>
  <?php endif; ?>
</section>
<script>
document.querySelectorAll('.an-sort th').forEach((th, i) => th.addEventListener('click', () => {
  const tb = th.closest('table').tBodies[0], num = th.dataset.k === 'n', asc = th.dataset.dir !== 'asc';
  th.closest('tr').querySelectorAll('th').forEach(x => delete x.dataset.dir); th.dataset.dir = asc ? 'asc' : 'desc';
  [...tb.rows].sort((a, b) => { let x = a.cells[i].dataset.v || '', y = b.cells[i].dataset.v || '';
    if (num) { x = +x; y = +y; } return (x > y ? 1 : x < y ? -1 : 0) * (asc ? 1 : -1) * (num ? -1 : 1); }).forEach(r => tb.appendChild(r));
}));
</script>
<?php dtm_page_end();
