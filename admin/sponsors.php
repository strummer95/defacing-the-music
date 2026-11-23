<?php
// admin > Sponsors: the numbers and prices on the public media kit (defacingthemusic.com/sponsor/), and messages from brands.
require __DIR__ . '/../lib/sponsor.php';
$user = dtm_require_admin();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  dtm_check_csrf();
  $a = (string)($_POST['action'] ?? '');
  if ($a === 'save') {
    $in = [];
    foreach (DTM_SPONSOR_FIELDS as $k => $_) $in[$k] = trim(mb_substr((string)($_POST[$k] ?? ''), 0, 120));
    if ($in['email'] !== '' && !filter_var($in['email'], FILTER_VALIDATE_EMAIL)) $err = 'That email looks wrong.';
    else { dtm_json_update('sponsor.json', fn($c) => $in); header('Location: sponsors.php?saved=1'); exit; }
  }
  if ($a === 'done' || $a === 'delete') {
    $id = (string)($_POST['id'] ?? '');
    dtm_json_update('sponsor-inquiries.json', function ($l) use ($a, $id) {
      if ($a === 'delete') return array_values(array_filter($l, fn($x) => $x['id'] !== $id));
      foreach ($l as &$x) if ($x['id'] === $id) $x['done'] = !$x['done'];
      return $l;
    });
    header('Location: sponsors.php#msgs'); exit;
  }
}
$cfg = dtm_sponsor_cfg();
$msgs = array_reverse(dtm_json_read('sponsor-inquiries.json'));
$open = count(array_filter($msgs, fn($m) => empty($m['done'])));
dtm_page_start('Sponsors', $user, true);
?>
<section class="admin-card">
  <h1 class="metal">Sponsors</h1>
  <p>Your media kit: <a href="/sponsor/" target="_blank" rel="noopener">defacingthemusic.com/sponsor</a>. Send that link to brands, or open it and hit <b>Save as PDF</b> for a one-page kit to attach to an email.</p>
  <?php if (isset($_GET['saved'])): ?><p class="notice ok bang" role="status">Saved. The sponsor page is updated.</p><?php endif; ?>
  <?php if ($err): ?><p class="notice err bang" role="alert"><?= h($err) ?></p><?php endif; ?>

  <h2 class="bang saved-h" id="msgs">Messages from brands<?= $open ? " ($open new)" : '' ?></h2>
  <?php if (!$msgs): ?><p class="small">Nothing yet. When a brand fills in the form on the sponsor page, it shows up here.</p><?php else: ?>
  <ul class="up-list">
    <?php foreach ($msgs as $m): ?>
    <li class="<?= !empty($m['done']) ? 'muted' : '' ?>"><b><?= h($m['company'] !== '' ? $m['company'] : ($m['name'] !== '' ? $m['name'] : $m['email'])) ?></b>
      <span class="small"> · <?= h(date('M j, Y g:ia', strtotime($m['at']))) ?> · <?= h($m['name']) ?> <a href="mailto:<?= h($m['email']) ?>"><?= h($m['email']) ?></a></span>
      <p style="white-space:pre-wrap;margin:6px 0"><?= h($m['message']) ?></p>
      <form method="post" style="display:inline"><?= dtm_csrf_field() ?><input type="hidden" name="id" value="<?= h($m['id']) ?>"><button name="action" value="done" class="linkish"><?= !empty($m['done']) ? 'Mark as new' : 'Mark as handled' ?></button> · <button name="action" value="delete" class="linkish" onclick="return confirm('Delete this message?')">Delete</button></form>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <details class="cut-more pitch" open>
    <summary class="bang">Who to pitch (private, not on the public page)</summary>
    <p class="small">Smaller brands that already work with small creators, usually starting with free product, an affiliate code, or a small paid read. Pitch the ones you'd actually use. Start with the affiliate programs: no minimum audience.</p>
    <dl>
      <dt>Start now: affiliate programs (you get a cut of sales, no audience minimum)</dt>
      <dd>Sweetwater, Reverb, Guitar Center / Musician's Friend, Amazon Associates. Put your links in episode descriptions for the gear you mention.</dd>
      <dt>Guitar strings and picks</dt>
      <dd><b>Stringjoy</b> (small, custom strings, loves creators), <b>Chicken Picks</b>, <b>V-Picks</b>, <b>Gravity Picks</b> (small pick makers). Bigger but have artist and creator programs: D'Addario, Ernie Ball, Elixir, Dunlop.</dd>
      <dt>Pedals and gear</dt>
      <dd>Indie pedal builders do a lot of creator deals: <b>EarthQuaker Devices</b>, <b>Walrus Audio</b>, <b>JHS Pedals</b>. Good fit for a quick "what's on Duck's board" read.</dd>
      <dt>Headphones and speakers</dt>
      <dd><b>Marshall</b> (headphones and speakers, and it's the same logo as the amps in your room), <b>Soundcore</b> (Anker, sponsors tons of creators), <b>Tribit</b>, <b>1More</b>, <b>Status Audio</b>, <b>Grado</b> (small family company).</dd>
      <dt>Band tees, vinyl and merch</dt>
      <dd>Indie record stores and band tee shops, vinyl clubs like <b>Vinyl Me, Please</b>, and print shops that'd trade printing your merch for a mention.</dd>
      <dt>Learn-to-play apps</dt>
      <dd><b>Fender Play</b>, <b>Yousician</b>, <b>Guitar Tricks</b>: they spend on music podcasts and YouTube.</dd>
      <dt>Irreverent brands that love metal and comedy</dt>
      <dd><b>Liquid Death</b> is the obvious one: its whole brand is the show's energy.</dd>
      <dt>Chicagoland (hometown pitch)</dt>
      <dd><b>Chicago Music Exchange</b>, local record stores (Reckless Records), local venues and bars. A hometown show is an easy yes, especially once the Disco Demolition episode is out.</dd>
    </dl>
    <p class="small"><b>How to pitch a small brand:</b> a short email with the sponsor page link, one sentence on why your listeners are their customers, and an easy first ask: "send us one to try, and if we like it we'll talk about it on the show." Free product first is normal. Ask for cash once you have numbers.</p>
  </details>

  <h2 class="bang saved-h">Your numbers and prices</h2>
  <p class="small">Leave anything blank and the page just doesn't show it (prices say "Ask us"). Until you fill in listens per episode, the page says you're brand new and offers launch pricing, which is the honest pitch right now. Write them how you want them shown, like <code>5,200</code> or <code>$150</code>.</p>
  <form method="post" class="admin-form"><?= dtm_csrf_field() ?><input type="hidden" name="action" value="save">
    <?php foreach (DTM_SPONSOR_FIELDS as $k => $label): ?>
    <label for="f-<?= h($k) ?>" class="bang"><?= h($label) ?></label>
    <input id="f-<?= h($k) ?>" name="<?= h($k) ?>" type="<?= $k === 'email' ? 'email' : 'text' ?>" maxlength="120" value="<?= h($cfg[$k]) ?>">
    <?php endforeach; ?>
    <div class="btn-row"><button type="submit" class="pull-btn bang">Save</button></div>
  </form>
  <p class="small">Episodes out is counted for you from admin > Episodes (published ones).</p>
</section>
<?php dtm_page_end();
