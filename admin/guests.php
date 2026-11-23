<?php
// Guest List (DTM-SUBSCRIBERS-1): everyone who signed up on the home page, a CSV download, remove,
// and emailing the list through Duck's Gmail (lib/mailer.php).
require __DIR__ . '/../lib/mailer.php';
$user = dtm_require_admin();
$msg = ''; $err = '';

if (isset($_GET['csv'])) {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="guest-list-' . date('Y-m-d') . '.csv"');
  $o = fopen('php://output', 'w'); fputcsv($o, ['email', 'signed_up']);
  foreach (dtm_subscribers() as $r) fputcsv($o, [$r['email'], $r['at'] ?? '']);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  dtm_check_csrf();
  $a = (string)($_POST['action'] ?? '');
  try {
    if ($a === 'remove') { dtm_subscriber_remove((string)($_POST['email'] ?? '')); header('Location: guests.php?removed=1'); exit; }
    if ($a === 'gmail') {
      $gu = strtolower(trim((string)($_POST['gmail_user'] ?? '')));
      $gp = preg_replace('/\s+/', '', (string)($_POST['gmail_pass'] ?? ''));
      if (!filter_var($gu, FILTER_VALIDATE_EMAIL)) $err = 'That Gmail address looks wrong.';
      elseif ($gp !== '' && !preg_match('/^[a-z]{16}$/i', $gp)) $err = "An app password is 16 letters (Google shows it in groups of four). That doesn't look like one. Your normal Gmail password won't work here.";
      elseif ($gp === '' && dtm_mail_cfg()['pass'] === '') $err = 'Paste the app password too.';
      else { $err = dtm_mail_save_secrets($gu, $gp); if ($err === '') { header('Location: guests.php?gmail=1'); exit; } }
    }
    if ($a === 'test' || $a === 'create') {
      $subject = trim(mb_substr((string)($_POST['subject'] ?? ''), 0, 150));
      $body = trim(mb_substr((string)($_POST['body'] ?? ''), 0, 20000));
      if ($subject === '' || $body === '') $err = 'Give it a subject and something to say.';
      elseif (!dtm_mail_ready()) $err = 'Set up Gmail first (below).';
      elseif ($a === 'test') {
        $to = dtm_mail_cfg()['user'];
        $smtp = new DtmSmtp(dtm_mail_cfg());
        try { $smtp->open(); [$t, $hm, $u] = dtm_mail_bodies($body, $to); $smtp->send($to, '[TEST] ' . $subject, $t, $hm, $u); $msg = "Test sent to $to. Check your inbox (and spam, the first time)."; }
        catch (RuntimeException $e) { $err = $e->getMessage(); }
        $smtp->close();
      } else {
        $id = bin2hex(random_bytes(6));
        dtm_json_update('mail-log.json', function ($log) use ($id, $subject, $body, $user) {
          $log['mailings'][$id] = ['subject' => $subject, 'body' => $body, 'created' => date('c'), 'by' => $user['name'], 'sent' => [], 'failed' => []];
          return $log;
        });
        header('Location: guests.php?send=' . $id); exit;
      }
    }
  } catch (RuntimeException $e) { $err = $e->getMessage(); }
}

