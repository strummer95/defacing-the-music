<?php
// Shared shell for the plain public pages (privacy, terms). Uses the Backstage look (assets/backstage.css).
require_once __DIR__ . '/https.php';
function dtm_legal_page(string $title, string $path, string $html): void {
  $root = dirname(__DIR__);
  ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title) ?> | Defacing the Music</title>
<link rel="canonical" href="https://defacingthemusic.com<?= htmlspecialchars($path) ?>">
<meta name="theme-color" content="#161518">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png?v=2">
<link rel="apple-touch-icon" href="/assets/apple-touch-icon.png?v=2">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&family=Oswald:wght@500;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/backstage.css?v=<?= @filemtime($root . '/assets/backstage.css') ?>">
</head>
<body class="sponsor-page">
<header class="bs-header"><div class="wrap">
  <a href="/" class="brand"><img src="/assets/logo-192.png" alt="" width="40" height="40">Defacing the Music</a>
  <nav class="bs-nav" aria-label="Main"><a href="/">The show</a></nav>
</div></header>
<main class="wrap kit">
  <section class="kit-card legal">
    <h1><?= htmlspecialchars($title) ?></h1>
    <?= $html ?>
  </section>
</main>
<footer class="bs-footer"><div class="wrap">
  <a href="/" class="brand"><img src="/assets/logo-192.png" alt="" width="40" height="40">Defacing the Music</a>
  <small>© <?= date('Y') ?> Duck and Rabbit LLC · <a href="/privacy/">Privacy</a> · <a href="/terms/">Terms</a></small>
</div></footer>
</body>
</html>
<?php
}
