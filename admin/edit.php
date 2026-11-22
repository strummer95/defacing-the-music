<?php
// Edit: mark up an episode transcript (CUT / COLD / CLIP / notes) for the editing session on Duck's PC.
require __DIR__ . '/../lib/edits.php';
$user = dtm_require_admin();
$id = preg_replace('/[^a-f0-9]/', '', (string)($_GET['id'] ?? $_POST['id'] ?? ''));
$json = fn($d, $code = 200) => (http_response_code($code) || true) && exit(json_encode($d));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');
  $ajax = in_array($action, ['mark', 'note'], true);
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
      $pi = (int)($_POST['part'] ?? -1);
      $ids = array_slice(array_map('intval', array_filter(explode(',', (string)($_POST['lines'] ?? '')), 'is_numeric')), 0, 5000);
      $m = (string)($_POST['m'] ?? '');
      $clip = trim(mb_substr((string)($_POST['clip'] ?? ''), 0, 40));
      if (!in_array($m, array_merge(DTM_EDIT_MARKS, ['clear']), true)) $json(['error' => 'Bad mark'], 400);
      $p = dtm_edit_update($id, function ($p) use ($pi, $ids, $m, $clip) {
        if (!isset($p['parts'][$pi])) return $p;
        $n = count($p['parts'][$pi]['lines']);
        foreach ($ids as $i) {
          if ($i < 0 || $i >= $n) continue;
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
  <p id="counts" class="small"><?= $c['lines'] ?> lines · <b><?= $c['cut'] ?></b> cut · <b><?= $c['cold'] ?></b> cold open · <b><?= $c['clip'] ?></b> clip · <b><?= $c['note'] ?></b> notes</p>
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
<section class="admin-card edit-help">
  <p><b>Tap a line</b> to select it, <b>tap another</b> to select everything in between. Then pick <b>Cut</b>, <b>Cold open</b>, <b>Clip</b> or <b>Note</b> in the bar at the bottom. Tap a timestamp to hear that spot (pick the audio for each part first).</p>
</section>
<?php endif; ?>

<?php foreach ($p['parts'] as $pi => $part): $au = $part['audio'] !== '' ? dtm_upload($part['audio']) : null; ?>
<section class="admin-card edit-part" id="part<?= $pi ?>" data-part="<?= $pi ?>">
  <h2 class="bang">Part <?= $pi + 1 ?>: <?= h($part['name']) ?></h2>
  <form method="post" class="edit-audio"><?= dtm_csrf_field() ?><input type="hidden" name="id" value="<?= h($p['id']) ?>"><input type="hidden" name="action" value="audio"><input type="hidden" name="part" value="<?= $pi ?>">
    <label class="small">Audio to play:
      <select name="upload" onchange="this.form.submit()">
        <option value="">None</option>
        <?php foreach ($avs as $u): ?><option value="<?= h($u['id']) ?>"<?= $u['id'] === $part['audio'] ? ' selected' : '' ?>><?= h($u['name']) ?></option><?php endforeach; ?>
      </select></label>
    <span class="small">(upload an audio export on the Uploads page first)</span>
  </form>
  <?php if ($au): ?><audio class="edit-player" controls preload="metadata" src="file.php?id=<?= h($au['id']) ?>"></audio><?php endif; ?>
  <ol class="tx">
    <?php foreach ($part['lines'] as $ln): ?>
    <li data-i="<?= $ln['i'] ?>" data-t="<?= h((string)$ln['t']) ?>" class="<?= $ln['flag'] ? 'flag' : '' ?><?= $ln['who'] !== '' ? ' who-' . h(strtolower(preg_replace('/[^A-Za-z]/', '', $ln['who']))) : '' ?>">
      <?php if ($ln['ts'] !== ''): ?><button type="button" class="ts"><?= h($ln['ts']) ?></button><?php endif; ?>
      <?php if ($ln['who'] !== ''): ?><b class="who"><?= h($ln['who']) ?></b><?php endif; ?>
      <span class="tx-t"><?= h($ln['text']) ?></span><span class="tag"></span><em class="nt"></em>
    </li>
    <?php endforeach; ?>
  </ol>
  <form method="post" onsubmit="return confirm('Remove this part and its marks?')" class="small"><?= dtm_csrf_field() ?><input type="hidden" name="id" value="<?= h($p['id']) ?>"><input type="hidden" name="action" value="delpart"><input type="hidden" name="part" value="<?= $pi ?>"><button class="linkish danger">Remove this part</button></form>
</section>
<?php endforeach; ?>

<section class="admin-card"><form method="post" onsubmit="return confirm('Delete this whole edit and all its marks?')"><?= dtm_csrf_field() ?><input type="hidden" name="id" value="<?= h($p['id']) ?>"><input type="hidden" name="action" value="delete"><button class="linkish danger">Delete this edit</button></form></section>

<div class="edit-bar" id="bar" hidden>
  <span id="selinfo" class="small"></span>
  <button type="button" data-m="cut" class="pull-btn bang">Cut</button>
  <button type="button" data-m="cold" class="pull-btn bang">Cold open</button>
  <button type="button" data-m="clip" class="pull-btn bang">Clip</button>
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
.tx li.sel{background:#fff3b0;border-left-color:#111}
.tx li.m-cut{border-left-color:#E4251B}.tx li.m-cut .tx-t{text-decoration:line-through;color:#8a7f70}
.tx li.m-cut .tag::before{content:"CUT";color:#E4251B}
.tx li.m-cold{border-left-color:#c77d00;background:#fff4dc}.tx li.m-cold .tag::before{content:"COLD OPEN";color:#c77d00}
.tx li.m-clip{box-shadow:inset -6px 0 0 #2a7d3a}.tx li.m-clip:not(.m-cut):not(.m-cold){background:#eaf6ea}.tx .tag .cl{color:#2a7d3a;margin-left:6px}
.edit-player{width:100%;margin:6px 0}
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
  var sel={part:null,a:null,b:null};
  function rows(pi){return Array.prototype.slice.call(document.querySelectorAll('.edit-part[data-part="'+pi+'"] .tx li'));}
  function paint(pi,li){
    var mk=MARKS[pi+':'+li.dataset.i]||{};
    li.classList.remove('m-cut','m-cold','m-clip');
    if(mk.m) li.classList.add('m-'+mk.m);
    var tag=li.querySelector('.tag'); tag.innerHTML='';
    if(mk.clip){li.classList.add('m-clip');var cl=document.createElement('span');cl.className='cl';cl.textContent='CLIP: '+mk.clip;tag.appendChild(cl);}
    li.querySelector('.nt').textContent=mk.note?('Note: '+mk.note):'';
  }
  document.querySelectorAll('.edit-part').forEach(function(s){var pi=s.dataset.part;rows(pi).forEach(function(li){paint(pi,li);});});
  function selected(){if(sel.part===null)return[];var lo=Math.min(sel.a,sel.b),hi=Math.max(sel.a,sel.b);return rows(sel.part).filter(function(li){var i=+li.dataset.i;return i>=lo&&i<=hi;});}
  function show(){
    document.querySelectorAll('.tx li.sel').forEach(function(li){li.classList.remove('sel');});
    var s=selected(); s.forEach(function(li){li.classList.add('sel');});
    bar.hidden=!s.length; info.textContent=s.length?(s.length+' line'+(s.length>1?'s':'')+' selected'):'';
  }
  document.querySelectorAll('.tx li').forEach(function(li){
    li.addEventListener('click',function(e){
      if(e.target.classList.contains('ts'))return;
      var pi=li.closest('.edit-part').dataset.part, i=+li.dataset.i;
      if(sel.part===pi&&sel.a!==null&&sel.b===sel.a&&i!==sel.a){sel.b=i;}
      else if(sel.part===pi&&sel.a===i&&sel.b===i){sel={part:null,a:null,b:null};}
      else{sel={part:pi,a:i,b:i};}
      show();
    });
  });
  document.querySelectorAll('.tx .ts').forEach(function(b){
    b.addEventListener('click',function(){
      var sec=b.closest('.edit-part'), au=sec.querySelector('.edit-player');
      if(!au){alert('Pick the audio for this part first (above the transcript).');return;}
      au.currentTime=Math.max(0,parseFloat(b.closest('li').dataset.t)-1); au.play();
    });
  });
  function post(data){
    var fd=new FormData(); fd.append('csrf',CSRF); fd.append('id',ID);
    for(var k in data) fd.append(k,data[k]);
    return fetch('edit.php',{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){
      if(j.error) throw new Error(j.error);
      var c=j.counts; counts.innerHTML=c.lines+' lines · <b>'+c.cut+'</b> cut · <b>'+c.cold+'</b> cold open · <b>'+c.clip+'</b> clip · <b>'+c.note+'</b> notes';
      return j;
    }).catch(function(e){alert(e.message+'\nYour last change was NOT saved.'); throw e;});
  }
  var lastClip='';
  bar.addEventListener('click',function(e){
    var m=e.target.dataset&&e.target.dataset.m; if(!m)return;
    if(m==='done'){sel={part:null,a:null,b:null};show();return;}
    var s=selected(); if(!s.length)return; var pi=sel.part;
    if(m==='note'){
      var first=s[0], cur=(MARKS[pi+':'+first.dataset.i]||{}).note||'';
      var n=prompt('Note for the editor (on the first selected line):',cur); if(n===null)return;
      post({action:'note',part:pi,line:first.dataset.i,note:n}).then(function(){var k=pi+':'+first.dataset.i;MARKS[k]=MARKS[k]||{};if(n.trim())MARKS[k].note=n.trim();else delete MARKS[k].note;paint(pi,first);});
      return;
    }
    var clip='';
    if(m==='clip'){clip=prompt('Name this clip (short, like "the frozen bat"):',lastClip); if(clip===null)return; lastClip=clip;}
    var ids=s.map(function(li){return li.dataset.i;});
    post({action:'mark',part:pi,lines:ids.join(','),m:m,clip:clip}).then(function(){
      s.forEach(function(li){var k=pi+':'+li.dataset.i, old=MARKS[k]||{};
        if(m==='clear'){ if(old.note) MARKS[k]={note:old.note}; else delete MARKS[k]; }
        else { MARKS[k]=old; if(m==='clip')MARKS[k].clip=clip||'clip'; else MARKS[k].m=m; }
        paint(pi,li);});
      sel={part:null,a:null,b:null}; show();
    });
  });
  document.querySelectorAll('.copy').forEach(function(b){b.addEventListener('click',function(){
    var t=b.dataset.link; (navigator.clipboard?navigator.clipboard.writeText(t):Promise.reject()).catch(function(){prompt('Copy this link:',t);}).then(function(){var o=b.textContent;b.textContent='Copied';setTimeout(function(){b.textContent=o;},1500);});
  });});
})();
</script>
<?php dtm_page_end();
