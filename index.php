<?php
// Defacing The Music: home page ("Gig Poster" design).
// Listen links stay empty until the show is on each platform; fill them in here.
$listen = [
  'Spotify'        => '',
  'Apple Podcasts' => '',
  'YouTube'        => 'https://youtu.be/X48gJu0NSyw',
];
$episodes = [
  ['num' => '01', 'title' => 'Due To Excessive Flash Powder', 'who' => 'Who Let Keith Moon Near the Pyro',
   'blurb' => "Live TV, 1967. Keith Moon's bass drum gets loaded with way more flash powder than any human needed. Pete Townshend's hair catches fire, and his ears never forgive anybody.",
   'meta' => 'CBS Studios, 1967', 'shadow' => ''],
  ['num' => '02', 'title' => 'Due To The Venue Being Ripped Apart', 'who' => 'Who Let Fred Durst Try to Calm People Down',
   'blurb' => '200,000 people, an old Air Force base, four dollar water, and a nu-metal band that did not calm anybody down.',
   'meta' => 'Rome, NY, 1999', 'shadow' => 'red'],
  ['num' => '03', 'title' => 'Due To a Possible Rabies Infection', 'who' => 'Who Let a Kid From Des Moines Bring a Bat to an Ozzy Show',
   'blurb' => "A kid from Des Moines throws a bat on stage. Ozzy assumes it's rubber. It is not rubber.",
   'meta' => 'Des Moines, 1982', 'shadow' => ''],
];
$signup = $_GET['signup'] ?? '';
$h = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$desc = "Music history's most unhinged live disasters, meltdowns and terrible decisions. One host. Fifteen minutes. No refunds.";
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Defacing the Music | Music history's most unhinged moments</title>
<meta name="description" content="<?= $h($desc) ?>">
<meta name="theme-color" content="#FFD21F">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Defacing the Music">
<meta property="og:title" content="Defacing the Music">
<meta property="og:description" content="<?= $h($desc) ?>">
<meta property="og:url" content="http://defacingthemusic.com/">
<meta property="og:image" content="http://defacingthemusic.com/assets/og-image.png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="http://defacingthemusic.com/assets/og-image.png">
<link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png">
<link rel="icon" type="image/png" sizes="192x192" href="assets/logo-192.png">
<link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Metal+Mania&family=Bangers&family=Archivo:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/site.css?v=<?= @filemtime(__DIR__ . '/assets/site.css') ?>">
</head>
<body>

<header class="wrap site-header">
  <a href="#top" class="wordmark metal"><img src="assets/logo-192.png" alt="" width="56" height="56" class="logo-mark">Defacing the Music</a>
  <nav class="nav bang" aria-label="Main">
    <a href="#now-playing">Episode 0</a>
    <a href="#episodes">Episodes</a>
    <a href="#host">The Host</a>
    <a href="#list">Guest List</a>
    <a href="#listen" class="cta">Listen Now</a>
  </nav>
</header>

<main>
<section id="top" class="hero">
  <div class="wrap hero-inner">
    <div class="hero-copy">
      <p class="kicker bang">A podcast about music's worst nights</p>
      <h1 class="metal">Defacing<br>the Music</h1>
      <p class="lede">Music history's most unhinged live disasters, meltdowns and terrible decisions. One host. Fifteen minutes. No refunds.</p>
      <div id="listen" class="listen bang">
        <?php $i = 0; foreach ($listen as $name => $url): $cls = 'btn' . ($i++ === 2 ? ' alt' : ''); ?>
          <?php if ($url): ?>
            <a class="<?= $cls ?>" href="<?= $h($url) ?>" rel="noopener" target="_blank"><?= $h($name) ?></a>
          <?php else: ?>
            <span class="<?= $cls ?> soon"><?= $h($name) ?><small>soon</small></span>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="hero-art">
      <img src="assets/logo.png" alt="Defacing the Music logo: a busted yellow warning sign with a music note for an exclamation mark" width="512" height="512" class="hero-logo">
      <a href="#now-playing" class="sticker bang">Ep 0<br>out<br>now</a>
    </div>
  </div>
</section>

<div class="ticker bang" aria-hidden="true">
  <div class="ticker-track">
    <?php for ($t = 0; $t < 2; $t++): ?>
    <p>Banned for life ★ Hospitalized for exhaustion ★ Creative differences ★ No refunds ★ Strictly no pyro ★ Banned for life ★ Hospitalized for exhaustion ★ Creative differences ★ No refunds ★ Strictly no pyro ★</p>
    <?php endfor; ?>
  </div>
