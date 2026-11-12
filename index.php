<?php /* Defacing The Music: home page. */ ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Defacing The Music | A podcast about the craziest stories in music</title>
<meta name="description" content="Defacing The Music is a comedy podcast about the wildest, dumbest, most unhinged stories in music history.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<style>
:root{--purple:#2a1450;--purple2:#4b2289;--teal:#1fd1c1;--pink:#ff3ea5;--gold:#ffc83d;--cream:#fff4e0;--ink:#12081f}
*{box-sizing:border-box}html{scroll-behavior:smooth}
body{margin:0;background:var(--ink);color:var(--cream);font-family:Inter,system-ui,sans-serif;line-height:1.55}
a{color:var(--teal)}
.wrap{max-width:1100px;margin:0 auto;padding:0 1.25rem}
nav{position:sticky;top:0;z-index:5;background:rgba(18,8,31,.85);backdrop-filter:blur(8px);border-bottom:3px solid var(--pink)}
nav .wrap{display:flex;align-items:center;justify-content:space-between;height:64px}
.brand{font-family:Anton,Impact,sans-serif;font-size:1.5rem;letter-spacing:.04em;text-transform:uppercase;color:var(--gold);text-decoration:none}
.brand b{color:var(--pink);font-weight:400}
nav ul{list-style:none;display:flex;gap:1.5rem;margin:0;padding:0}
nav ul a{color:var(--cream);text-decoration:none;font-weight:600;text-transform:uppercase;font-size:.85rem;letter-spacing:.08em}
nav ul a:hover{color:var(--teal)}
.hero{position:relative;overflow:hidden;background:radial-gradient(circle at 25% 10%,var(--purple2),var(--purple) 55%,var(--ink));padding:6rem 0 7rem;text-align:center}
.hero:before{content:"";position:absolute;inset:0;background:repeating-linear-gradient(-12deg,transparent 0 40px,rgba(255,62,165,.06) 40px 80px)}
.hero .wrap{position:relative}
.tag{display:inline-block;background:var(--teal);color:var(--ink);font-weight:800;text-transform:uppercase;letter-spacing:.12em;font-size:.8rem;padding:.35rem .8rem;transform:rotate(-2deg);margin-bottom:1.5rem}
h1{font-family:Anton,Impact,sans-serif;font-weight:400;text-transform:uppercase;line-height:.9;margin:0;font-size:clamp(3.5rem,13vw,9.5rem);color:var(--pink);text-shadow:6px 6px 0 #000,10px 10px 0 var(--teal)}
h1 span{display:block;color:var(--gold);transform:rotate(-2deg)}
.hero p.lede{font-size:clamp(1.1rem,2.4vw,1.5rem);max-width:640px;margin:2rem auto 2.5rem;color:var(--cream)}
.btn{display:inline-block;font-weight:800;text-transform:uppercase;letter-spacing:.08em;text-decoration:none;padding:1rem 1.6rem;border:3px solid #000;box-shadow:5px 5px 0 #000;transition:transform .1s,box-shadow .1s}
.btn:hover{transform:translate(2px,2px);box-shadow:3px 3px 0 #000}
.btn.pink{background:var(--pink);color:#fff}.btn.gold{background:var(--gold);color:var(--ink);margin-left:.75rem}
section{padding:5rem 0}
h2{font-family:Anton,Impact,sans-serif;font-weight:400;text-transform:uppercase;font-size:clamp(2.2rem,6vw,3.8rem);margin:0 0 1rem;color:var(--gold);line-height:1}
h2 em{font-style:normal;color:var(--pink)}
.sub{color:#cdbfe6;max-width:640px;margin:0 0 2.5rem;font-size:1.1rem}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.5rem}
.card{background:var(--purple);border:3px solid #000;box-shadow:8px 8px 0 var(--teal);padding:1.75rem;position:relative}
.card:nth-child(2){box-shadow:8px 8px 0 var(--pink)}.card:nth-child(3){box-shadow:8px 8px 0 var(--gold)}
.card .yr{font-family:Anton,Impact,sans-serif;font-size:2.6rem;color:var(--teal);line-height:1}
.card:nth-child(2) .yr{color:var(--pink)}.card:nth-child(3) .yr{color:var(--gold)}
.card h3{margin:.5rem 0;font-size:1.3rem;text-transform:uppercase}
.card p{margin:0;color:#e2d7f5}
.soon{display:inline-block;margin-top:1rem;font-size:.75rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--ink);background:var(--cream);padding:.2rem .5rem}
.about{background:var(--purple)}
.about .wrap{display:grid;grid-template-columns:1.2fr 1fr;gap:3rem;align-items:center}
.rules{list-style:none;padding:0;margin:0}
.rules li{font-weight:800;text-transform:uppercase;letter-spacing:.04em;padding:.9rem 1rem;margin-bottom:.75rem;background:var(--ink);border-left:8px solid var(--pink)}
.rules li:nth-child(2){border-color:var(--teal)}.rules li:nth-child(3){border-color:var(--gold)}
.cta{text-align:center;background:linear-gradient(135deg,var(--pink),var(--purple2))}
.cta h2{color:#fff;text-shadow:4px 4px 0 #000}
.cta p{font-size:1.2rem;max-width:560px;margin:0 auto 2rem}
footer{padding:2rem 0;text-align:center;color:#9d8dbd;font-size:.9rem;border-top:3px solid var(--purple2)}
@media(max-width:760px){nav ul{display:none}.about .wrap{grid-template-columns:1fr}.btn.gold{margin:1rem 0 0}}
</style>
</head>
<body>
<nav><div class="wrap">
  <a class="brand" href="/">Defacing <b>The Music</b></a>
  <ul><li><a href="#stories">Stories</a></li><li><a href="#about">About</a></li><li><a href="#listen">Listen</a></li></ul>
</div></nav>

<header class="hero"><div class="wrap">
  <div class="tag">A comedy podcast</div>
  <h1>Defacing <span>The Music</span></h1>
  <p class="lede">The wildest, dumbest, most unhinged stories in music history. Riots, explosions, bad decisions, and the people who made them.</p>
  <a class="btn pink" href="#stories">See what's coming</a><a class="btn gold" href="#listen">Get notified</a>
</div></header>

<section id="stories"><div class="wrap">
  <h2>Stories on <em>the chopping block</em></h2>
  <p class="sub">Every episode digs into one moment where music went completely off the rails. A few we can't wait to tear into:</p>
  <div class="cards">
    <article class="card"><div class="yr">1999</div><h3>Woodstock '99</h3><p>Peace, love, $4 water, and a crowd that took "Break Stuff" as an instruction manual.</p><span class="soon">Coming soon</span></article>
    <article class="card"><div class="yr">1967</div><h3>Keith Moon vs. TV</h3><p>The Who on the Smothers Brothers, a drum kit packed with way too much explosive, and Pete Townshend's hearing.</p><span class="soon">Coming soon</span></article>
    <article class="card"><div class="yr">1982</div><h3>The Bat Incident</h3><p>A fan throws something on stage. A rock star assumes it's rubber. It was not rubber.</p><span class="soon">Coming soon</span></article>
  </div>
</div></section>

<section id="about" class="about"><div class="wrap">
  <div>
    <h2>What is <em>this?</em></h2>
    <p class="sub" style="color:var(--cream)">Defacing The Music is a podcast for people who love music and love it even more when it goes horribly wrong. We dig up the real stories behind the legends, then pick them apart with zero reverence.</p>
  </div>
  <ul class="rules">
    <li>Real stories, real chaos</li>
    <li>No sacred cows</li>
    <li>Turn it up to 11</li>
  </ul>
</div></section>

<section id="listen" class="cta"><div class="wrap">
  <h2>First episode dropping soon</h2>
  <p>We're warming up the amps. Check back here for episodes, or follow along as we get louder.</p>
</div></section>

<footer><div class="wrap">&copy; <?= date('Y') ?> Defacing The Music. All rights wronged.</div></footer>
</body>
</html>
