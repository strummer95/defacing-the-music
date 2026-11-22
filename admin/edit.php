<?php
// Edit: mark up an episode transcript (CUT / COLD / CLIP / notes) for the editing session on Duck's PC.
require __DIR__ . '/../lib/edits.php';
$user = dtm_require_admin();
$id = preg_replace('/[^a-f0-9]/', '', (string)($_GET['id'] ?? $_POST['id'] ?? ''));
$json = fn($d, $code = 200) => (http_response_code($code) || true) && exit(json_encode($d));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');
  $ajax = in_array($action, ['mark', 'note', 'words'], true);
  if ($ajax) {
    header('Content-Type: application/json');
    dtm_session();
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) $json(['error' => 'Page expired. Reload.'], 400);
  } else dtm_check_csrf();
  try {
    if ($action === 'create') {
      $name = trim(mb_substr((string)($_POST['name'] ?? ''), 0, 80)) ?: 'New episode';
      $nid = bin2hex(random_bytes(6));
      dtm_edit_update($nid, fn($p) => ['id' => $nid, 'name' => $name, 'created' => date('c'), 'token' => bin2hex(random_bytes(20)), 'parts' => [], 'marks' => []]);
      header("Location: edit.php?id=$nid"); exit;
    }
    if (!dtm_edit($id)) { http_response_code(404); exit('No such edit.'); }
    if ($action === 'addpart') {
      $f = $_FILES['transcript'] ?? null;
      if (!$f || ($f['error'] ?? 1) !== UPLOAD_ERR_OK || $f['size'] > 5 * 1048576) { header("Location: edit.php?id=$id&err=file"); exit; }
      $lines = dtm_edit_parse((string)file_get_contents($f['tmp_name']));
      if (!array_filter($lines, fn($l) => $l['ts'] !== '')) { header("Location: edit.php?id=$id&err=format"); exit; }
      $pname = trim(mb_substr((string)($_POST['partname'] ?? ''), 0, 60)) ?: pathinfo((string)$f['name'], PATHINFO_FILENAME);
      dtm_edit_update($id, function ($p) use ($lines, $pname, $f) { $p['parts'][] = ['name' => $pname, 'file' => basename((string)$f['name']), 'audio' => '', 'lines' => $lines]; return $p; });
      header("Location: edit.php?id=$id&added=1"); exit;
    }
    if ($action === 'delpart') {
      $pi = (int)($_POST['part'] ?? -1);
      dtm_edit_update($id, function ($p) use ($pi) {
        if (!isset($p['parts'][$pi])) return $p;
        array_splice($p['parts'], $pi, 1);
        $marks = [];
        foreach ($p['marks'] as $k => $v) { [$a, $b] = array_map('intval', explode(':', $k)); if ($a === $pi) continue; $marks[($a > $pi ? $a - 1 : $a) . ":$b"] = $v; }
        $p['marks'] = $marks;
        return $p;
      });
      header("Location: edit.php?id=$id"); exit;
    }
    if ($action === 'audio') {
      $pi = (int)($_POST['part'] ?? -1); $up = (string)($_POST['upload'] ?? '');
      if ($up !== '' && !dtm_upload($up)) $up = '';
      dtm_edit_update($id, function ($p) use ($pi, $up) { if (isset($p['parts'][$pi])) $p['parts'][$pi]['audio'] = $up; return $p; });
      header("Location: edit.php?id=$id#part$pi"); exit;
    }
    if ($action === 'newlink') { dtm_edit_update($id, function ($p) { $p['token'] = bin2hex(random_bytes(20)); return $p; }); header("Location: edit.php?id=$id&newlink=1"); exit; }
    if ($action === 'delete') { dtm_edit_update($id, fn($p) => null); header('Location: edit.php?deleted=1'); exit; }
    if ($action === 'mark') {
      // items: "part:line,part:line,..." (one selection can run across the part break)
      $items = [];
      foreach (array_slice(explode(',', (string)($_POST['items'] ?? '')), 0, 5000) as $it) if (preg_match('/^(\d+):(\d+)$/', $it, $mm)) $items[] = [(int)$mm[1], (int)$mm[2]];
      $m = (string)($_POST['m'] ?? '');
      $clip = trim(mb_substr((string)($_POST['clip'] ?? ''), 0, 40));
      if (!in_array($m, array_merge(DTM_EDIT_MARKS, ['clear']), true)) $json(['error' => 'Bad mark'], 400);
      $p = dtm_edit_update($id, function ($p) use ($items, $m, $clip) {
        foreach ($items as [$pi, $i]) {
          if (!isset($p['parts'][$pi]['lines'][$i])) continue;
          $k = "$pi:$i"; $old = $p['marks'][$k] ?? [];
          if ($m === 'clear') { if (trim((string)($old['note'] ?? '')) === '') unset($p['marks'][$k]); else $p['marks'][$k] = ['note' => $old['note']]; continue; }
          // a line can be in a clip AND be cut or cold open, so the clip is kept separately from cut/cold
          if ($m === 'clip') { $old['clip'] = $clip !== '' ? $clip : 'clip'; } else { $old['m'] = $m; }
          $p['marks'][$k] = $old;
        }
        return $p;
      });
      $json(['ok' => true, 'counts' => dtm_edit_counts($p)]);
    }
    if ($action === 'words') {
      $pi = (int)($_POST['part'] ?? -1); $i = (int)($_POST['line'] ?? -1);
      $w = array_values(array_unique(array_map('intval', array_filter(explode(',', (string)($_POST['words'] ?? '')), 'is_numeric'))));
      sort($w);
      $p = dtm_edit_update($id, function ($p) use ($pi, $i, $w) {
        if (!isset($p['parts'][$pi]['lines'][$i])) return $p;
        $n = count(dtm_edit_words($p['parts'][$pi]['lines'][$i]['text']));
        $w = array_values(array_filter($w, fn($x) => $x >= 0 && $x < $n));
        $k = "$pi:$i"; $mk = $p['marks'][$k] ?? [];
        if ($w) $mk['words'] = $w; else unset($mk['words']);
        if ($mk) $p['marks'][$k] = $mk; else unset($p['marks'][$k]);
        return $p;
      });
      $json(['ok' => true, 'counts' => dtm_edit_counts($p)]);
    }
    if ($action === 'note') {
      $pi = (int)($_POST['part'] ?? -1); $i = (int)($_POST['line'] ?? -1); $note = trim(mb_substr((string)($_POST['note'] ?? ''), 0, 500));
      $p = dtm_edit_update($id, function ($p) use ($pi, $i, $note) {
        if (!isset($p['parts'][$pi]['lines'][$i])) return $p;
        $k = "$pi:$i"; $mk = $p['marks'][$k] ?? [];
        if ($note === '') unset($mk['note']); else $mk['note'] = $note;
        if ($mk) $p['marks'][$k] = $mk; else unset($p['marks'][$k]);
        return $p;
      });
      $json(['ok' => true, 'counts' => dtm_edit_counts($p)]);
    }
  } catch (Throwable $e) {
    if ($ajax) $json(['error' => 'Could not save: ' . $e->getMessage()], 500);
    http_response_code(500); exit('Could not save: ' . h($e->getMessage()));
  }
  header('Location: edit.php' . ($id ? "?id=$id" : '')); exit;
}

