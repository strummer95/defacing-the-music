<?php
require __DIR__ . '/lib/https.php';
// Defacing The Music: home page ("Gig Poster" design).
// Listen links stay empty until the show is on each platform; fill them in here.
$listen = [
  'Spotify'        => 'https://open.spotify.com/show/2WZWOUIt4e7I7IIQRvh6fx',
  'Apple Podcasts' => 'https://podcasts.apple.com/us/podcast/defacing-the-music/id6834472289',
  'YouTube'        => 'https://www.youtube.com/@DefacingTheMusic',
];
// Social pages. Paste the full URL to make a button live.
$social = [
  'Facebook'  => 'https://www.facebook.com/defacingthemusic',
  'Instagram' => 'https://www.instagram.com/defacingthemusicpod/',
  'TikTok'    => 'https://www.tiktok.com/@defacingthemusic',
];
// Upcoming stories (no episode numbers until Duck makes them episodes in the admin). Launch order.
$episodes = [
  ['title' => 'Due To a Possible Rabies Infection', 'who' => 'Who Let That Guy Bring a Bat?',
   'blurb' => "A kid from Des Moines throws a bat on stage. Ozzy assumes it's rubber. It is not rubber. And it wasn't even his first animal.",
   'meta' => 'Des Moines, 1982', 'shadow' => ''],
  ['title' => 'Due To Not Giving Peace a Chance', 'who' => 'Who Asked Fred Durst to Be a Therapist?',
   'blurb' => '200,000 people, an old Air Force base, four dollar water, and a nu-metal band that did not calm anybody down.',
   'meta' => 'Rome, NY, 1999', 'shadow' => 'red'],
  ['title' => 'Due To Excessive Disco', 'who' => 'Who Let a DJ Blow Up Center Field?',
   'blurb' => "A White Sox promotion: bring a disco record, get in for 98 cents, watch a DJ blow them up in center field. Chicago did not stay in its seats.",
   'meta' => 'Comiskey Park, 1979', 'shadow' => ''],
];
// Latest episode for the top of the page: the newest published episode in admin > Episodes that has a YouTube link.
// Falls back to Episode 0 if that data isn't available.
$latest = ['num' => '0', 'title' => 'Due To Unforeseen Circumstances', 'who' => 'Who Gave This Guy a Mic?', 'yt' => 'FQK-XMkoajA'];
$blurbs = [
  '0' => "Meet your host, hear the house rules, and get a preview of the disasters coming up: Keith Moon's flash powder, Woodstock '99, and a bat that was not rubber.",
];
try {
  require_once __DIR__ . '/lib/episodes.php';
  foreach (array_reverse(dtm_episodes()) as $e) {
    if (($e['status'] ?? '') !== 'published') continue;
    if (!preg_match('~(?:youtu\.be/|v=|/embed/|/shorts/|/live/)([A-Za-z0-9_-]{11})~', (string)($e['youtube'] ?? ''), $m)) continue;
    $latest = ['num' => (string)$e['number'], 'title' => (string)$e['title'], 'who' => (string)($e['subtitle'] ?? ''), 'yt' => $m[1]];
    break;
  }
} catch (Throwable $t) { /* keep the fallback */ }
$latestBlurb = $blurbs[$latest['num']] ?? '';
$signup = $_GET['signup'] ?? '';
$h = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$desc = "Rock and roll's worst nights, and who to blame for them. Real stories, told like a friend at the bar who actually went down the rabbit hole.";
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
<meta property="og:url" content="https://defacingthemusic.com/">
<meta property="og:image" content="https://defacingthemusic.com/assets/og-image.png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="https://defacingthemusic.com/assets/og-image.png">
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
    <a href="#top">Latest</a>
    <a href="#episodes">Episodes</a>
    <a href="#host">The Hosts</a>
    <a href="#list">Guest List</a>
    <a href="#listen" class="cta">Listen Now</a>
  </nav>
</header>

<main>
<section id="top" class="hero hero-np">
  <div class="wrap np-hero">
    <div class="np-brand">
      <p class="kicker bang">A podcast about music's worst nights</p>
      <h1 class="metal">Defacing<br>the Music</h1>
      <p class="lede">Rock and roll's worst nights, and who to blame for them. Real stories, told like a friend at the bar who actually went down the rabbit hole.</p>
      <img class="hosts-art" src="assets/hosts.webp" srcset="assets/hosts-800.webp 800w, assets/hosts.webp 1400w" sizes="(max-width: 720px) 92vw, 46vw" width="1400" height="1045" alt="Your hosts: Duck, slicked-back hair, aviator glasses and ear gauges, arms crossed, and Pat, bald with a full beard" fetchpriority="high">
    </div>
    <div class="np-side">
      <p class="np-tag bang">Now playing · EP <?= $h(str_pad($latest['num'], 2, '0', STR_PAD_LEFT)) ?></p>
      <div class="video-frame np-video">
        <iframe src="https://www.youtube-nocookie.com/embed/<?= $h($latest['yt']) ?>" title="Defacing the Music, Episode <?= $h($latest['num']) ?>: <?= $h($latest['title']) ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
      </div>
      <h2 class="bang np-title"><?= $h($latest['title']) ?></h2>
      <?php if ($latest['who'] !== ''): ?><p class="np-who"><?= $h($latest['who']) ?></p><?php endif; ?>
      <div id="listen" class="listen-main">
        <a class="btn yt-btn bang" href="<?= $h($listen['YouTube']) ?>?sub_confirmation=1" rel="noopener" target="_blank">
          <svg width="34" height="24" viewBox="0 0 34 24" aria-hidden="true"><rect x="1.5" y="1.5" width="31" height="21" rx="6" fill="#E4251B" stroke="#111111" stroke-width="3"/><path d="M13.5 7 L22.5 12 L13.5 17 Z" fill="#F2E8D5" stroke="#111111" stroke-width="2" stroke-linejoin="round"/></svg>
          Subscribe on YouTube
        </a>
        <p class="also">Also on
          <?php $alts = array_filter(['Spotify' => $listen['Spotify'], 'Apple Podcasts' => $listen['Apple Podcasts']]); $k = 0;
          foreach ($alts as $name => $url): ?><?= $k++ ? ' and ' : ' ' ?><a href="<?= $h($url) ?>" rel="noopener" target="_blank"><?= $h($name) ?></a><?php endforeach; ?>
        </p>
      </div>
      <div class="follow bang">
        <span class="follow-label">Follow the wreckage</span>
        <?php foreach ($social as $name => $url): ?>
          <?php if ($url): ?>
            <a class="btn sm" href="<?= $h($url) ?>" rel="noopener" target="_blank"><?= $h($name) ?></a>
          <?php else: ?>
            <span class="btn sm soon"><?= $h($name) ?><small>soon</small></span>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
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
        <span class="num"><?= $h(substr($ep['meta'], -4)) ?></span>
        <span class="admit">Admit one</span>
      </div>
      <div class="ticket-body">
        <h3 class="bang"><?= $h($ep['title']) ?></h3>
        <p class="who"><?= $h($ep['who']) ?></p>
        <p class="blurb"><?= $h($ep['blurb']) ?></p>
        <div class="ticket-foot">
          <span class="meta"><?= $h($ep['meta']) ?></span>
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
      <h2 class="metal">Who let these guys have a mic?</h2>
      <p><strong>Duck</strong> tells the story. A guy from Chicagoland with thirty two years of playing guitar and standing on stages, watching people make really bad decisions that were awesome. He did the reading so you don't have to.</p>
      <p><strong>Pat</strong> hears it for the first time, right along with you, and says what everybody's thinking. Somebody has to.</p>
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
