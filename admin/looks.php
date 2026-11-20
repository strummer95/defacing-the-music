<?php
// Site looks: preview the two alternative designs (and the live site) side by side with the real pages.
// The mockups live in brand/concepts/. Nothing here changes the live site.
require_once __DIR__ . '/../lib/auth.php';
$user = dtm_require_admin();
$looks = [
  'backstage' => ['Backstage', '/brand/concepts/backstage/', 'Dark stage, warm spotlight on you two, ALL ACCESS pass for the latest episode, gaffer-taped setlist for what\'s coming up.'],
  'suspects'  => ['Suspects', '/brand/concepts/suspects/?p=oxblood', 'Ransom-note letters, the polaroid stamped SUSPECTS, the latest episode as a case file. Try the color schemes above the preview.'],
  'current'   => ['Current site', '/', 'What\'s live right now.'],
];
$colors = ['oxblood' => 'Oxblood', 'olive' => 'Olive', 'bone' => 'Bone', 'concrete' => 'Concrete', 'charcoal' => 'Charcoal', 'tobacco' => 'Tobacco'];
dtm_page_start('Site looks', $user, true);
?>
<section class="admin-card looks">
  <h1 class="metal">Site looks</h1>
  <p>The two alternatives for the website, live, next to what's up now. Just mockups: nothing here changes the real site.</p>
  <div class="look-tabs" role="tablist">
    <?php $i = 0; foreach ($looks as $k => [$name, $url, $desc]): ?>
    <button type="button" class="pull-btn bang look-tab<?= $i++ ? '' : ' on' ?>" data-k="<?= h($k) ?>" data-url="<?= h($url) ?>" data-desc="<?= h($desc) ?>"><?= h($name) ?></button>
    <?php endforeach; ?>
  </div>
  <p id="look-desc" class="look-desc"><?= h(reset($looks)[2]) ?></p>
  <div class="look-bar">
    <span class="look-size"><button type="button" class="linkish on" data-w="">Computer</button> · <button type="button" class="linkish" data-w="390">Phone</button></span>
    <span id="look-colors" class="look-colors" hidden>Colors:
      <?php foreach ($colors as $c => $label): ?><button type="button" class="linkish<?= $c === 'oxblood' ? ' on' : '' ?>" data-c="<?= h($c) ?>"><?= h($label) ?></button> <?php endforeach; ?>
    </span>
    <a id="look-open" class="look-open" href="<?= h(reset($looks)[1]) ?>" target="_blank" rel="noopener">Open full page</a>
  </div>
  <div class="look-frame"><iframe id="look-if" src="<?= h(reset($looks)[1]) ?>" title="Design preview" loading="lazy"></iframe></div>
  <p class="small">Side-by-side page with screenshots and talking points: <a href="/brand/concepts/" target="_blank" rel="noopener">all options</a>.</p>
</section>
<style>
.admin-main:has(.looks){max-width:1280px}
.look-tabs{display:flex;flex-wrap:wrap;gap:10px;margin:6px 0 10px}
.look-tab{opacity:.55}.look-tab.on{opacity:1}
.look-desc{min-height:1.5em}
.look-bar{display:flex;flex-wrap:wrap;gap:10px 22px;align-items:center;margin:0 0 12px;font-size:15px}
.look-bar .linkish{font-weight:600;opacity:.6}.look-bar .linkish.on{opacity:1;text-decoration:underline;font-weight:800}
.look-open{margin-left:auto}
.look-frame{border:4px solid var(--black);background:#111;display:flex;justify-content:center}
.look-frame iframe{display:block;width:100%;height:78vh;border:0;background:#fff;transition:width .2s}
</style>
<script>
(function(){
  var tabs=document.querySelectorAll('.look-tab'), ifr=document.getElementById('look-if'), open=document.getElementById('look-open'),
      desc=document.getElementById('look-desc'), cols=document.getElementById('look-colors'), base='', color='oxblood';
  function go(url){ifr.src=url;open.href=url;}
  function suspectsUrl(){return '/brand/concepts/suspects/?p='+color;}
  tabs.forEach(function(t){t.addEventListener('click',function(){
    tabs.forEach(function(x){x.classList.toggle('on',x===t)});
    desc.textContent=t.dataset.desc; cols.hidden=t.dataset.k!=='suspects';
    go(t.dataset.k==='suspects'?suspectsUrl():t.dataset.url);
  })});
  document.querySelectorAll('[data-w]').forEach(function(b){b.addEventListener('click',function(){
    document.querySelectorAll('[data-w]').forEach(function(x){x.classList.toggle('on',x===b)});
    ifr.style.width=b.dataset.w?b.dataset.w+'px':'100%';
  })});
  document.querySelectorAll('[data-c]').forEach(function(b){b.addEventListener('click',function(){
    document.querySelectorAll('[data-c]').forEach(function(x){x.classList.toggle('on',x===b)});
    color=b.dataset.c; go(suspectsUrl());
  })});
})();
</script>
<?php dtm_page_end();