$p = $id ? dtm_edit($id) : null;
dtm_page_start('Edit', $user, true);
if (!$p): $all = dtm_edits(); ?>
<section class="admin-card">
  <h1 class="metal">Edit</h1>
  <p>Mark up an episode's transcript: what to cut, the cold open, and the clips. The editing session on your PC picks up your marks from a private link.</p>
  <?php if (isset($_GET['deleted'])): ?><p class="notice ok bang" role="status">Deleted.</p><?php endif; ?>
  <?php if ($all): ?><ul class="up-list">
    <?php foreach ($all as $e): $c = dtm_edit_counts($e); ?>
    <li><div class="up-name"><strong><a href="edit.php?id=<?= h($e['id']) ?>"><?= h($e['name']) ?></a></strong>
      <span class="small"><?= count($e['parts']) ?> part(s) · <?= $c['lines'] ?> lines · <?= $c['cut'] ?> cut · <?= $c['cold'] ?> cold open · <?= $c['clip'] ?> clip</span></div></li>
    <?php endforeach; ?></ul><?php endif; ?>
  <form method="post" class="admin-form"><?= dtm_csrf_field() ?><input type="hidden" name="action" value="create">
    <label for="name" class="bang">New edit</label>
    <input id="name" name="name" type="text" maxlength="80" placeholder="Episode 1: Ozzy" required>
    <div class="btn-row"><button class="pull-btn bang">Start it</button></div>
  </form>
