<?php
// Public Sources & Credits page per episode: defacingthemusic.com/ep/<number> (rewritten by ep/.htaccess).
// /ep/ alone lists every episode. A library script with no number yet has no public page;
// a signed-in admin can preview one with ?preview=<script id>.
require __DIR__ . '/../lib/cutaways.php';

$n = (string)($_GET['n'] ?? '');
if ($n === '' && !empty($_SERVER['PATH_INFO'])) $n = trim((string)$_SERVER['PATH_INFO'], '/'); // /ep/index.php/1 fallback
$ep = null; $script = null; $preview = false;
if ($n !== '' && preg_match('/^\d{1,4}$/', $n)) {
  $ep = dtm_episode_by_number($n);
  if ($ep && $ep['script_id'] !== '') $script = dtm_script($ep['script_id']);
} elseif (($pv = (string)($_GET['preview'] ?? '')) !== '' && dtm_user()) {
  $script = dtm_script($pv); $preview = (bool)$script;
}
$listing = $n === '' && !$preview;
$episodes = $listing ? array_values(array_filter(dtm_episodes(), fn($e) => $e['script_id'] !== '')) : [];
if (!$listing && !$ep && !$preview) http_response_code(404);

$title = $ep['title'] ?? ($script['title'] ?? '');
$sub = $ep['subtitle'] ?? ($script['subtitle'] ?? '');
$sources = $script ? dtm_script_sources($script) : [];
$images = $script ? dtm_cutaways($script) : [];
$pageTitle = $listing ? 'Sources & Credits' : ($ep ? 'Episode ' . $ep['number'] . ': ' . $title : ($preview ? 'Preview: ' . $title : 'Not found'));
$canon = $ep ? dtm_episode_page_url((string)$ep['number']) : 'https://defacingthemusic.com/ep/';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle) ?> | Defacing the Music</title>
<meta name="description" content="Sources and image credits for Defacing the Music<?= $ep ? ', episode ' . h($ep['number']) : '' ?>.">
<?php if ($preview || (!$listing && !$ep)): ?><meta name="robots" content="noindex"><?php else: ?><link rel="canonical" href="<?= h($canon) ?>"><?php endif; ?>
<meta name="theme-color" content="#FFD21F">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png">
<link rel="apple-touch-icon" href="/assets/apple-touch-icon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Metal+Mania&family=Bangers&family=Archivo:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/site.css?v=<?= @filemtime(DTM_ROOT . '/assets/site.css') ?>">
</head>
<body class="credits-page">
<header class="wrap site-header">
  <a href="/" class="wordmark metal"><img src="/assets/logo-192.png" alt="" width="56" height="56" class="logo-mark">Defacing the Music</a>
  <nav class="nav bang" aria-label="Main"><a href="/ep/">All episodes</a><a href="/#listen" class="cta">Listen Now</a></nav>
</header>
<main class="wrap credits">
<?php if ($listing): ?>
  <p class="kicker bang">The receipts</p>
  <h1 class="metal">Sources &amp; Credits</h1>
  <p class="credits-lede">Every story on this show is real, so here's where we got it. Pick an episode.</p>
  <?php if (!$episodes): ?><p>Nothing here yet.</p><?php endif; ?>
  <ul class="ep-index">
    <?php foreach ($episodes as $e): ?>
    <li><a href="<?= h(dtm_episode_page_path((string)$e['number'])) ?>"><span class="num bang">EP <?= h(str_pad((string)$e['number'], 2, '0', STR_PAD_LEFT)) ?></span>
      <span class="t bang"><?= h($e['title']) ?></span><?php if ($e['subtitle'] !== ''): ?><span class="who"><?= h($e['subtitle']) ?></span><?php endif; ?></a></li>
    <?php endforeach; ?>
  </ul>
