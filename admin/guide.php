<?php
// Host guide inside the admin, with the menu bar (Duck, Nov 23: only the teleprompter should hide the menu).
// The text lives in brand/guide/index.html (also at defacingthemusic.com/brand/guide/ for Pat's phone, no login).
require __DIR__ . '/../lib/auth.php';
$user = dtm_require_admin();
$html = (string)@file_get_contents(DTM_ROOT . '/brand/guide/index.html');
$a = strpos($html, '<nav class="toc">');
$b = strrpos($html, '</div></body>');
$body = ($a !== false && $b !== false && $b > $a) ? substr($html, $a, $b - $a) : '<p>The guide file is missing.</p>';
dtm_page_start('Host Guide', $user, true);
?>
<section class="admin-card guide">
  <h1 class="metal">Host Guide</h1>
  <p>For Duck and Pat. How an episode goes, what the cues mean, who does what, and the checklist before you hit record. Pat can keep the no-login copy open on his phone: <a href="/brand/guide/" target="_blank" rel="noopener">defacingthemusic.com/brand/guide</a></p>
  <?= $body ?>
</section>
<?php dtm_page_end();
