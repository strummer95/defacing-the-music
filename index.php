<?php
require __DIR__ . '/lib/https.php';
// Defacing The Music: home page ("Backstage" design, Duck's pick Nov 22, 2026; styles in assets/backstage.css).
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
<meta name="theme-color" content="#161518">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Defacing the Music">
<meta property="og:title" content="Defacing the Music">
<meta property="og:description" content="<?= $h($desc) ?>">
<meta property="og:url" content="https://defacingthemusic.com/">
<meta property="og:image" content="https://defacingthemusic.com/assets/og-image.png?v=2">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="https://defacingthemusic.com/assets/og-image.png?v=2">
<link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png?v=2">
<link rel="icon" type="image/png" sizes="192x192" href="assets/icon-192.png?v=2">
<link rel="apple-touch-icon" href="assets/apple-touch-icon.png?v=2">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&family=Permanent+Marker&family=Oswald:wght@500;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/backstage.css?v=<?= @filemtime(__DIR__ . '/assets/backstage.css') ?>">
</head>
<body>
<main>
<section id="top" class="stage">
  <header class="bs-header">
    <div class="wrap">
      <a href="#top" class="brand"><img src="assets/logo-192.png" alt="" width="40" height="40">Defacing the Music</a>
      <nav class="bs-nav" aria-label="Main">
        <a href="#latest">Latest</a>
        <a href="#episodes">Coming up</a>
        <a href="#hosts">The hosts</a>
        <a href="#list">Guest list</a>
        <a href="#top" class="cta">Listen</a>
      </nav>
    </div>
  </header>
  <div class="wrap">
    <div class="stage-copy">
      <span class="label">True stories. Terrible decisions.</span>
      <h1>Defacing<br><span>the Music</span></h1>
      <p class="lede">Rock and roll's worst nights, and who to blame for them.</p>
      <a class="btn" href="<?= $h($listen['YouTube']) ?>?sub_confirmation=1" rel="noopener" target="_blank">Subscribe on YouTube</a>
      <span class="also">Also on
        <?php $alts = array_filter(['Spotify' => $listen['Spotify'], 'Apple Podcasts' => $listen['Apple Podcasts']]); $k = 0;
        foreach ($alts as $name => $url): ?><?= $k++ ? ' and ' : ' ' ?><a href="<?= $h($url) ?>" rel="noopener" target="_blank"><?= $h($name) ?></a><?php endforeach; ?>
      </span>
    </div>
    <div class="stage-art">
      <img src="assets/hosts.webp?v=<?= @filemtime(__DIR__ . '/assets/hosts.webp') ?>" srcset="assets/hosts-800.webp?v=<?= @filemtime(__DIR__ . '/assets/hosts-800.webp') ?> 800w, assets/hosts.webp?v=<?= @filemtime(__DIR__ . '/assets/hosts.webp') ?> 1400w" sizes="(max-width: 820px) 92vw, 50vw" width="1400" height="1048" alt="Your hosts: Duck, slicked-back hair, aviator glasses and ear gauges, arms crossed, and Pat, bald with a full beard" fetchpriority="high">
    </div>
  </div>
</section>
<div class="stage-edge" aria-hidden="true"></div>

<section class="floor">
  <div class="wrap two">
    <div id="latest">
      <h2>Latest episode</h2>
      <div class="lanyard">
        <div class="pass"><span class="hole" aria-hidden="true"></span>
          <div class="top"><span class="aa">All access</span><span class="ep">EP <?= $h(str_pad($latest['num'], 2, '0', STR_PAD_LEFT)) ?></span></div>
          <div class="video">
            <iframe src="https://www.youtube-nocookie.com/embed/<?= $h($latest['yt']) ?>" title="Defacing the Music, Episode <?= $h($latest['num']) ?>: <?= $h($latest['title']) ?>" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
          </div>
          <h3><?= $h($latest['title']) ?></h3>
          <?php if ($latest['who'] !== ''): ?><p class="who"><?= $h($latest['who']) ?></p><?php endif; ?>
          <?php if ($latestBlurb !== ''): ?><p><?= $h($latestBlurb) ?></p><?php endif; ?>
          <a class="btn" href="https://youtu.be/<?= $h($latest['yt']) ?>" target="_blank" rel="noopener">Watch on YouTube</a>
        </div>
      </div>
    </div>
    <div id="episodes">
      <h2>Tonight's setlist</h2>
      <div class="setlist">
        <span class="gaff g1" aria-hidden="true"></span><span class="gaff g2" aria-hidden="true"></span><span class="gaff g3" aria-hidden="true"></span><span class="gaff g4" aria-hidden="true"></span>
        <div class="hd"><span>DTM</span><span>coming up</span></div>
        <ol>
          <?php foreach ($episodes as $ep): ?>
          <li><span class="song"><?= $h($ep['title']) ?></span><span class="meta"><?= $h($ep['meta']) ?> · <?= $h($ep['who']) ?></span><span class="blurb"><?= $h($ep['blurb']) ?></span></li>
          <?php endforeach; ?>
          <li><span class="encore">encore?? ask Pat</span></li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section id="hosts" class="dark">
  <div class="wrap hosts-copy">
    <h2>Who let these guys have a mic?</h2>
    <p><strong>Duck</strong> tells the story. A guy from Chicagoland with thirty two years of playing guitar and standing on stages, watching people make really bad decisions that were awesome. He did the reading so you don't have to.</p>
    <p><strong>Pat</strong> hears it for the first time, right along with you, and says what everybody's thinking. Somebody has to.</p>
    <span class="tape-note">Every story is real. Allegedly.</span>
  </div>
</section>

<section id="list" class="floor">
  <div class="wrap guest">
    <h2>Get on the guest list</h2>
    <p>New episodes in your inbox before security can stop us. No spam. We can barely manage a podcast.</p>
    <?php if ($signup === 'ok'): ?>
      <p class="notice ok" role="status">You're on the list. Security has been notified.</p>
    <?php elseif ($signup === 'bad'): ?>
      <p class="notice err" role="alert">That email looks fake. Try again.</p>
    <?php elseif ($signup === 'err'): ?>
      <p class="notice err" role="alert">Something broke on our end. Try again later.</p>
    <?php endif; ?>
    <form class="signup" method="post" action="signup.php">
      <label for="email">Your email</label>
      <input id="email" name="email" type="email" required maxlength="254" autocomplete="email" placeholder="you@example.com">
      <div class="hp" aria-hidden="true"><label for="website">Leave this empty</label><input id="website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
      <button type="submit" class="btn">Sign me up</button>
    </form>
  </div>
</section>
</main>

<footer class="bs-footer">
  <div class="wrap">
    <a href="#top" class="brand"><img src="assets/logo-192.png" alt="" width="40" height="40">Defacing the Music</a>
    <div class="socials">
      <span class="label">Follow the wreckage</span>
      <?php foreach ($social as $name => $url): if (!$url) continue; ?>
        <a class="btn sm ghost" href="<?= $h($url) ?>" rel="noopener" target="_blank"><?= $h($name) ?></a>
      <?php endforeach; ?>
    </div>
    <small>Due to unforeseen circumstances, all rights reserved. · defacingthemusic.com</small>
  </div>
</footer>
</body>
</html>
