<?php
// Admin auth for Defacing The Music: password login (works over plain http), Google sign-in (https only),
// sessions, CSRF, per-IP throttle. Admin accounts live in data/admins.json (never deployed over by pull.php).
// Secrets come from ../dtm-secrets.php (one folder above the site), never from git.

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
  $fp = fopen(dtm_data_dir() . "/$name", 'c+');
  if (!$fp) throw new RuntimeException("Cannot open data/$name");
  flock($fp, $fn ? LOCK_EX : LOCK_SH);
  $data = json_decode(stream_get_contents($fp) ?: '[]', true);
  if (!is_array($data)) $data = [];
  if ($fn) {
    $new = $fn($data);
    if (is_array($new)) {
      ftruncate($fp, 0); rewind($fp);
      fwrite($fp, json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
      fflush($fp);
      $data = $new;
    }
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
<link href="https://fonts.googleapis.com/css2?family=Metal+Mania&family=Bangers&family=Archivo:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon-32.png">
<link rel="stylesheet" href="../assets/site.css?v=<?= @filemtime(DTM_ROOT . '/assets/site.css') ?>">
<link rel="stylesheet" href="../assets/admin.css?v=<?= @filemtime(DTM_ROOT . '/assets/admin.css') ?>">
</head>
<body class="admin">
<header class="admin-bar">
  <a href="index.php" class="wordmark metal"><img src="../assets/logo-192.png" alt="" width="40" height="40" class="logo-mark">Defacing the Music</a>
  <?php if ($user): ?>
  <div class="admin-bar-right">
    <a href="scripts.php" class="admin-user bang">Scripts</a>
    <a href="uploads.php" class="admin-user bang">Uploads</a>
    <a href="account.php" class="admin-user bang"><?= h($user['name']) ?></a>
    <?php if ($pullButton): ?>
    <form method="post" action="deploy.php" id="pull-form">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <button type="submit" class="pull-btn bang" onclick="this.disabled=true;this.textContent='Pulling...';this.form.submit()">Pull latest</button>
    </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</header>
<main class="admin-main">
<?php
}
function dtm_page_end(): void { echo "</main>\n</body>\n</html>\n"; }