</section>
<?php dtm_page_end(); exit; endif;

$c = dtm_edit_counts($p);
$link = dtm_site_base() . '/admin/edit-export.php?id=' . $p['id'] . '&t=' . $p['token'];
$avs = array_values(array_filter(dtm_uploads(), fn($u) => in_array($u['ext'], ['mp3','m4a','wav','aac','flac','ogg','mp4','m4v','mov','mkv','webm'], true)));
$marksJs = $p['marks'] ?: new stdClass();
?>
<section class="admin-card">
  <p class="small"><a href="edit.php">All edits</a></p>
  <h1 class="metal"><?= h($p['name']) ?></h1>
  <?php if (isset($_GET['added'])): ?><p class="notice ok bang" role="status">Transcript added.</p><?php endif; ?>
  <?php if (isset($_GET['newlink'])): ?><p class="notice ok bang" role="status">New link made. The old one stopped working.</p><?php endif; ?>
  <?php if (($_GET['err'] ?? '') === 'file'): ?><p class="notice err bang" role="alert">Pick a transcript file (under 5 MB).</p><?php endif; ?>
  <?php if (($_GET['err'] ?? '') === 'format'): ?><p class="notice err bang" role="alert">That file has no lines like "[12:34] DUCK: ...". Is it the right transcript?</p><?php endif; ?>
  <p id="counts" class="small"><?= $c['lines'] ?> lines · <b><?= $c['cut'] ?></b> cut · <b><?= $c['cold'] ?></b> cold open · <b><?= $c['clip'] ?></b> clip · <b><?= $c['note'] ?></b> notes · <b><?= $c['words'] ?></b> words cut</p>
  <div class="btn-row">
    <button type="button" class="pull-btn bang copy" data-link="<?= h($link) ?>">Copy link for Claude</button>
    <a class="pull-btn bang link-btn" href="edit-export.php?id=<?= h($p['id']) ?>&amp;dl=1">Download marked transcript</a>
  </div>
  <div class="small" style="margin-top:14px">Paste the link to the editing session on your PC. It always has your latest marks. <form method="post" style="display:inline"><?= dtm_csrf_field() ?><input type="hidden" name="id" value="<?= h($p['id']) ?>"><input type="hidden" name="action" value="newlink"><button class="linkish">New link</button></form></div>
  <details class="cut-more"<?= $p['parts'] ? '' : ' open' ?>>
    <summary class="bang">Add a transcript part</summary>
    <form method="post" enctype="multipart/form-data" class="admin-form"><?= dtm_csrf_field() ?><input type="hidden" name="id" value="<?= h($p['id']) ?>"><input type="hidden" name="action" value="addpart">
      <label for="transcript" class="bang">Transcript file (.md or .txt)</label>
      <input id="transcript" name="transcript" type="file" accept=".md,.txt,text/plain,text/markdown" required>
      <label for="partname" class="bang">Part name</label>
      <input id="partname" name="partname" type="text" maxlength="60" placeholder="Part <?= count($p['parts']) + 1 ?>">
      <div class="btn-row"><button class="pull-btn bang">Add it</button></div>
    </form>
  </details>