</div>

<section id="now-playing" class="wrap now-playing">
  <div class="section-head">
    <h2 class="metal">Now Playing</h2>
    <p class="bang">Episode 0. Admit one.</p>
  </div>
  <div class="np-grid">
    <div class="video-frame">
      <iframe src="https://www.youtube-nocookie.com/embed/X48gJu0NSyw" title="Defacing the Music, Episode 0: Due To Unforeseen Circumstances" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
    </div>
    <div class="np-copy">
      <p class="np-num bang">EP 00</p>
      <h3 class="bang">Due To Unforeseen Circumstances</h3>
      <p class="who">Who Let This Guy Have a Mic</p>
      <p>Meet your host, hear the house rules, and get a preview of the disasters coming up: Keith Moon's flash powder, Woodstock '99, and a bat that was not rubber.</p>
      <a class="btn bang" href="https://youtu.be/X48gJu0NSyw" target="_blank" rel="noopener">Watch on YouTube</a>
    </div>
  </div>
</section>

<section id="episodes" class="wrap episodes">
  <div class="section-head">
    <h2 class="metal">Tonight's Lineup</h2>
    <p class="bang">Doors at 7. Disaster by 7:15.</p>
  </div>
  <div class="cards">
    <?php foreach ($episodes as $ep): ?>
    <article class="ticket <?= $h($ep['shadow']) ?>">
      <div class="ticket-top bang">
        <span class="num">EP <?= $h($ep['num']) ?></span>
        <span class="admit">Admit one</span>
      </div>
      <div class="ticket-body">
        <h3 class="bang"><?= $h($ep['title']) ?></h3>
        <p class="who"><?= $h($ep['who']) ?></p>
        <p class="blurb"><?= $h($ep['blurb']) ?></p>
        <div class="ticket-foot">
          <span class="meta"><?= $h($ep['meta']) ?> · 15 min</span>
          <span class="status bang">Coming soon</span>
        </div>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</section>

<section id="host" class="host">
  <div class="wrap host-inner">
    <div class="host-photo">
      <svg viewBox="0 0 200 260" role="img" aria-label="Cartoon microphone">
        <rect x="55" y="10" width="90" height="130" rx="45" fill="#FFD21F" stroke="#F2E8D5" stroke-width="8"/>
        <path d="M70,50 H130 M70,75 H130 M70,100 H130" stroke="#111111" stroke-width="7" stroke-linecap="round"/>
        <path d="M35,110 C35,170 165,170 165,110" fill="none" stroke="#F2E8D5" stroke-width="10" stroke-linecap="round"/>
        <path d="M100,165 V215 M60,225 H140" stroke="#F2E8D5" stroke-width="10" stroke-linecap="round"/>
        <path d="M150,20 L178,6 M160,48 L192,46" stroke="#E4251B" stroke-width="8" stroke-linecap="round"/>
      </svg>
    </div>
    <div class="host-copy">
      <h2 class="metal">Who let this guy have a mic?</h2>
      <p>One guy from Chicagoland, one microphone, and the worst nights in music history. No co-host to stop him. Thirty two years of playing guitar and standing on stages, watching people make really bad decisions that were awesome. He judges them anyway.</p>
      <p class="stamp bang">Every story is real. Allegedly.</p>
    </div>
  </div>
</section>

<section id="list" class="guest">
  <div class="guest-inner">
    <h2 class="metal">Get on the Guest List</h2>
    <p>New episodes in your inbox before security can stop us. No spam. We can barely manage a podcast.</p>
    <?php if ($signup === 'ok'): ?>
      <p class="notice ok bang" role="status">You're on the list. Security has been notified.</p>
    <?php elseif ($signup === 'bad'): ?>
      <p class="notice err bang" role="alert">That email looks fake. Try again.</p>
    <?php elseif ($signup === 'err'): ?>
      <p class="notice err bang" role="alert">Something broke on our end. Try again later.</p>
    <?php endif; ?>
    <form class="signup" method="post" action="signup.php">
      <label for="email" class="bang">Your email</label>
      <input id="email" name="email" type="email" required maxlength="254" autocomplete="email" placeholder="you@example.com">
      <div class="hp" aria-hidden="true"><label for="website">Leave this empty</label><input id="website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
      <button type="submit" class="bang">Sign me up</button>
    </form>
  </div>
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
