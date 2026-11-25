<?php
// Admin auth for Defacing The Music: password login (works over plain http), Google sign-in (https only),
// sessions, CSRF, per-IP throttle. Admin accounts live in data/admins.json (never deployed over by pull.php).
// Secrets come from ../dtm-secrets.php (one folder above the site), never from git.

require_once __DIR__ . '/https.php';
const DTM_ROOT = __DIR__ . '/..';
date_default_timezone_set('America/Chicago');

function dtm_secrets(): array {
  static $cfg = null;
  if ($cfg === null) {
    $f = dirname(__DIR__, 2) . '/dtm-secrets.php';
    $cfg = is_file($f) ? (require $f) : [];
    if (!is_array($cfg)) $cfg = [];
  }
  return $cfg;
}

function dtm_is_https(): bool {
  return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || (($_SERVER['SERVER_PORT'] ?? '') === '443');
}

function dtm_session(): void {
  if (session_status() === PHP_SESSION_ACTIVE) return;
  session_name('dtm_admin');
  session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => dtm_is_https()]);
  session_start();
}

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ---------- data files (flock-locked JSON under data/) ----------
function dtm_data_dir(): string {
  $d = DTM_ROOT . '/data';
  if (!is_dir($d)) @mkdir($d, 0755, true);
  if (!is_file("$d/.htaccess")) @file_put_contents("$d/.htaccess", "Require all denied\n");
  return $d;
}

// Read-modify-write a JSON file under an exclusive lock. $fn gets the decoded array and returns the new one
// (or null to leave the file untouched). Returns whatever the file holds afterwards.
function dtm_json_update(string $name, ?callable $fn = null): array {
  $path = dtm_data_dir() . "/$name";
  if (!$fn) { // plain read: never create anything (creating fails when the hosting is full)
    if (!is_file($path)) return [];
    $fp = @fopen($path, 'r');
    if (!$fp) throw new RuntimeException("Cannot open data/$name");
    flock($fp, LOCK_SH);
    $data = json_decode(stream_get_contents($fp) ?: '[]', true);
    flock($fp, LOCK_UN); fclose($fp);
    return is_array($data) ? $data : [];
  }
  $fp = @fopen($path, 'c+');
  if (!$fp) throw new RuntimeException("Cannot open data/$name (is the web hosting full?)");
  flock($fp, LOCK_EX);
  $data = json_decode(stream_get_contents($fp) ?: '[]', true);
  if (!is_array($data)) $data = [];
  $new = $fn($data);
  if (is_array($new)) {
    // Write the new version to a temp file first, then swap it in. If the hosting is full the temp write
    // fails and the old file is left exactly as it was (the old way emptied the file first, then failed).
    $json = json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $tmp = "$path.tmp-" . bin2hex(random_bytes(4)) . '.json'; // ends in .json, so data/.htaccess blocks it too
    $ok = $json !== false && @file_put_contents($tmp, $json) === strlen($json) && @rename($tmp, $path);
    if (!$ok) {
      @unlink($tmp); flock($fp, LOCK_UN); fclose($fp);
      throw new RuntimeException("Could not save data/$name (is the web hosting full?). Nothing was changed.");
    }
    $data = $new;
  }
  flock($fp, LOCK_UN); fclose($fp);
  return $data;
}
function dtm_json_read(string $name): array { return dtm_json_update($name); }

// ---------- admins ----------
function dtm_admins(): array { return dtm_json_read('admins.json'); }

function dtm_find_admin(string $login): ?array {
  $login = strtolower(trim($login));
  foreach (dtm_admins() as $a) {
    if (strtolower($a['email']) === $login || strtolower($a['username'] ?? '') === $login) return $a;
  }
  return null;
}

function dtm_google_emails(): array {
  return array_map('strtolower', (array)(dtm_secrets()['admin_emails'] ?? []));
}

// ---------- CSRF ----------
function dtm_csrf(): string {
  dtm_session();
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
  return $_SESSION['csrf'];
}
function dtm_csrf_field(): string { return '<input type="hidden" name="csrf" value="' . h(dtm_csrf()) . '">'; }
function dtm_check_csrf(): void {
  dtm_session();
  if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
    http_response_code(400); exit('Form expired. Go back, refresh, and try again.');
  }
}

