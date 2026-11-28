<?php
// B-Sides builder (Duck, Nov 28): don't pre-make B-Side episodes. List every single Off The Record story with
// its length, let Duck tick the ones he wants, and stitch them into one new B-Side script.
require_once __DIR__ . '/scripts.php';

const DTM_WPM = 150;

// Spoken words: everything outside [brackets] and ## headings.
function dtm_spoken_words(string $body): int {
  $t = preg_replace('/^##.*$/m', '', $body);
  $t = preg_replace('/\[[^\]]*\]/', '', (string)$t);
  return str_word_count((string)$t, 0, "'0123456789$");
}

// Single stories only: Off The Record scripts without their own cold open, not already out.
function dtm_bside_pool(): array {
  $out = [];
  foreach (dtm_scripts() as $s) {
    if ($s['kind'] !== 'otr' || $s['status'] === 'published') continue;
    if (str_contains((string)$s['body'], '## COLD OPEN')) continue; // a finished B-Side episode, not a story
    $w = dtm_spoken_words((string)$s['body']);
    $out[] = ['id' => $s['id'], 'title' => preg_replace('/^Off The Record:\s*/i', '', (string)$s['title']), 'subtitle' => (string)$s['subtitle'], 'words' => $w, 'min' => round($w / DTM_WPM, 1), 'status' => $s['status']];
  }
  usort($out, fn($a, $b) => strcasecmp($a['title'], $b['title']));
  return $out;
}

// Build one B-Side from the picked stories, in the order picked. Returns the new script id, or '' if nothing valid.
function dtm_bside_build(array $ids, string $title = ''): string {
  $byId = [];
  foreach (dtm_bside_pool() as $p) $byId[$p['id']] = $p;
  $scripts = [];
  foreach (dtm_scripts() as $s) $scripts[$s['id']] = $s;
  $parts = [];
  foreach ($ids as $id) { $id = (string)$id; if (isset($byId[$id]) && !in_array($id, $parts, true)) $parts[] = $id; }
  if (count($parts) < 2) return '';
  $names = array_map(fn($id) => $byId[$id]['title'], $parts);
  $count = ['', 'one', 'two', 'three', 'four', 'five', 'six'][count($parts)] ?? (string)count($parts);
  $body = "## COLD OPEN\n[COLD OPEN: about 30 seconds. One weird hook from each story, no answers, then the intro line.]\n\n";
  $body .= "I'm Duck, and due to unconfirmed reports, this is Defacing the Music: Off the Record.\n\n[THEME SONG, hard stop]\n\n";
  $body .= "Off the Record. " . ucfirst($count) . " stories you've heard about. Pat calls it, legend or fact, and then we find out.\n\n";
  foreach ($parts as $id) {
    $b = str_replace("\r\n", "\n", (string)$scripts[$id]['body']);
    $b = preg_replace('/^\s*\[STORY BLOCK[^\]]*\]\s*/', '', $b); // the "waiting for two more stories" note
    $b = trim((string)$b);
    if (!str_starts_with($b, '## ')) $b = '## ' . strtoupper($byId[$id]['title']) . "\n" . $b;
    $body .= $b . "\n\n";
  }
  $body .= "## SIGN-OFF\nThat's $count. Got one you want us to check? Send it in.\n\nI'm Duck. Due to unforeseen circumstances, I'll see you next time.\n\n[THEME SONG]\n";
  $title = trim($title);
  if ($title === '') $title = 'Off The Record: ' . implode(', ', $names);
  if (mb_strlen($title) > 120) $title = mb_substr($title, 0, 117) . '...';
  $sub = implode(', ', $names);
  if (mb_strlen($sub) > 160) $sub = mb_substr($sub, 0, 157) . '...';
  $new = bin2hex(random_bytes(6));
  dtm_json_update('scripts.json', function ($l) use ($new, $title, $sub, $body, $parts) {
    $l[] = ['id' => $new, 'episode' => '', 'kind' => 'otr', 'status' => 'draft', 'title' => $title, 'subtitle' => $sub, 'body' => $body, 'parts' => $parts, 'updated' => date('c')];
    return $l;
  });
  return $new;
}