</section>

<?php if ($p['parts']): ?>
<section class="admin-card">
  <h2 class="bang">Parts</h2>
  <p class="small">The episode is one video. Each part is one recording (its own camera files); the transcript below runs straight through, with a line where each new part starts.</p>
  <ul class="up-list">
  <?php foreach ($p['parts'] as $pi => $part): ?>
    <li><div class="up-name"><strong>Part <?= $pi + 1 ?>: <?= h($part['name']) ?></strong> <span class="small"><?= count($part['lines']) ?> lines · <?= h($part['file']) ?></span></div>
      <div class="up-acts">
        <form method="post" class="edit-audio"><?= dtm_csrf_field() ?><input type="hidden" name="id" value="<?= h($p['id']) ?>"><input type="hidden" name="action" value="audio"><input type="hidden" name="part" value="<?= $pi ?>">
          <label class="small">Audio: <select name="upload" onchange="this.form.submit()"><option value="">None</option>
            <?php foreach ($avs as $u): ?><option value="<?= h($u['id']) ?>"<?= $u['id'] === $part['audio'] ? ' selected' : '' ?>><?= h($u['name']) ?></option><?php endforeach; ?>
          </select></label></form>
        <form method="post" onsubmit="return confirm('Remove part <?= $pi + 1 ?> and its marks?')"><?= dtm_csrf_field() ?><input type="hidden" name="id" value="<?= h($p['id']) ?>"><input type="hidden" name="action" value="delpart"><input type="hidden" name="part" value="<?= $pi ?>"><button class="linkish danger">Remove</button></form>
      </div></li>
  <?php endforeach; ?>
  </ul>
  <p class="small">Audio is optional: upload an audio export on the Uploads page, pick it here, then tap a timestamp to hear that spot.</p>
</section>

<section class="admin-card edit-part">
  <h2 class="bang">Transcript</h2>
  <p class="small"><b>Tap a line</b> to select it, <b>tap another</b> to select everything in between (it can cross a part line). Then pick <b>Cut</b>, <b>Cold open</b>, <b>Clip</b> or <b>Note</b> in the bar at the bottom. To cut <b>single words</b>, select one line, tap <b>Words</b>, tap the words to cut, then <b>Done</b>.</p>
  <?php foreach ($p['parts'] as $pi => $part): $au = $part['audio'] !== '' ? dtm_upload($part['audio']) : null;
    if ($au): ?><audio class="edit-player" data-part="<?= $pi ?>" preload="none" src="file.php?id=<?= h($au['id']) ?>"></audio><?php endif; endforeach; ?>
  <div id="nowplaying" class="small" hidden><button type="button" class="linkish" id="stopbtn">Stop</button> <span id="nptxt"></span></div>
  <ol class="tx">
    <?php foreach ($p['parts'] as $pi => $part): ?>
    <li class="part-break" aria-hidden="<?= $pi === 0 ? 'true' : 'false' ?>"><?= $pi === 0 ? 'Part 1 starts' : 'Part ' . ($pi + 1) . ' starts here (new recording)' ?>: <?= h($part['name']) ?></li>
    <?php foreach ($part['lines'] as $ln): ?>
    <li data-p="<?= $pi ?>" data-i="<?= $ln['i'] ?>" data-t="<?= h((string)$ln['t']) ?>" class="<?= $ln['flag'] ? 'flag' : '' ?><?= $ln['who'] !== '' ? ' who-' . h(strtolower(preg_replace('/[^A-Za-z]/', '', $ln['who']))) : '' ?>">
      <?php if ($ln['ts'] !== ''): ?><button type="button" class="ts" title="Part <?= $pi + 1 ?>, <?= h($ln['ts']) ?>"><?= h($ln['ts']) ?></button><?php endif; ?>
      <?php if ($ln['who'] !== ''): ?><b class="who"><?= h($ln['who']) ?></b><?php endif; ?>
      <span class="tx-t"><?= h($ln['text']) ?></span><span class="tag"></span><em class="nt"></em>
    </li>
    <?php endforeach; endforeach; ?>
  </ol>
