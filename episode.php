<?php
// Defacing The Music: single episode page ("Gig Poster" design). ?slug=<slug>
require __DIR__ . '/lib/episodes.php';
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$slug = (string)($_GET['slug'] ?? '');
$ep = $slug !== '' ? dtm_episode_by_slug($slug) : null;
if (!$ep) { http_response_code(404); }
$title = $ep ? $ep['title'] : 'Episode not found';
$desc = $ep ? $ep['summary'] : "That episode doesn't exist, or hasn't dropped yet.";
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $h($title) ?> | Defacing the Music</title>
<meta name="description" content="<?= $h($desc) ?>">
<meta name="theme-color" content="#FFD21F">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Defacing the Music">
<meta property="og:title" content="<?= $h($title) ?>">
<meta property="og:description" content="<?= $h($desc) ?>">
<meta property="og:url" content="http://defacingthemusic.com/episode.php?slug=<?= $h($slug) ?>">
<meta property="og:image" content="http://defacingthemusic.com/<?= $ep && !empty($ep['cover']) ? $h($ep['cover']) : 'assets/og-image.png' ?>">
<link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png">
<link rel="icon" type="image/png" sizes="192x192" href="assets/logo-192.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Metal+Mania&family=Bangers&family=Archivo:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/site.css?v=4">
</head>
<body>

<header class="wrap site-header">
  <a href="./" class="wordmark metal"><img src="assets/logo-192.png" alt="" width="56" height="56" class="logo-mark">Defacing the Music</a>
  <nav class="nav bang" aria-label="Main">
    <a href="./#episodes">Episodes</a>
    <a href="./#host">The Host</a>
    <a href="./#list">Guest List</a>
  </nav>
</header>

<main>
<section class="wrap episodes">
<?php if (!$ep): ?>
  <div class="section-head">
    <h2 class="metal">No show tonight</h2>
    <p class="bang">That episode doesn't exist, or hasn't dropped yet.</p>
  </div>
  <p><a href="./#episodes" class="btn alt">Back to the lineup</a></p>
<?php else: ?>
  <div class="section-head">
    <h2 class="metal"><?= $h($ep['title']) ?></h2>
    <p class="bang">EP <?= $h($ep['number']) ?> · <?= $h(date('F j, Y', strtotime($ep['date']))) ?></p>
  </div>
  <div class="cards">
    <article class="ticket ticket-solo">
      <?php if (!empty($ep['cover'])): ?>
        <img class="ticket-cover" src="<?= $h($ep['cover']) ?>" alt="" width="640" height="360">
      <?php endif; ?>
      <div class="ticket-body">
        <?php if (!empty($ep['subject'])): ?><p class="who">Featuring <?= $h($ep['subject']) ?></p><?php endif; ?>
        <p class="blurb"><?= $h($ep['summary']) ?></p>
        <?php if (!empty($ep['embed'])): ?>
          <iframe class="episode-embed" src="<?= $h($ep['embed']) ?>" loading="lazy" allow="encrypted-media" allowfullscreen></iframe>
        <?php else: ?>
          <div class="ticket-foot"><span class="status bang">Coming soon</span></div>
        <?php endif; ?>
      </div>
    </article>
  </div>
  <p><a href="./#episodes" class="btn alt">Back to the lineup</a></p>
<?php endif; ?>
</section>
</main>

<footer class="site-footer">
  <div class="wrap footer-inner">
    <p class="wordmark metal"><img src="assets/logo-192.png" alt="" width="44" height="44" class="logo-mark">Defacing the Music</p>
    <p>Due to unforeseen circumstances, all rights reserved. · defacingthemusic.com</p>
  </div>
</footer>

</body>
</html>