$subs = dtm_subscribers();
$cfg = dtm_mail_cfg();
$log = dtm_json_read('mail-log.json');
$mailings = $log['mailings'] ?? [];
uasort($mailings, fn($a, $b) => strcmp($b['created'], $a['created']));
$sendId = preg_replace('/[^a-f0-9]/', '', (string)($_GET['send'] ?? ''));
$sending = $sendId !== '' && isset($mailings[$sendId]) ? $mailings[$sendId] : null;
$todayLeft = DTM_MAIL_DAILY_CAP - dtm_mail_sent_today($log);
dtm_page_start('Guest List', $user, true);
?>
<section class="admin-card">
  <h1 class="metal">Guest List</h1>
  <p class="cut-for bang"><?= count($subs) ?> signed up</p>
  <?php if (isset($_GET['removed'])): ?><p class="notice ok bang" role="status">Removed.</p><?php endif; ?>
  <?php if (isset($_GET['gmail']) && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?><p class="notice ok bang" role="status">Gmail saved. Send yourself a test below.</p><?php endif; ?>
  <?php if ($msg): ?><p class="notice ok bang" role="status"><?= h($msg) ?></p><?php endif; ?>
  <?php if ($err): ?><p class="notice err bang" role="alert"><?= h($err) ?></p><?php endif; ?>

  <?php if ($sending): ?>
  <div class="mail-run" id="mail-run" data-id="<?= h($sendId) ?>" data-csrf="<?= h(dtm_csrf()) ?>">
    <h2>Sending: <?= h($sending['subject']) ?></h2>
    <div class="up-bar"><i id="mail-bar" style="width:0%"></i></div>
    <p id="mail-status" class="bang">Starting... keep this page open.</p>
  </div>
  <script>
  (function(){var box=document.getElementById('mail-run'),st=document.getElementById('mail-status'),bar=document.getElementById('mail-bar');
   function step(){var f=new FormData();f.append('id',box.dataset.id);f.append('csrf',box.dataset.csrf);
    fetch('guests-send.php',{method:'POST',body:f,credentials:'same-origin'}).then(function(r){return r.json()}).then(function(j){
     var pct=j.total?Math.round(100*(j.sent+(j.failed||0))/j.total):100;bar.style.width=pct+'%';
     if(j.error){st.textContent=j.error+(j.total?' ('+j.sent+' of '+j.total+' sent so far.)':'');return;}
     if(j.done){st.textContent='Done. Sent to '+j.sent+' of '+j.total+(j.failed?', '+j.failed+' failed (see below).':'.');return;}
     st.textContent='Sent '+j.sent+' of '+j.total+'... keep this page open.';setTimeout(step,1500);
    }).catch(function(){st.textContent='Lost the connection. Reload the page to pick up where it left off.';});}
   step();})();
  </script>
  <?php endif; ?>

  <?php if (dtm_mail_ready()): ?>
  <details class="cut-more" open>
    <summary class="bang">Email the list</summary>
    <form method="post" class="admin-form"><?= dtm_csrf_field() ?>
      <label for="msubj" class="bang">Subject</label>
      <input id="msubj" name="subject" type="text" maxlength="150" required value="<?= h($_POST['subject'] ?? '') ?>" placeholder="New episode: Due To a Possible Rabies Infection">
      <label for="mbody" class="bang">Message</label>
      <textarea id="mbody" name="body" class="script-box" rows="10" required placeholder="Write it like a text to a friend. Paste the YouTube link on its own line. A blank line starts a new paragraph."><?= h($_POST['body'] ?? '') ?></textarea>
      <p class="small">Goes out from <b><?= h($cfg['user']) ?></b> as "Defacing the Music". Everyone gets their own copy (nobody sees the other addresses) with an unsubscribe link at the bottom. Replies come to your Gmail. Gmail allows about 500 a day; you have <?= max(0, $todayLeft) ?> left today.</p>
      <div class="btn-row">
        <button type="submit" name="action" value="test" class="pull-btn bang">Send me a test</button>
        <button type="submit" name="action" value="create" class="pull-btn bang" onclick="return confirm('Send this to all <?= count($subs) ?> people on the Guest List?')">Send to everyone (<?= count($subs) ?>)</button>
      </div>
    </form>
  </details>
  <?php endif; ?>

  <details class="cut-more"<?= dtm_mail_ready() ? '' : ' open' ?>>
    <summary class="bang"><?= dtm_mail_ready() ? 'Gmail settings' : 'Set up Gmail (one time)' ?></summary>
    <ol class="small">
      <li>Your Google account needs 2-Step Verification on: <a href="https://myaccount.google.com/signinoptions/two-step-verification" target="_blank" rel="noopener">turn it on here</a>.</li>
      <li>Make an app password: <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">myaccount.google.com/apppasswords</a>. Name it "DTM site". Google shows 16 letters once. Copy them.</li>
      <li>Paste it below with the Gmail address. It's saved in the server's secrets file, never shown again, never in git. To cut the site off later, delete the app password at that same Google page.</li>
    </ol>
    <form method="post" class="admin-form" autocomplete="off"><?= dtm_csrf_field() ?><input type="hidden" name="action" value="gmail">
      <label for="gu" class="bang">Gmail address</label>
      <input id="gu" name="gmail_user" type="email" required value="<?= h($cfg['user']) ?>" placeholder="defacingthemusic@gmail.com">
      <label for="gp" class="bang">App password<?= $cfg['pass'] !== '' ? ' (saved; leave blank to keep it)' : '' ?></label>
      <input id="gp" name="gmail_pass" type="password" autocomplete="new-password" placeholder="abcd efgh ijkl mnop">
      <div class="btn-row"><button type="submit" class="pull-btn bang">Save</button></div>
    </form>
    <p class="small">If saving fails, add these two lines inside <code>dtm-secrets.php</code> (one folder above the site) by hand, plus a <code>'mail_key'</code> of any long random letters: <code>'gmail_user' =&gt; 'you@gmail.com',</code> <code>'gmail_app_password' =&gt; 'the16letters',</code></p>
  </details>

  <?php if ($mailings): ?>
  <details class="cut-more">
    <summary class="bang">Past emails</summary>
    <ul class="up-list">
      <?php foreach (array_slice($mailings, 0, 20, true) as $mid => $m): ?>
      <li><b><?= h($m['subject']) ?></b> · <?= h(date('M j, Y g:ia', strtotime($m['created']))) ?> · sent to <?= count($m['sent'] ?? []) ?><?= !empty($m['failed']) ? ', ' . count($m['failed']) . ' failed' : '' ?><?= empty($m['finished']) ? ' · <a href="guests.php?send=' . h($mid) . '">not finished: resume</a>' : '' ?>
        <?php if (!empty($m['failed'])): ?><br><span class="small"><?php foreach ($m['failed'] as $fe => $why) echo h($fe) . ': ' . h($why) . '<br>'; ?></span><?php endif; ?></li>
      <?php endforeach; ?>
    </ul>
  </details>
  <?php endif; ?>

  <h2>Everyone on the list</h2>
  <p class="small"><a href="guests.php?csv=1">Download as CSV</a></p>
  <?php if (!$subs): ?><p>Nobody yet.</p><?php else: ?>
  <ul class="up-list guest-list">
    <?php foreach ($subs as $r): ?>
    <li><span><?= h($r['email']) ?></span> <span class="small"><?= !empty($r['at']) ? h(date('M j, Y', strtotime($r['at']))) : '' ?></span>
      <form method="post" style="display:inline"><?= dtm_csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="email" value="<?= h($r['email']) ?>"><button type="submit" class="linkish" onclick="return confirm('Take <?= h(addslashes($r['email'])) ?> off the list?')">Remove</button></form></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</section>
<?php dtm_page_end();