// ---------- throttle (per IP) ----------
const DTM_MAX_FAILS = 5;
const DTM_WINDOW = 900; // 15 minutes

function dtm_ip(): string { return $_SERVER['REMOTE_ADDR'] ?? 'unknown'; }

function dtm_throttled(): bool {
  $t = dtm_json_read('throttle.json');
  $row = $t[dtm_ip()] ?? null;
  return $row && $row['n'] >= DTM_MAX_FAILS && time() - $row['t'] < DTM_WINDOW;
}
function dtm_fail(): void {
  $ip = dtm_ip();
  dtm_json_update('throttle.json', function ($t) use ($ip) {
    $now = time();
    foreach ($t as $k => $r) if ($now - $r['t'] > DTM_WINDOW) unset($t[$k]);
    $r = $t[$ip] ?? ['n' => 0, 't' => $now];
    $t[$ip] = ['n' => $r['n'] + 1, 't' => $now];
    return $t;
  });
}
function dtm_clear_fails(): void {
  $ip = dtm_ip();
  dtm_json_update('throttle.json', function ($t) use ($ip) { unset($t[$ip]); return $t; });
}

// ---------- login state ----------
function dtm_login_as(string $email, string $name, string $via): void {
  dtm_session();
  session_regenerate_id(true);
  $_SESSION['admin'] = ['email' => $email, 'name' => $name, 'via' => $via];
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function dtm_user(): ?array { dtm_session(); return $_SESSION['admin'] ?? null; }
function dtm_require_admin(): array {
  $u = dtm_user();
  if (!$u) { header('Location: login.php'); exit; }
  return $u;
}
function dtm_logout(): void {
  dtm_session();
  $_SESSION = [];
  $p = session_get_cookie_params();
  setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'httponly' => true, 'samesite' => 'Lax', 'secure' => $p['secure']]);
  session_destroy();
}

// ---------- Google ----------
function dtm_google_ready(): bool { return dtm_is_https() && !empty(dtm_secrets()['google_client_id']); }