</section>
<?php endif; ?>

<section class="admin-card"><form method="post" onsubmit="return confirm('Delete this whole edit and all its marks?')"><?= dtm_csrf_field() ?><input type="hidden" name="id" value="<?= h($p['id']) ?>"><input type="hidden" name="action" value="delete"><button class="linkish danger">Delete this edit</button></form></section>

<div class="edit-bar" id="bar" hidden>
  <span id="selinfo" class="small"></span>
  <button type="button" data-m="cut" class="pull-btn bang">Cut</button>
  <button type="button" data-m="cold" class="pull-btn bang">Cold open</button>
  <button type="button" data-m="clip" class="pull-btn bang">Clip</button>
  <button type="button" data-m="words" class="pull-btn bang" id="wordsbtn">Words</button>
  <button type="button" data-m="note" class="pull-btn bang">Note</button>
  <button type="button" data-m="clear" class="pull-btn bang">Clear</button>
  <button type="button" data-m="done" class="linkish">Done</button>
</div>

<style>
.admin-main:has(.edit-part){max-width:1100px}
.tx{list-style:none;margin:10px 0 16px;padding:0;font-size:17px;line-height:1.45}
.tx li{display:flex;flex-wrap:wrap;gap:4px 10px;align-items:baseline;padding:7px 10px;border-left:6px solid transparent;cursor:pointer;border-bottom:1px solid rgba(0,0,0,.08)}
.tx li:hover{background:rgba(0,0,0,.04)}
.tx li.flag{color:#7a6f5f;font-size:14px;font-style:italic}
.tx .ts{font:600 13px ui-monospace,Menlo,monospace;background:#111;color:#FFD21F;border:0;padding:2px 6px;cursor:pointer}
.tx .who{font:15px Bangers,sans-serif;letter-spacing:.05em;min-width:44px}
.tx .who-pat .who,.tx li.who-pat .who{color:#1a5fb4}
.tx .tx-t{flex:1 1 320px}
.tx .tag{font:13px Bangers,sans-serif;letter-spacing:.05em}
.tx .nt{flex-basis:100%;font-size:14px;color:#1a5fb4}
.tx .nt:empty{display:none}
.tx .w{cursor:pointer;border-radius:3px;padding:0 1px}
.tx li.wedit .w{outline:1px dashed #999;margin:0 1px}
.tx li.wedit .w:hover{background:#ffe1dc}
.tx .w.wc{color:#E4251B;text-decoration:line-through;background:#fde3df}
.tx li.sel{background:#fff3b0;border-left-color:#111}
.tx li.m-cut{border-left-color:#E4251B}.tx li.m-cut .tx-t{text-decoration:line-through;color:#8a7f70}
.tx li.m-cut .tag::before{content:"CUT";color:#E4251B}
.tx li.m-cold{border-left-color:#c77d00;background:#fff4dc}.tx li.m-cold .tag::before{content:"COLD OPEN";color:#c77d00}
.tx li.m-clip{box-shadow:inset -6px 0 0 #2a7d3a}.tx li.m-clip:not(.m-cut):not(.m-cold){background:#eaf6ea}.tx .tag .cl{color:#2a7d3a;margin-left:6px}
.edit-player{display:none}
.tx li.part-break{cursor:default;display:block;background:#111;color:#FFD21F;font:20px Bangers,sans-serif;letter-spacing:.05em;padding:8px 12px;margin:14px 0 4px;border:0}
.tx li.part-break:first-child{margin-top:0}
.edit-audio{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.edit-bar[hidden]{display:none}
.edit-bar{position:fixed;left:0;right:0;bottom:0;z-index:50;display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:center;padding:10px;background:#111;border-top:4px solid #FFD21F}
.edit-bar .pull-btn{min-height:40px;font-size:18px;padding:4px 12px}
.edit-bar #selinfo,.edit-bar .linkish{color:#F2E8D5}
body{padding-bottom:90px}
</style>
<script>
(function(){
  var ID=<?= json_encode($p['id']) ?>, CSRF=<?= json_encode(dtm_csrf()) ?>, MARKS=<?= json_encode($marksJs) ?>;
  var bar=document.getElementById('bar'), info=document.getElementById('selinfo'), counts=document.getElementById('counts');
  var ALL=Array.prototype.slice.call(document.querySelectorAll('.tx li[data-i]')); // every line, in episode order
  ALL.forEach(function(li,n){li.dataset.n=n;});
  var sel={a:null,b:null};
  function key(li){return li.dataset.p+':'+li.dataset.i;}
  function paint(li){
    var mk=MARKS[key(li)]||{};
    li.classList.remove('m-cut','m-cold','m-clip');
    if(mk.m) li.classList.add('m-'+mk.m);
    var tag=li.querySelector('.tag'); tag.innerHTML='';
    if(mk.clip){li.classList.add('m-clip');var cl=document.createElement('span');cl.className='cl';cl.textContent='CLIP: '+mk.clip;tag.appendChild(cl);}
    li.querySelector('.nt').textContent=mk.note?('Note: '+mk.note):'';
    paintWords(li);
  }
  function words(li){ if(!li.dataset.txt) li.dataset.txt=li.querySelector('.tx-t').textContent; return li.dataset.txt.split(/\s+/).filter(Boolean); }
  function paintWords(li){
    var mk=MARKS[key(li)]||{}, cut=mk.words||[], t=li.querySelector('.tx-t'), ws=words(li);
    if(!cut.length && !li.classList.contains('wedit')){ t.textContent=li.dataset.txt; return; }
    t.innerHTML='';
    ws.forEach(function(w,n){ var sp=document.createElement('span'); sp.className='w'+(cut.indexOf(n)>=0?' wc':''); sp.dataset.w=n; sp.textContent=w; t.appendChild(sp); t.appendChild(document.createTextNode(' ')); });
  }
  ALL.forEach(paint);
  var wline=null; // the line being word-edited
  function endWords(){ if(!wline) return; wline.classList.remove('wedit'); paintWords(wline); wline=null; document.getElementById('wordsbtn').textContent='Words'; }
  function selected(){if(sel.a===null)return[];var lo=Math.min(sel.a,sel.b),hi=Math.max(sel.a,sel.b);return ALL.slice(lo,hi+1);}
  function show(){
    document.querySelectorAll('.tx li.sel').forEach(function(li){li.classList.remove('sel');});
    var s=selected(); s.forEach(function(li){li.classList.add('sel');});
    bar.hidden=!s.length; info.textContent=s.length?(s.length+' line'+(s.length>1?'s':'')+' selected'):'';
  }
  ALL.forEach(function(li){
    li.addEventListener('click',function(e){
      if(e.target.classList.contains('ts'))return;
      if(wline===li){
        var w=e.target.closest('.w'); if(!w) return;
        var k=key(li), mk=MARKS[k]=MARKS[k]||{}, cut=(mk.words||[]).slice(), n=+w.dataset.w, at=cut.indexOf(n);
        if(at>=0) cut.splice(at,1); else cut.push(n);
        cut.sort(function(a,b){return a-b;});
        w.classList.toggle('wc');
        post({action:'words',part:li.dataset.p,line:li.dataset.i,words:cut.join(',')}).then(function(){ if(cut.length) mk.words=cut; else delete mk.words; },function(){ w.classList.toggle('wc'); });
        return;
      }
      if(wline) endWords();
      var n=+li.dataset.n;
      if(sel.a!==null&&sel.b===sel.a&&n!==sel.a){sel.b=n;}
      else if(sel.a===n&&sel.b===n){sel={a:null,b:null};}
      else{sel={a:n,b:n};}
      show();
    });
  });
  var np=document.getElementById('nowplaying'), nptxt=document.getElementById('nptxt'), playing=null;
  function stop(){document.querySelectorAll('.edit-player').forEach(function(a){a.pause();}); np.hidden=true;}
  document.getElementById('stopbtn').addEventListener('click',stop);
  document.querySelectorAll('.tx .ts').forEach(function(b){
    b.addEventListener('click',function(){
      var li=b.closest('li'), au=document.querySelector('.edit-player[data-part="'+li.dataset.p+'"]');
      if(!au){alert('Pick the audio for part '+(+li.dataset.p+1)+' first (in Parts, above).');return;}
      stop(); au.currentTime=Math.max(0,parseFloat(li.dataset.t)-1); au.play();
      nptxt.textContent='Playing part '+(+li.dataset.p+1)+' from '+b.textContent; np.hidden=false;
    });
  });
  function post(data){
    var fd=new FormData(); fd.append('csrf',CSRF); fd.append('id',ID);
    for(var k in data) fd.append(k,data[k]);
    return fetch('edit.php',{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){
      if(j.error) throw new Error(j.error);
      var c=j.counts; counts.innerHTML=c.lines+' lines · <b>'+c.cut+'</b> cut · <b>'+c.cold+'</b> cold open · <b>'+c.clip+'</b> clip · <b>'+c.note+'</b> notes · <b>'+c.words+'</b> words cut';
      return j;
    }).catch(function(e){alert(e.message+'\nYour last change was NOT saved.'); throw e;});
  }
  var lastClip='';
  bar.addEventListener('click',function(e){
    var m=e.target.dataset&&e.target.dataset.m; if(!m)return;
    if(m==='done'){endWords();sel={a:null,b:null};show();return;}
    if(m==='words'){
      var s1=selected(); if(s1.length!==1){alert('Select just one line to cut words from it.');return;}
      if(wline){endWords();return;}
      wline=s1[0]; wline.classList.add('wedit'); paintWords(wline); e.target.textContent='Words done'; return;
    }
    var s=selected(); if(!s.length)return;
    if(m==='note'){
      var first=s[0], k=key(first), cur=(MARKS[k]||{}).note||'';
      var n=prompt('Note for the editor (on the first selected line):',cur); if(n===null)return;
      post({action:'note',part:first.dataset.p,line:first.dataset.i,note:n}).then(function(){MARKS[k]=MARKS[k]||{};if(n.trim())MARKS[k].note=n.trim();else delete MARKS[k].note;paint(first);});
      return;
    }
    var clip='';
    if(m==='clip'){clip=prompt('Name this clip (short, like "the frozen bat"):',lastClip); if(clip===null)return; lastClip=clip;}
    post({action:'mark',items:s.map(key).join(','),m:m,clip:clip}).then(function(){
      s.forEach(function(li){var k=key(li), old=MARKS[k]||{};
        if(m==='clear'){ if(old.note) MARKS[k]={note:old.note}; else delete MARKS[k]; }
        else { MARKS[k]=old; if(m==='clip')MARKS[k].clip=clip||'clip'; else MARKS[k].m=m; }
        paint(li);});
      sel={a:null,b:null}; show();
    });
  });
  document.querySelectorAll('.copy').forEach(function(b){b.addEventListener('click',function(){
    var t=b.dataset.link; (navigator.clipboard?navigator.clipboard.writeText(t):Promise.reject()).catch(function(){prompt('Copy this link:',t);}).then(function(){var o=b.textContent;b.textContent='Copied';setTimeout(function(){b.textContent=o;},1500);});
  });});
})();
</script>
<?php dtm_page_end();
