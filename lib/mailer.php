<?php
// Guest List email: sends through Duck's Gmail (smtp.gmail.com, an app password), one copy per subscriber,
// with an unsubscribe link and one-click List-Unsubscribe headers. Settings live only in ../dtm-secrets.php:
//   'gmail_user' => 'name@gmail.com', 'gmail_app_password' => '16 letters', 'mail_key' => random (unsubscribe links)
require_once __DIR__ . '/auth.php';

const DTM_MAIL_DAILY_CAP = 450; // Gmail allows about 500 a day from a normal account; stay under it

function dtm_mail_cfg(): array {
  $s = dtm_secrets();
  return ['user' => trim((string)($s['gmail_user'] ?? '')), 'pass' => str_replace(' ', '', (string)($s['gmail_app_password'] ?? '')), 'key' => (string)($s['mail_key'] ?? '')];
}
function dtm_mail_ready(): bool { $c = dtm_mail_cfg(); return $c['user'] !== '' && $c['pass'] !== '' && $c['key'] !== ''; }

function dtm_subscribers(): array {
  $list = array_values(array_filter(dtm_json_read('subscribers.json'), fn($r) => is_array($r) && !empty($r['email'])));
  usort($list, fn($a, $b) => strcmp((string)($b['at'] ?? ''), (string)($a['at'] ?? '')));
  return $list;
}
function dtm_subscriber_remove(string $email): bool {
  $email = strtolower(trim($email)); $hit = false;
  dtm_json_update('subscribers.json', function ($list) use ($email, &$hit) {
    $out = [];
    foreach ($list as $r) { if (strtolower((string)($r['email'] ?? '')) === $email) { $hit = true; continue; } $out[] = $r; }
    return $out;
  });
  return $hit;
}

function dtm_unsub_token(string $email): string { return substr(hash_hmac('sha256', strtolower(trim($email)), dtm_mail_cfg()['key']), 0, 32); }
function dtm_unsub_url(string $email): string {
  return 'https://defacingthemusic.com/unsubscribe.php?e=' . rawurlencode(strtolower(trim($email))) . '&t=' . dtm_unsub_token($email);
}

// Saves the Gmail settings into ../dtm-secrets.php (the only place secrets live). Writes a temp file next to it,
// checks it loads and still has every old setting, then swaps it in. Returns '' or an error message.
function dtm_mail_save_secrets(string $user, string $pass): string {
  $f = dirname(__DIR__, 2) . '/dtm-secrets.php';
  if (!is_file($f)) return 'The secrets file (dtm-secrets.php, one folder above the site) is missing.';
  $old = require $f;
  if (!is_array($old)) return 'The secrets file looks broken, so I did not touch it.';
  $new = $old;
  $new['gmail_user'] = $user;
  if ($pass !== '') $new['gmail_app_password'] = $pass;
  if (empty($new['mail_key'])) $new['mail_key'] = bin2hex(random_bytes(24));
  $php = "<?php\n// Server secrets for Defacing the Music. Never put this file in git.\nreturn " . var_export($new, true) . ";\n";
  $tmp = dirname($f) . '/.dtm-secrets.tmp-' . bin2hex(random_bytes(4)) . '.php';
  if (@file_put_contents($tmp, $php) !== strlen($php)) { @unlink($tmp); return "The site can't write next to the secrets file. Add the two lines by hand (see below)."; }
  $check = (function ($p) { return require $p; })($tmp);
  if (!is_array($check) || array_diff_key($old, $check)) { @unlink($tmp); return 'Something went wrong building the new secrets file, so nothing was changed.'; }
  @chmod($tmp, 0600);
  if (!@rename($tmp, $f)) { @unlink($tmp); return "The site can't replace the secrets file. Add the two lines by hand (see below)."; }
  if (function_exists('opcache_invalidate')) @opcache_invalidate($f, true);
  return '';
}