<?php elseif (!$script): ?>
  <p class="kicker bang">Due to unforeseen circumstances</p>
  <h1 class="metal">No episode here</h1>
  <p class="credits-lede"><?= $ep ? 'That episode has no sources page yet.' : 'That episode isn\'t out yet, or it never existed. Either way, nothing to see.' ?></p>
  <p><a class="btn bang" href="/ep/">All episodes</a></p>
<?php else: ?>
  <?php if ($preview): ?><p class="notice err bang">Preview. This script has no episode number yet, so the public doesn't see this page.</p><?php endif; ?>
  <p class="kicker bang"><?= $ep ? 'Episode ' . h($ep['number']) . ' · ' : '' ?>Sources &amp; Credits</p>
  <h1 class="metal credits-title"><?= h($title) ?></h1>
  <?php if ($sub !== ''): ?><p class="credits-who"><?= h($sub) ?></p><?php endif; ?>
  <?php if ($ep && ($ep['youtube'] !== '' || $ep['spotify'] !== '')): ?>
  <p class="credits-watch">
    <?php if (dtm_safe_url($ep['youtube'])): ?><a class="btn sm bang" href="<?= h($ep['youtube']) ?>" target="_blank" rel="noopener">Watch on YouTube</a><?php endif; ?>
    <?php if (dtm_safe_url($ep['spotify'])): ?><a class="btn sm alt bang" href="<?= h($ep['spotify']) ?>" target="_blank" rel="noopener">Listen on Spotify</a><?php endif; ?>
  </p>
  <?php endif; ?>

  <section class="credit-card">
    <h2 class="bang">Sources</h2>
    <?php if (!$sources): ?><p>This one's mostly the show talking about itself. No outside sources needed.</p><?php else: ?>
    <p class="small-print">Where the facts came from. Anything we couldn't pin down, the episode calls a legend.</p>
    <ol class="src-list">
      <?php foreach ($sources as $s): $u = dtm_safe_url((string)$s['url']); ?>
      <li><?php if ($u): ?><a href="<?= h($u) ?>" target="_blank" rel="noopener nofollow"><?= h($s['title']) ?></a><?php else: ?><?= h($s['title']) ?><?php endif; ?><?php if (($s['publisher'] ?? '') !== ''): ?><span class="pub">, <?= h($s['publisher']) ?></span><?php endif; ?></li>
      <?php endforeach; ?>
    </ol>
    <?php endif; ?>
  </section>

  <?php if ($images): ?>
  <section class="credit-card red">
    <h2 class="bang">Image credits</h2>
    <p class="small-print">Pictures that may show up on screen in this episode. They may be cropped, resized or zoomed for video. None of these people or organizations endorse the show.</p>
    <ol class="src-list">
      <?php foreach ($images as $im):
        $u = dtm_safe_url((string)($im['source'] ?? '')); $lu = dtm_safe_url((string)($im['license_url'] ?? ''));
        $commons = str_contains($u, 'commons.wikimedia.org'); ?>
      <li>
        <?php if ($u): ?><a href="<?= h($u) ?>" target="_blank" rel="noopener nofollow">"<?= h($im['title']) ?>"</a><?php else: ?>"<?= h($im['title']) ?>"<?php endif; ?>
        <?php if (($im['author'] ?? '') !== ''): ?> by <?= h($im['author']) ?><?php endif; ?><?php if (($im['license'] ?? '') !== ''): ?>,
          <?php if ($lu): ?><a href="<?= h($lu) ?>" target="_blank" rel="noopener nofollow license"><?= h($im['license']) ?></a><?php else: ?><?= h($im['license']) ?><?php endif; ?><?php endif; ?><?php if ($commons): ?>, via Wikimedia Commons<?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ol>
  </section>
  <?php endif; ?>
<?php endif; ?>
</main>
<footer class="site-footer">
  <div class="wrap footer-inner">
    <p class="wordmark metal"><img src="/assets/logo-192.png" alt="" width="44" height="44" class="logo-mark">Defacing the Music</p>
    <p>Due to unforeseen circumstances, all rights reserved. · <a href="/">defacingthemusic.com</a></p>
  </div>
</footer>
</body>
</html>
