<?php
// Uploads: raw episode files and anything else Duck wants on the server. Chunked upload, so big files work.
require __DIR__ . '/../lib/uploads.php';
$user = dtm_require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  dtm_check_csrf();
  $id = (string)($_POST['id'] ?? '');
  if (($_POST['action'] ?? '') === 'delete' && ($u = dtm_upload($id))) {
    @unlink(dtm_uploads_dir() . '/media/' . $u['file']);
    dtm_json_update('uploads.json', fn($l) => array_values(array_filter($l, fn($x) => $x['id'] !== $id)));
    header('Location: uploads.php?deleted=1'); exit;
  }
  if (($_POST['action'] ?? '') === 'newlink' && dtm_upload($id)) {
    dtm_json_update('uploads.json', function ($l) use ($id) { foreach ($l as &$x) if ($x['id'] === $id) $x['token'] = bin2hex(random_bytes(20)); return $l; });
    header('Location: uploads.php?newlink=1'); exit;
  }
}
dtm_uploads_dir();
$files = dtm_uploads();
$chunk = dtm_chunk_size();
dtm_page_start('Uploads', $user, true);
?>
<section class="admin-card">
  <h1 class="metal">Uploads</h1>
  <p>Raw episode files, audio, video, images. Big files are fine; they go up in pieces. Keep this tab open until it says done.</p>
  <?php if (isset($_GET['deleted'])): ?><p class="notice ok bang" role="status">Deleted.</p><?php endif; ?>
  <?php if (isset($_GET['newlink'])): ?><p class="notice ok bang" role="status">New link made. The old one stopped working.</p><?php endif; ?>
  <div class="admin-form">
    <label for="files" class="bang">Pick files</label>
    <input id="files" type="file" multiple accept="<?= h(implode(',', array_map(fn($e) => ".$e", DTM_UPLOAD_EXT))) ?>">
    <label for="note" class="bang">Note (optional)</label>
    <input id="note" type="text" maxlength="200" placeholder="Episode 0, take 2, raw">
    <div class="btn-row"><button type="button" id="go" class="pull-btn bang">Upload</button></div>
  </div>
  <div id="queue" class="up-queue"></div>
</section>

<section class="admin-card">
  <h2 class="bang">On the server</h2>
  <?php if (!$files): ?><p>Nothing uploaded yet.</p><?php endif; ?>
  <ul class="up-list">
    <?php foreach ($files as $f): $link = dtm_site_base() . '/admin/file.php?id=' . $f['id'] . '&t=' . $f['token']; ?>
    <li>
      <div class="up-name"><strong><?= h($f['name']) ?></strong>
        <span class="small"><?= h(dtm_human_size((int)$f['size'])) ?> · <?= h(date('M j, g:ia', strtotime($f['uploaded']))) ?> · <?= h($f['by']) ?><?= $f['note'] !== '' ? ' · ' . h($f['note']) : '' ?></span></div>
      <div class="up-acts">
        <a href="file.php?id=<?= h($f['id']) ?>" target="_blank" rel="noopener">Open</a>
        <a href="file.php?id=<?= h($f['id']) ?>&amp;dl=1">Download</a>
        <button type="button" class="linkish copy" data-link="<?= h($link) ?>">Copy link for Claude</button>
        <form method="post"><?= dtm_csrf_field() ?><input type="hidden" name="action" value="newlink"><input type="hidden" name="id" value="<?= h($f['id']) ?>"><button class="linkish" title="Make a new secret link (the old one stops working)">New link</button></form>
        <form method="post" onsubmit="return confirm('Delete <?= h(addslashes($f['name'])) ?> from the server for good?')"><?= dtm_csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= h($f['id']) ?>"><button class="linkish danger">Delete</button></form>
      </div>
    </li>
    <?php endforeach; ?>
  </ul>
  <p class="small">"Copy link for Claude" copies a private link to that one file. Anyone with the link can download it, so only paste it to Claude. "New link" kills the old one.</p>
</section>

<script>
(function(){
  var CHUNK=<?= (int)$chunk ?>, CSRF=<?= json_encode(dtm_csrf()) ?>;
  var q=document.getElementById('queue'), busy=false;
  function hex(n){var a=new Uint8Array(n);crypto.getRandomValues(a);return Array.from(a,function(b){return('0'+b.toString(16)).slice(-2)}).join('')}
  function size(b){return b>=1073741824?(b/1073741824).toFixed(2)+' GB':(b/1048576).toFixed(1)+' MB'}
  function row(file){
    var d=document.createElement('div');d.className='up-row';
    d.innerHTML='<div class="up-top"><span class="up-file"></span><span class="up-pct">waiting</span></div><div class="up-bar"><i></i></div>';
    d.querySelector('.up-file').textContent=file.name+' ('+size(file.size)+')';
    q.appendChild(d);return d;
  }
  function send(fd,tries){
    return fetch('upload-chunk.php',{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){
      return r.json().catch(function(){return {error:'Server said '+r.status}}).then(function(j){ if(!r.ok||j.error) throw new Error(j.error||('HTTP '+r.status)); return j; });
    }).catch(function(e){
      if(tries>0 && !/expired|Signed out|Can't upload|sync|Missing a chunk/.test(e.message)) return new Promise(function(ok){setTimeout(ok,3000)}).then(function(){return send(fd,tries-1)});
      throw e;
    });
  }
  async function upload(file,note){
    var d=row(file), bar=d.querySelector('i'), pct=d.querySelector('.up-pct');
    var id=hex(12), total=Math.max(1,Math.ceil(file.size/CHUNK)), t0=Date.now();
    for(var i=0;i<total;i++){
      var fd=new FormData();
      fd.append('csrf',CSRF);fd.append('upload_id',id);fd.append('index',i);fd.append('total',total);
      fd.append('size',file.size);fd.append('name',file.name);fd.append('note',note);
      fd.append('chunk',file.slice(i*CHUNK,Math.min(file.size,(i+1)*CHUNK)),'chunk');
      try{ await send(fd,4); }catch(e){ d.classList.add('err'); pct.textContent='Failed: '+e.message; return false; }
      var done=(i+1)/total, secs=(Date.now()-t0)/1000, left=done<1?Math.round(secs/done-secs):0;
      bar.style.width=(done*100).toFixed(1)+'%';
      pct.textContent=Math.floor(done*100)+'%'+(left>5?' · about '+(left>90?Math.round(left/60)+' min':left+' s')+' left':'');
    }
    d.classList.add('ok'); pct.textContent='Done'; return true;
  }
  window.addEventListener('beforeunload',function(e){ if(busy){ e.preventDefault(); e.returnValue=''; } });
  document.getElementById('go').addEventListener('click', async function(){
    var files=document.getElementById('files').files, note=document.getElementById('note').value;
    if(!files.length||busy) return;
    busy=true; this.disabled=true; var allOk=true;
    for(var k=0;k<files.length;k++){ if(!(await upload(files[k],note))) allOk=false; }
    busy=false; this.disabled=false;
    if(allOk) setTimeout(function(){location.href='uploads.php'},800);
  });
  document.querySelectorAll('.copy').forEach(function(b){ b.addEventListener('click',function(){
    var l=b.getAttribute('data-link');
    (navigator.clipboard?navigator.clipboard.writeText(l):Promise.reject()).then(function(){b.textContent='Copied!';setTimeout(function(){b.textContent='Copy link for Claude'},2000)})
    .catch(function(){ window.prompt('Copy this link:',l); });
  }); });
})();
</script>
<?php dtm_page_end();