// Verifies a Google ID token with Google's tokeninfo endpoint. Returns [email, name] or null.
function dtm_google_verify(string $idToken): ?array {
  $client = (string)(dtm_secrets()['google_client_id'] ?? '');
  if ($client === '' || $idToken === '') return null;
  $ch = curl_init('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken));
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
  $body = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
  if ($code !== 200 || !$body) return null;
  $t = json_decode($body, true);
  if (!is_array($t)) return null;
  if (($t['aud'] ?? '') !== $client) return null;
  if (!in_array($t['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true)) return null;
  if (($t['email_verified'] ?? '') !== 'true' && ($t['email_verified'] ?? null) !== true) return null;
  if ((int)($t['exp'] ?? 0) < time()) return null;
  $email = strtolower($t['email'] ?? '');
  if ($email === '' || !in_array($email, dtm_google_emails(), true)) return null;
  return [$email, $t['name'] ?? $email];
}

// ---------- shared admin page chrome ----------
function dtm_page_start(string $title, ?array $user = null, bool $pullButton = false): void {
  $csrf = $user ? dtm_csrf() : '';
  ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= h($title) ?> | Defacing the Music admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&family=Oswald:wght@500;700&family=Inter:wght@400;600;800&family=Metal+Mania&family=Bangers&family=Archivo:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon-32.png?v=2">
<link rel="stylesheet" href="../assets/site.css?v=<?= @filemtime(DTM_ROOT . '/assets/site.css') ?>">
<link rel="stylesheet" href="../assets/admin.css?v=<?= @filemtime(DTM_ROOT . '/assets/admin.css') ?>">
<link rel="stylesheet" href="../assets/admin-backstage.css?v=<?= @filemtime(DTM_ROOT . '/assets/admin-backstage.css') ?>">
</head>
<body class="admin">
<header class="admin-bar">
  <a href="index.php" class="wordmark metal"><img src="../assets/logo-192.png" alt="" width="40" height="40" class="logo-mark">Defacing the Music</a>
  <?php if ($user): ?>
  <div class="admin-bar-right">
    <a href="account.php" class="admin-user bang"><?= h($user['name']) ?></a>
    <?php if ($pullButton): ?>
    <form method="post" action="deploy.php" id="pull-form">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="back" value="<?= h((string)($_SERVER['REQUEST_URI'] ?? '')) ?>">
      <button type="submit" class="pull-btn bang" onclick="this.disabled=true;this.textContent='Pulling...';this.form.submit()">Pull latest</button>
    </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</header>
<?php if ($user): echo dtm_admin_nav(); endif; ?>
<main class="admin-main">
<?php if ($user) echo dtm_pull_flash(); ?>
<?php
}
// The menu bar (Duck, Nov 25): Episodes, Side Stories, Guest List, Stats, and a Backstage menu with Uploads, Host Guide,
// Sponsors & Media Kit and Site Looks.
function dtm_admin_nav(): string {
  $page = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
  $side = false;
  if (in_array($page, ['scripts.php', 'prompter.php', 'images.php', 'edit.php'], true) && function_exists('dtm_script')) {
    $sid = (string)($_GET['edit'] ?? $_GET['id'] ?? $_GET['script'] ?? '');
    if ($page !== 'edit.php' && $sid !== '' && ($sc = dtm_script($sid))) $side = ($sc['kind'] ?? 'episode') !== 'episode';
    if ($page === 'scripts.php' && isset($_GET['new'])) $side = ($_GET['kind'] ?? 'episode') !== 'episode';
  }
  $items = [
    'episodes' => ['Episodes', 'episodes.php', ['episodes.php', 'scripts.php', 'prompter.php', 'images.php', 'edit.php']],
    'side' => ['Side Stories', 'side.php', ['side.php', 'news.php']],
    'guests' => ['Guest List', 'guests.php', ['guests.php']],
    'analytics' => ['Stats', 'analytics.php', ['analytics.php', 'stats-videos.php']],
  ];
  // Everything else lives under one "Backstage" menu (Duck, Nov 25).
  $more = [
    ['Uploads', 'uploads.php', ['uploads.php']],
    ['Host Guide', 'guide.php', ['guide.php']],
    ['Sponsors & Media Kit', 'sponsors.php', ['sponsors.php']],
    ['Site Looks', 'looks.php', ['looks.php']],
  ];
  $o = '<nav class="admin-nav" aria-label="Admin"><div class="admin-nav-in">';
  foreach ($items as $k => [$label, $href, $pages]) {
    $on = in_array($page, $pages, true);
    if ($k === 'episodes' && $side) $on = false;
    if ($k === 'side' && $side) $on = true;
    $o .= '<a href="' . h($href) . '"' . ($on ? ' class="on" aria-current="page"' : '') . '>' . h($label) . '</a>';
  }
  $moreOn = false; $links = '';
  foreach ($more as [$label, $href, $pages]) {
    $on = in_array($page, $pages, true); $moreOn = $moreOn || $on;
    $links .= '<a href="' . h($href) . '"' . ($on ? ' class="on" aria-current="page"' : '') . '>' . h($label) . '</a>';
  }
  $o .= '<details class="nav-more' . ($moreOn ? ' on' : '') . '"><summary>Backstage</summary><div class="nav-more-menu">' . $links . '</div></details>';
  $o .= '<script>document.addEventListener("click",e=>{document.querySelectorAll(".nav-more[open]").forEach(d=>{if(!d.contains(e.target))d.removeAttribute("open")})});</script>';
  return $o . '</div></nav>';
}
// After Pull latest: a one-line result at the top of whatever page Duck was on (details tucked away).
function dtm_pull_flash(): string {
  dtm_session();
  $f = $_SESSION['flash'] ?? null;
  if (!$f) return '';
  unset($_SESSION['flash']);
  $ok = !empty($f['ok']);
  return '<details class="notice ' . ($ok ? 'ok' : 'err') . ' bang pull-flash"' . ($ok ? '' : ' open') . '><summary>' . ($ok ? 'Pulled. The site is up to date.' : 'Pull failed. Tap for details.') . '</summary><pre class="pull-output">' . h((string)($f['msg'] ?? '')) . '</pre></details>';
}
// Where Pull latest sends Duck back to: the admin page he was on, never anywhere else.
function dtm_safe_back(string $back): string {
  $back = (string)strtok($back, '#');
  if (!preg_match('#^/admin/[A-Za-z0-9_.-]+\.php(\?[^\s<>"\'`]*)?$#', $back) || str_contains($back, 'deploy.php') || str_contains($back, '..')) return 'index.php';
  return $back;
}
function dtm_page_end(): void { echo "</main>\n</body>\n</html>\n"; }
