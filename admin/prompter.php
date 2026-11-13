<?php
// Teleprompter: black screen, big white text, yellow cues, dim headings. Admin only.
require __DIR__ . '/../lib/scripts.php';
dtm_require_admin();
$s = dtm_script((string)($_GET['id'] ?? ''));
if (!$s) { header('Location: scripts.php'); exit; }
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex">
<meta name="theme-color" content="#000000">
<title>EP <?= h($s['episode']) ?> · <?= h($s['title']) ?><?= !empty($s['subtitle']) ? ' · ' . h($s['subtitle']) : '' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;700&display=swap" rel="stylesheet">
<style>
:root{--scale:1;--w:88}
*{box-sizing:border-box}
html,body{margin:0;background:#000;color:#e8e8e8}
body{font-family:'Archivo',system-ui,sans-serif;-webkit-font-smoothing:antialiased}
#script{width:calc(var(--w) * 1vw);max-width:100%;margin:0 auto;padding:40vh 0 70vh;font-size:calc(clamp(26px,4.6vw,84px) * var(--scale));line-height:1.35;font-weight:500}
#script p{margin:0 0 .55em}
#script .gap{height:.4em}
#script h2{margin:1.4em 0 .5em;font-size:.55em;letter-spacing:.12em;text-transform:uppercase;color:#5a5a5a;font-weight:700}
#script .cue{color:#FFD21F}
#mirror-flag{display:none}
body.mirror #script{transform:scaleX(-1)}
/* reading line */
#line{position:fixed;left:0;right:0;top:38vh;height:0;border-top:2px solid rgba(255,210,31,.18);pointer-events:none}
body.noline #line{display:none}
/* tiny controls, dim, fade out while scrolling */
#bar{position:fixed;left:50%;bottom:max(10px,env(safe-area-inset-bottom));transform:translateX(-50%);display:flex;gap:6px;align-items:center;padding:6px;background:rgba(20,20,20,.85);border:1px solid #222;border-radius:12px;transition:opacity .4s;z-index:5}
body.playing.idle #bar{opacity:0}
#bar button,#bar a{font:600 15px/1 'Archivo',system-ui,sans-serif;color:#9a9a9a;background:#111;border:1px solid #2a2a2a;border-radius:8px;min-width:42px;height:40px;padding:0 10px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center}
#bar button:active{background:#1c1c1c}
#bar .val{color:#6a6a6a;font:600 13px/1 'Archivo',sans-serif;min-width:44px;text-align:center}
#play.on{color:#FFD21F;border-color:#4a3d00}
#help{position:fixed;top:10px;right:12px;color:#3a3a3a;font:500 12px/1.4 'Archivo',sans-serif;text-align:right;transition:opacity .4s}
body.playing #help{opacity:0}
@media (max-width:600px){#bar .hide-sm{display:none}#help{display:none}}
</style>
</head>
<body>
<div id="line"></div>
<div id="help">Space: play/pause · ↑↓: speed · + −: size · [ ]: width · F: full screen · M: mirror</div>
<main id="script"><?= dtm_script_html((string)$s['body']) ?></main>
<nav id="bar" aria-label="Teleprompter controls">
  <a href="scripts.php" title="Back to scripts">✕</a>
  <button id="smaller" title="Smaller text (−)">A−</button>
  <button id="bigger" title="Bigger text (+)">A+</button>
  <button id="narrower" title="Narrower column ([)">⇥⇤</button>
  <button id="wider" title="Wider column (])">⇤⇥</button>
  <button id="slower" title="Slower (↓)">−</button>
  <span class="val" id="speedv"></span>
  <button id="faster" title="Faster (↑)">+</button>
  <button id="play" title="Play / pause (space)">▶</button>
  <button id="top" class="hide-sm" title="Back to top">⤒</button>
  <button id="mirror" class="hide-sm" title="Mirror for a teleprompter glass (M)">⇋</button>
  <button id="full" title="Full screen (F)">⛶</button>
</nav>
<script>
(function(){
  var KEY='dtm-prompter', st={scale:1,speed:40,mirror:false,w:88};
  try{Object.assign(st,JSON.parse(localStorage.getItem(KEY)||'{}'))}catch(e){}
  var root=document.documentElement, body=document.body, playing=false, last=0, acc=0, idleT;
  function save(){try{localStorage.setItem(KEY,JSON.stringify(st))}catch(e){}}
  function apply(){
    st.scale=Math.min(3,Math.max(.4,Math.round(st.scale*100)/100));
    st.speed=Math.min(400,Math.max(5,st.speed));
    st.w=Math.min(98,Math.max(40,st.w||88));
    root.style.setProperty('--w',st.w);
    root.style.setProperty('--scale',st.scale);
    document.getElementById('speedv').textContent=st.speed;
    body.classList.toggle('mirror',!!st.mirror);
    save();
  }
  function tick(t){
    if(!playing) return;
    if(last){acc+=st.speed*(t-last)/1000; var px=Math.floor(acc); if(px){window.scrollBy(0,px);acc-=px;}}
    last=t;
    if(window.innerHeight+window.scrollY>=document.body.scrollHeight-2){toggle(false);return;}
    requestAnimationFrame(tick);
  }
  function toggle(on){
    playing=on===undefined?!playing:on; last=0; acc=0;
    body.classList.toggle('playing',playing);
    var b=document.getElementById('play'); b.textContent=playing?'❚❚':'▶'; b.classList.toggle('on',playing);
    if(playing) requestAnimationFrame(tick);
    wake();
  }
  function wake(){body.classList.remove('idle');clearTimeout(idleT);idleT=setTimeout(function(){body.classList.add('idle')},2500);}
  function full(){
    var d=document; if(!d.fullscreenElement&&!d.webkitFullscreenElement){(root.requestFullscreen||root.webkitRequestFullscreen||function(){}).call(root);}
    else{(d.exitFullscreen||d.webkitExitFullscreen).call(d);}
  }
  function on(id,fn){document.getElementById(id).addEventListener('click',function(e){e.preventDefault();fn();wake();});}
  on('bigger',function(){st.scale*=1.1;apply()});
  on('smaller',function(){st.scale/=1.1;apply()});
  on('faster',function(){st.speed+=st.speed<60?5:10;apply()});
  on('slower',function(){st.speed-=st.speed<=60?5:10;apply()});
  on('narrower',function(){st.w-=6;apply()});
  on('wider',function(){st.w+=6;apply()});
  on('play',function(){toggle()});
  on('top',function(){window.scrollTo(0,0)});
  on('mirror',function(){st.mirror=!st.mirror;apply()});
  on('full',full);
  document.addEventListener('keydown',function(e){
    if(e.target.tagName==='INPUT'||e.target.tagName==='TEXTAREA') return;
    var k=e.key;
    if(k===' '){e.preventDefault();toggle();}
    else if(k==='ArrowUp'){e.preventDefault();st.speed+=st.speed<60?5:10;apply();}
    else if(k==='ArrowDown'){e.preventDefault();st.speed-=st.speed<=60?5:10;apply();}
    else if(k==='+'||k==='='){st.scale*=1.1;apply();}
    else if(k==='-'||k==='_'){st.scale/=1.1;apply();}
    else if(k==='f'||k==='F'){full();}
    else if(k==='m'||k==='M'){st.mirror=!st.mirror;apply();}
    else if(k==='['){st.w-=6;apply();}
    else if(k===']'){st.w+=6;apply();}
    else if(k==='Home'){window.scrollTo(0,0);}
    wake();
  });
  ['mousemove','touchstart','wheel'].forEach(function(ev){document.addEventListener(ev,wake,{passive:true})});
  // tap the text to play/pause (handy on a phone or tablet)
  document.getElementById('script').addEventListener('click',function(){toggle();});
  apply(); wake();
})();
</script>
</body>
</html>
