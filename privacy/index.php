<?php
require __DIR__ . '/../lib/legal-page.php';
dtm_legal_page('Privacy policy', '/privacy/', <<<'HTML'
<p class="small">Last updated November 25, 2026. Defacing the Music is run by Duck and Rabbit LLC.</p>
<h2>The short version</h2>
<p>We collect as little as we can, we don't sell anything about you, and we don't run ad trackers on this site.</p>
<h2>What we collect</h2>
<ul>
  <li><b>Email signups.</b> If you join the list on the home page, we keep your email address and when you signed up, so we can send you show emails. Every email has an unsubscribe link, and unsubscribing deletes you from the list.</li>
  <li><b>Sponsor messages.</b> If you use the form on the sponsor page, we keep what you send (name, company, email, message) so we can answer you.</li>
  <li><b>Server logs.</b> Our web host and Cloudflare keep standard technical logs (like IP address and browser) for security and to keep the site running.</li>
  <li><b>Videos.</b> Episodes on this site are YouTube embeds in privacy-enhanced mode (youtube-nocookie.com). YouTube's own privacy policy covers what happens when you press play.</li>
</ul>
<h2>Our social accounts</h2>
<p>Our private admin dashboard connects to our own YouTube, Facebook, Instagram and TikTok accounts to read the show's own stats (views, likes, comments, shares, followers). It only reads our accounts. It never posts, never reads anyone else's account, and never collects information about the people who watch.</p>
<h2>What we don't do</h2>
<ul>
  <li>We don't sell, rent or trade your information.</li>
  <li>We don't use ad trackers or tracking pixels on this site.</li>
  <li>We only share information with the services that run the site (our web host, Cloudflare, and our email provider) to do exactly that.</li>
</ul>
<h2>Your choices</h2>
<p>You can unsubscribe from any email at any time. To ask us to delete anything we have about you, send a message through the form on our <a href="/sponsor/#talk">contact page</a>.</p>
<h2>Kids</h2>
<p>The show is made for adults and this site isn't aimed at children under 13.</p>
<h2>Changes</h2>
<p>If this policy changes, we'll update this page and the date at the top.</p>
HTML);
