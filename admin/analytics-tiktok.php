<?php
// Connect TikTok for admin > Analytics: sends Duck to TikTok's login, then trades the returned code for tokens.
// The redirect URI registered in the TikTok app must be exactly https://defacingthemusic.com/admin/analytics-tiktok.php
require __DIR__ . '/../lib/analytics.php';
$user = dtm_require_admin();
dtm_session();
$cfg = dtm_an_cfg();
$fail = function (string $m) { header('Location: analytics.php?tt_err=' . rawurlencode($m) . '#settings'); exit; };

if (isset($_GET['code']) || isset($_GET['error'])) {
  $want = (string)($_SESSION['tt_state'] ?? '');
  unset($_SESSION['tt_state']);
  if (isset($_GET['error'])) $fail('TikTok login was cancelled or refused: ' . mb_substr((string)($_GET['error_description'] ?? $_GET['error']), 0, 200));
  if ($want === '' || !hash_equals($want, (string)($_GET['state'] ?? ''))) $fail('That TikTok login link expired. Click Connect TikTok again.');
  try { dtm_an_tt_tokens(['grant_type' => 'authorization_code', 'code' => (string)$_GET['code'], 'redirect_uri' => dtm_an_tt_redirect()]); }
  catch (RuntimeException $e) { $fail($e->getMessage()); }
  header('Location: analytics.php?saved=tt'); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: analytics.php#settings'); exit; }
dtm_check_csrf();
if ($cfg['tt_key'] === '' || $cfg['tt_secret'] === '') $fail('Paste the TikTok Client key and Client secret first.');
$_SESSION['tt_state'] = bin2hex(random_bytes(16));
header('Location: https://www.tiktok.com/v2/auth/authorize/?' . http_build_query([
  'client_key' => $cfg['tt_key'], 'scope' => DTM_TT_SCOPES, 'response_type' => 'code',
  'redirect_uri' => dtm_an_tt_redirect(), 'state' => $_SESSION['tt_state'],
]));
