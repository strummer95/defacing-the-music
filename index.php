<?php
// Defacing The Music: home page ("Gig Poster" design).
// Listen links stay empty until the show is on each platform; fill them in here.
$listen = [
  'Spotify'        => '',
  'Apple Podcasts' => '',
  'YouTube'        => '',
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
<meta name="twitter:card" content="summary">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><rect width='64' height='64' rx='12' fill='%23FFD21F'/><circle cx='32' cy='34' r='20' fill='%23E4251B' stroke='%23111' stroke-width='5'/><circle cx='32' cy='34' r='12' fill='%23F2E8D5' stroke='%23111' stroke-width='4'/></svg>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Metal+Mania&family=Bangers&family=Archivo:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/site.css?v=2">
</head>
<body>

<header class="wrap site-header">
  <a href="#top" class="wordmark metal">Defacing the Music</a>
  <nav class="nav bang" aria-label="Main">
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
      <svg viewBox="0 0 600 600" aria-hidden="true">
        <polygon points="592,300 526,332 580,382 507,395 546,458 472,449 491,521 423,492 421,566 364,519 342,589 300,528 258,589 236,519 179,566 177,492 109,521 128,449 54,458 93,395 20,382 74,332 8,300 74,268 20,218 93,205 54,142 128,151 109,79 177,108 179,34 236,81 258,11 300,72 342,11 364,81 421,34 423,108 491,79 472,151 546,142 507,205 580,218 526,268" fill="#E4251B" stroke="#111111" stroke-width="8" stroke-linejoin="round"/>
        <path d="M150,270 C130,190 200,175 185,105 C240,150 232,195 262,185 C248,120 290,85 300,30 C335,105 365,125 345,185 C372,185 385,135 418,112 C418,180 470,200 452,270 Z" fill="#FF8A00" stroke="#111111" stroke-width="8" stroke-linejoin="round"/>
        <path d="M205,260 C195,210 235,200 228,160 C258,190 255,215 278,210 C272,170 295,140 300,110 C322,155 340,170 328,212 C350,210 358,180 380,165 C384,210 410,225 398,260 Z" fill="#FFE23B" stroke="#111111" stroke-width="5" stroke-linejoin="round"/>
        <g transform="rotate(-28 150 120)"><rect x="60" y="112" width="190" height="18" rx="9" fill="#E8B06A" stroke="#111111" stroke-width="5"/><ellipse cx="250" cy="121" rx="14" ry="11" fill="#F2E8D5" stroke="#111111" stroke-width="5"/></g>
        <g transform="rotate(32 460 130)"><rect x="370" y="122" width="190" height="18" rx="9" fill="#E8B06A" stroke="#111111" stroke-width="5"/><ellipse cx="370" cy="131" rx="14" ry="11" fill="#F2E8D5" stroke="#111111" stroke-width="5"/></g>
        <path d="M40,200 L80,215 M30,240 L72,246 M560,215 L520,228 M570,255 L528,258" stroke="#111111" stroke-width="6" stroke-linecap="round"/>
        <g fill="#C9C9C9" stroke="#111111" stroke-width="5">
          <?php foreach ([0,45,90,135,180,225,270,315] as $deg): ?>
          <rect x="290" y="156" width="20" height="30" rx="4" transform="rotate(<?= $deg ?> 300 345)"/>
          <?php endforeach; ?>
        </g>
        <circle cx="300" cy="345" r="168" fill="#E4251B" stroke="#111111" stroke-width="10"/>
        <circle cx="300" cy="345" r="138" fill="#F2E8D5" stroke="#111111" stroke-width="8"/>
        <polyline points="210,265 250,312 228,338 292,372 270,418 300,470" fill="none" stroke="#111111" stroke-width="6" stroke-linejoin="round" stroke-linecap="round"/>
        <path d="M232,292 L272,326 M272,292 L232,326 M328,292 L368,326 M368,292 L328,326" stroke="#111111" stroke-width="10" stroke-linecap="round"/>
        <ellipse cx="305" cy="400" rx="46" ry="34" fill="#111111"/>
        <ellipse cx="312" cy="418" rx="22" ry="12" fill="#E4251B"/>
        <polygon points="90,90 98,109 119,111 102,124 108,144 90,133 72,144 78,124 61,111 82,109" fill="#FFE23B" stroke="#111111" stroke-width="4" stroke-linejoin="round"/>
        <polygon points="520,444 526,461 545,462 530,473 535,491 520,481 505,491 510,473 495,462 514,461" fill="#FFE23B" stroke="#111111" stroke-width="4" stroke-linejoin="round"/>
        <polygon points="500,90 505,103 519,104 509,113 512,126 500,119 488,126 491,113 481,104 495,103" fill="#F2E8D5" stroke="#111111" stroke-width="4" stroke-linejoin="round"/>
        <g transform="rotate(-10 300 560)">
          <text x="300" y="578" text-anchor="middle" font-family="Bangers, sans-serif" font-size="92" fill="#FFE23B" stroke="#111111" stroke-width="14" paint-order="stroke" letter-spacing="3">KA-BLAM!</text>
        </g>
      </svg>
      <p class="sticker bang">First eps<br>coming<br>soon</p>
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
      <p>One guy from Chicagoland, one microphone, and the worst nights in music history. No co-host to stop him. He plays in a Limp Bizkit tribute band, so he has no right to judge anyone. He judges anyway.</p>
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
    <p class="wordmark metal">Defacing the Music</p>
    <p>Due to unforeseen circumstances, all rights reserved. · defacingthemusic.com</p>
  </div>
</footer>

</body>
</html>
