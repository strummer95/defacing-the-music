<?php
// Public sponsor page / one-page media kit: defacingthemusic.com/sponsor/
// Numbers and prices only show once Duck fills them in (admin > Sponsors). Print = a one-page PDF to send brands.
require __DIR__ . '/../lib/sponsor.php';
$cfg = dtm_sponsor_cfg();
$sent = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $f = fn($k, $n) => trim(mb_substr((string)($_POST[$k] ?? ''), 0, $n));
  if ($f('website', 10) !== '') { header('Location: /sponsor/?sent=ok#talk'); exit; } // bot trap
  $name = $f('name', 100); $co = $f('company', 120); $email = strtolower($f('email', 254)); $msg = $f('message', 3000);
  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $msg === '') { header('Location: /sponsor/?sent=bad#talk'); exit; }
  try {
    dtm_json_update('sponsor-inquiries.json', function ($l) use ($name, $co, $email, $msg) {
      if (count($l) >= 500) array_shift($l);
      $l[] = ['id' => bin2hex(random_bytes(6)), 'at' => date('c'), 'name' => $name, 'company' => $co, 'email' => $email, 'message' => $msg, 'done' => false];
      return $l;
    });
  } catch (Throwable $e) { header('Location: /sponsor/?sent=err#talk'); exit; }
  header('Location: /sponsor/?sent=ok#talk'); exit;
}
$sent = (string)($_GET['sent'] ?? '');
$eps = dtm_sponsor_episodes_out();
$stats = array_filter(['Listens per episode' => $cfg['listens'], 'YouTube subscribers' => $cfg['youtube_subs'], 'Social followers' => $cfg['followers'], 'Episodes out' => $eps ? (string)$eps : '']);
$spots = [
  ['Pre-roll', 'About 30 seconds before the story starts. Duck reads it himself.', $cfg['rate_pre']],
  ['Mid-roll', 'About 60 seconds in the middle of the story, when people are hooked. The best spot.', $cfg['rate_mid']],
  ['Video integration', 'The read plus your product or logo on screen in the YouTube episode.', $cfg['rate_video']],
  ['Shorts mention', 'A quick shout-out on a Short, Reel or TikTok clip.', $cfg['rate_short']],
];
$email = filter_var($cfg['email'], FILTER_VALIDATE_EMAIL) ? $cfg['email'] : '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sponsor the show | Defacing the Music</title>
<meta name="description" content="Advertise on Defacing the Music, a comedy podcast about the craziest true stories in music history. Host-read ads, YouTube integrations and Shorts.">
<link rel="canonical" href="https://defacingthemusic.com/sponsor/">
<meta name="theme-color" content="#161518">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png?v=2">
<link rel="apple-touch-icon" href="/assets/apple-touch-icon.png?v=2">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&family=Oswald:wght@500;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/backstage.css?v=<?= @filemtime(DTM_ROOT . '/assets/backstage.css') ?>">
</head>
<body class="sponsor-page">
<header class="bs-header"><div class="wrap">
  <a href="/" class="brand"><img src="/assets/logo-192.png" alt="" width="40" height="40">Defacing the Music</a>
  <nav class="bs-nav" aria-label="Main"><a href="/">The show</a><a href="#talk" class="cta">Let's talk</a></nav>
</div></header>
<main class="wrap kit">
  <section class="kit-hero">
    <span class="label">Sponsor the show</span>
    <h1>Put your brand<br><span>on the wreckage.</span></h1>
    <p class="kit-lede">Defacing the Music is a comedy podcast about the craziest true stories in music history: the riots, the stunts, the terrible decisions, and the one guy who thought it was a good idea. Duck tells the story, Pat hears it for the first time, and in every episode we name That Guy.</p>
    <p class="kit-btns"><a class="btn" href="#talk">Let's talk</a> <button type="button" class="btn ghost" onclick="window.print()">Save as PDF</button></p>
  </section>

  <section class="kit-grid">
    <div class="kit-card">
      <h2>The numbers</h2>
      <?php if ($stats): ?>
        <div class="kit-stats"><?php foreach ($stats as $k => $v): ?><div><b><?= h($v) ?></b><span><?= h($k) ?></span></div><?php endforeach; ?></div>
      <?php endif; ?>
      <?php if (!$cfg['listens']): ?>
        <p><b>Brand new, launching November 2026.</b> We're not going to pretend we have big numbers yet. What we have is a show built to grow: a full episode on YouTube and every podcast app, plus several Shorts, Reels and TikToks cut from every episode.</p>
        <p>Early sponsors get launch pricing, locked in while the audience grows.</p>
      <?php endif; ?>
    </div>
    <div class="kit-card">
      <h2>Who's listening</h2>
      <ul>
        <li>Music fans who know the hits and want the story behind the chaos</li>
        <li>Guitar players and gear people (Duck's been playing for 32 years)</li>
        <li>Rock, metal and music-history nerds</li>
        <li>Comedy and true-story podcast fans</li>
      </ul>
      <p class="small">Based near Chicago. Released everywhere: YouTube, Spotify, Apple Podcasts.</p>
    </div>
  </section>

  <section class="kit-card">
    <h2>Ad spots</h2>
    <div class="kit-spots">
      <?php foreach ($spots as [$name, $desc, $price]): ?>
      <div class="spot"><h3><?= h($name) ?></h3><p><?= h($desc) ?></p><span class="price"><?= $price !== '' ? h($price) : 'Ask us' ?></span></div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="kit-card">
    <h2>How we do ads</h2>
    <ul>
      <li><b>Host-read, in our own voice.</b> No canned spots read by a robot. That's why they work.</li>
      <li><b>Only stuff we'd actually use.</b> If it's not a fit for the show, we'll say so.</li>
      <li><b>Clearly an ad.</b> Our listeners know when we're being paid, and we keep it that way.</li>
      <li><b>No politics, no hot takes.</b> The show stays out of polarizing stuff, so your brand does too.</li>
    </ul>
  </section>

  <section class="kit-card" id="talk">
    <h2>Let's talk</h2>
    <?php if ($sent === 'ok'): ?><p class="notice ok" role="status">Got it. We'll get back to you soon.</p>
    <?php elseif ($sent === 'bad'): ?><p class="notice err" role="alert">We need a real email and a message.</p>
    <?php elseif ($sent === 'err'): ?><p class="notice err" role="alert">Something broke on our end. Try again later.</p><?php endif; ?>
    <?php if ($email): ?><p>Email <a href="mailto:<?= h($email) ?>"><?= h($email) ?></a>, or use the form.</p><?php endif; ?>
    <form method="post" class="kit-form">
      <label>Your name<input name="name" type="text" maxlength="100" autocomplete="name"></label>
      <label>Company<input name="company" type="text" maxlength="120" autocomplete="organization"></label>
      <label>Email<input name="email" type="email" maxlength="254" required autocomplete="email"></label>
      <label class="full">What do you have in mind?<textarea name="message" rows="5" maxlength="3000" required placeholder="Product, budget, timing, anything."></textarea></label>
      <div class="hp" aria-hidden="true"><label>Leave this empty<input name="website" type="text" tabindex="-1" autocomplete="off"></label></div>
      <button type="submit" class="btn">Send it</button>
    </form>
  </section>
</main>
<footer class="bs-footer"><div class="wrap">
  <a href="/" class="brand"><img src="/assets/logo-192.png" alt="" width="40" height="40">Defacing the Music</a>
  <small>© <?= date('Y') ?> Duck and Rabbit LLC · defacingthemusic.com/sponsor</small>
</div></footer>
</body>
</html>