// A tiny SMTP client for Gmail (no Composer on this hosting). One connection, many messages.
final class DtmSmtp {
  private $s;
  public function __construct(private array $cfg) {}
  private function read(): string {
    $all = '';
    while (($l = fgets($this->s, 1024)) !== false) { $all .= $l; if (strlen($l) < 4 || $l[3] === ' ') break; }
    return $all;
  }
  private function cmd(string $c, string $want, bool $hide = false): string {
    fwrite($this->s, $c . "\r\n");
    $r = $this->read();
    if (!str_starts_with($r, $want)) throw new RuntimeException(($hide ? 'Login' : strtok($c, ' ')) . ' failed: ' . trim($r));
    return $r;
  }
  public function open(): void {
    $this->s = @stream_socket_client('ssl://smtp.gmail.com:465', $no, $err, 20);
    if (!$this->s) throw new RuntimeException("Can't reach Gmail from the server ($err). The hosting may block outgoing email connections.");
    stream_set_timeout($this->s, 30);
    if (!str_starts_with($this->read(), '220')) throw new RuntimeException('Gmail did not say hello.');
    $this->cmd('EHLO defacingthemusic.com', '250');
    $this->cmd('AUTH LOGIN', '334');
    $this->cmd(base64_encode($this->cfg['user']), '334', true);
    try { $this->cmd(base64_encode($this->cfg['pass']), '235', true); }
    catch (RuntimeException $e) {
      // Gmail's own reason (never contains the password), e.g. "535 5.7.8 Username and Password not accepted".
      $why = trim(preg_replace('/^\d{3}[- ]/', '', strtok(substr($e->getMessage(), 14), "\n")));
      $why = trim(preg_replace('/For more information.*$/i', '', $why));
      throw new RuntimeException('Gmail turned down the login for ' . $this->cfg['user'] . '. Gmail said: "' . rtrim($why, '.') . '". Usually the app password was made on a different Google account than this address, or it got mistyped. Make a new one while signed in as ' . $this->cfg['user'] . ' and save it again.');
    }
  }
  public function send(string $to, string $subject, string $text, string $html, string $unsub): void {
    $b = 'dtm' . bin2hex(random_bytes(8));
    $from = $this->cfg['user'];
    $h = [
      'Date: ' . date('r'),
      'From: =?UTF-8?B?' . base64_encode('Defacing the Music') . "?= <$from>",
      "To: <$to>",
      'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
      'Message-ID: <' . bin2hex(random_bytes(12)) . '@defacingthemusic.com>',
      "List-Unsubscribe: <$unsub>",
      'List-Unsubscribe-Post: List-Unsubscribe=One-Click',
      'MIME-Version: 1.0',
      "Content-Type: multipart/alternative; boundary=\"$b\"",
    ];
    $body = "--$b\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text))
      . "--$b\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html)) . "--$b--\r\n";
    $this->cmd("MAIL FROM:<$from>", '250');
    $this->cmd("RCPT TO:<$to>", '25');
    $this->cmd('DATA', '354');
    fwrite($this->s, implode("\r\n", $h) . "\r\n\r\n" . $body . ".\r\n"); // base64 lines never start with a dot
    $r = $this->read();
    if (!str_starts_with($r, '250')) throw new RuntimeException('Gmail refused the message: ' . trim($r));
  }
  public function close(): void { if ($this->s) { @fwrite($this->s, "QUIT\r\n"); @fclose($this->s); $this->s = null; } }
}

// Duck writes plain text. Links become clickable in the HTML version; both get the unsubscribe footer.
function dtm_mail_bodies(string $body, string $email): array {
  $unsub = dtm_unsub_url($email);
  $foot = "You're getting this because you signed up for the Guest List at defacingthemusic.com.";
  $text = rtrim($body) . "\n\n--\nDefacing the Music\n$foot\nUnsubscribe: $unsub\n";
  $paras = '';
  foreach (preg_split('/\R{2,}/', trim($body)) as $p) {
    $p = nl2br(h($p));
    $p = preg_replace('#(https?://[^\s<]+)#', '<a href="$1" style="color:#E4251B">$1</a>', $p);
    $paras .= '<p style="margin:0 0 16px">' . $p . '</p>';
  }
  $html = '<!DOCTYPE html><html><body style="margin:0;background:#161518;padding:24px 12px">'
    . '<div style="max-width:560px;margin:0 auto;background:#F6F3EC;color:#111;border-radius:6px;padding:28px 26px;font:16px/1.55 Arial,Helvetica,sans-serif">'
    . '<p style="margin:0 0 18px;font:bold 13px Arial,sans-serif;letter-spacing:2px;text-transform:uppercase;color:#E4251B">Defacing the Music</p>'
    . $paras
    . '<p style="margin:26px 0 0;padding-top:14px;border-top:1px solid #ddd6c8;font-size:12px;color:#6a645a">' . h($foot) . ' <a href="' . h($unsub) . '" style="color:#6a645a">Unsubscribe</a></p>'
    . '</div></body></html>';
  return [$text, $html, $unsub];
}

// Sends in the last 24 hours, across all mailings (for the daily cap).
function dtm_mail_sent_today(array $log): int {
  $n = 0; $since = time() - 86400;
  foreach ($log['sends'] ?? [] as $t) if ($t >= $since) $n++;
  return $n;
}
