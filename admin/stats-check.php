<?php
// admin > Stats > Check Facebook numbers (Nov 26): shows every views number Facebook gives for each video and the
// Page's own daily total, so we can match the site to Facebook's dashboard instead of guessing.
require __DIR__ . '/../lib/analytics.php';
$user = dtm_require_admin();
$cfg = dtm_an_cfg();
$rows = []; $page = []; $err = '';
$try = function (string $id, string $edge, string $metric, string $tok, array $extra = []) {
  try {
    $r = dtm_an_meta($id . '/' . $edge, ['metric' => $metric] + $extra, $tok);
    foreach ($r['data'] ?? [] as $d) { $v = $d['values'][0]['value'] ?? ($d['total_value']['value'] ?? null); if (is_numeric($v)) return (string)(int)$v; if (is_array($v)) return json_encode($v); }
    return 'empty';
  } catch (RuntimeException $e) { return 'no'; }
};
if ($cfg['meta'] === '') $err = 'Facebook is not connected.';
else {
  try {
    [$tok, $pageId] = dtm_an_meta_page($cfg['meta']);
    foreach (['page_media_view', 'page_video_views', 'page_impressions'] as $m) {
      try {
        $r = dtm_an_meta($pageId . '/insights', ['metric' => $m, 'period' => 'day', 'since' => date('Y-m-d', strtotime('-28 days')), 'until' => date('Y-m-d')], $tok);
        $page[$m] = array_sum(array_map(fn($v) => is_numeric($v['value'] ?? null) ? (int)$v['value'] : 0, $r['data'][0]['values'] ?? []));
      } catch (RuntimeException $e) { $page[$m] = 'no'; }
    }
    $seen = [];
    foreach (['video_reels', 'videos'] as $edge) {
      $r = dtm_an_meta($pageId . '/' . $edge, ['fields' => 'id,title,description,post_id,created_time', 'limit' => 25], $tok);
      foreach ($r['data'] ?? [] as $v) {
        if (isset($seen[$v['id']])) continue; $seen[$v['id']] = 1;
        $pid = (string)($v['post_id'] ?? ''); $pid = $pid === '' ? '' : (str_contains($pid, '_') ? $pid : $pageId . '_' . $pid);
        $rows[] = ['t' => mb_strimwidth(trim((string)($v['title'] ?? '')) ?: trim((string)($v['description'] ?? '')), 0, 50, '…'), 'd' => substr((string)$v['created_time'], 0, 10),
          'post media view' => $pid !== '' ? $try($pid, 'insights', 'post_media_view', $tok) : 'no post id',
          'reel total plays' => $try($v['id'], 'video_insights', 'fb_reels_total_plays', $tok),
          'reel plays' => $try($v['id'], 'video_insights', 'blue_reels_play_count', $tok),
          '3-sec views' => $try($v['id'], 'video_insights', 'post_video_views', $tok),
          'total video views' => $try($v['id'], 'video_insights', 'total_video_views', $tok),
          'impressions' => $pid !== '' ? $try($pid, 'insights', 'post_impressions', $tok) : 'no post id'];
      }
    }
  } catch (RuntimeException $e) { $err = $e->getMessage(); }
}
dtm_page_start('Check Facebook numbers', $user, true);
?>
<section class="admin-card an">
  <div class="an-head"><h1 class="metal">Facebook check</h1><a href="analytics.php" class="an-btn an-back">‹ Stats</a></div>
  <p class="small">Every views number Facebook gives the site, per video. Screenshot this next to Facebook's dashboard and Claude picks the one that matches. "no" means Facebook refused that number.</p>
  <?php if ($err): ?><p class="notice err bang"><?= h($err) ?></p><?php endif; ?>
  <?php if ($page): ?><p><b>Page total, last 28 days:</b> <?php foreach ($page as $m => $v): ?><?= h($m) ?> = <b><?= h(is_int($v) ? number_format($v) : $v) ?></b> &nbsp; <?php endforeach; ?></p><?php endif; ?>
  <?php if ($rows): ?>
  <div class="an-scroll"><table class="an-table an-vids"><thead><tr><?php foreach (array_keys($rows[0]) as $c): ?><th class="<?= in_array($c, ['t', 'd'], true) ? '' : 'num' ?>"><?= h($c === 't' ? 'Video' : ($c === 'd' ? 'Posted' : $c)) ?></th><?php endforeach; ?></tr></thead>
  <tbody><?php foreach ($rows as $r): ?><tr><?php foreach ($r as $c => $v): ?><td class="<?= in_array($c, ['t', 'd'], true) ? '' : 'num' ?>"><?= h(is_numeric($v) ? number_format((int)$v) : $v) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</section>
<?php dtm_page_end();
