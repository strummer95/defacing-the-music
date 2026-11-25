<?php
// Connect YouTube Analytics for admin > Stats: Google sign-in (yt-analytics.readonly) for shares and watch time.
// The OAuth client (google_client_id) needs https://defacingthemusic.com/admin/analytics-google.php as an
// Authorized redirect URI, and the YouTube Analytics API enabled in the same Google Cloud project.
require __DIR__ . '/../lib/analytics.php';
$user = dtm_require_admin();
dtm_session();
$cfg = dtm_an_cfg();
$fail = function (string $m) { header('Location: analytics.php?g_err=' . rawurlencode($m) . '#settings'); exit; };

if (isset($_GET['code']) || isset($_GET['error'])) {
  $want = (string)($_SESSION['g_state'] ?? '');
  unset($_SESSION['g_state']);
  if (isset($_GET['error'])) $fail('Google sign-in was cancelled or refused: ' . mb_substr((string)$_GET['error'], 0, 200));
  if ($want === '' || !hash_equals($want, (string)($_GET['state'] ?? ''))) $fail('That Google sign-in link expired. Click Connect again.');
  try { dtm_an_g_tokens(['grant_type' => 'authorization_code', 'code' => (string)$_GET['code'], 'redirect_uri' => dtm_an_g_redirect()]); }
  catch (RuntimeException $e) { $fail($e->getMessage()); }
  header('Location: analytics.php?saved=g'); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: analytics.php#settings'); exit; }
dtm_check_csrf();
if ($cfg['g_id'] === '' || $cfg['g_secret'] === '') $fail('Paste the Google client secret first.');
$_SESSION['g_state'] = bin2hex(random_bytes(16));
header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
  'client_id' => $cfg['g_id'], 'redirect_uri' => dtm_an_g_redirect(), 'response_type' => 'code', 'scope' => DTM_YTA_SCOPE,
  'access_type' => 'offline', 'prompt' => 'consent', 'state' => $_SESSION['g_state'],
]));
