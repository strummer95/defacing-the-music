<?php
// Episode scripts for the teleprompter. Stored in data/scripts.json (flock-locked, never overwritten by a pull).
require_once __DIR__ . '/auth.php';

// Scripts that ship with the site (lib/seed/*.txt). Each is added once: if it was already added
// (even if Duck deleted or edited it since) or a script with that episode number exists, it's skipped.
const DTM_SEEDS = [
  'episode-00' => ['0', 'Due To Unforeseen Circumstances'],
  'episode-01' => ['1', 'Due To Excessive Flash Powder'],
  'episode-02' => ['2', 'Due To The Venue Being Ripped Apart'],
  'episode-03' => ['3', 'Due To a Possible Rabies Infection'],
];

function dtm_seed_scripts(): void {
  $marker = dtm_data_dir() . '/scripts.seeded';
  $done = [];
  if (is_file($marker)) {
    $raw = trim((string)@file_get_contents($marker));
    $done = $raw === '' ? ['episode-00'] : (json_decode($raw, true) ?: ['episode-00']); // old empty marker = ep 0 seeded
  }
  $todo = array_diff(array_keys(DTM_SEEDS), $done);
  if (!$todo) return;
  dtm_json_update('scripts.json', function ($list) use ($todo) {
    $eps = array_map(fn($s) => (string)$s['episode'], $list);
    foreach ($todo as $key) {
      [$ep, $title] = DTM_SEEDS[$key];
      if (in_array($ep, $eps, true)) continue;
      $body = (string)@file_get_contents(__DIR__ . "/seed/$key.txt");
      if ($body === '') continue;
      $list[] = ['id' => bin2hex(random_bytes(6)), 'episode' => $ep, 'title' => $title, 'body' => $body, 'updated' => date('c')];
    }
    return $list;
  });
  @file_put_contents($marker, json_encode(array_values(array_unique(array_merge($done, $todo)))));
}

function dtm_scripts(): array {
  dtm_seed_scripts();
  $list = dtm_json_read('scripts.json');
  usort($list, fn($a, $b) => strnatcmp((string)$a['episode'], (string)$b['episode']));
  return $list;
}

function dtm_script(string $id): ?array {
  foreach (dtm_scripts() as $s) if (hash_equals($s['id'], $id)) return $s;
  return null;
}

// Plain text -> reader HTML. "## " lines are headings; [ ... ] cues are highlighted. Everything is escaped.
function dtm_script_html(string $body): string {
  $out = [];
  foreach (preg_split('/\R/', $body) as $line) {
    $t = trim($line);
    if ($t === '') { $out[] = '<div class="gap"></div>'; continue; }
    if (str_starts_with($t, '## ')) { $out[] = '<h2>' . h(substr($t, 3)) . '</h2>'; continue; }
    $html = preg_replace('/\[([^\]\n]{1,300})\]/', '<span class="cue">[$1]</span>', h($t));
    $out[] = '<p>' . $html . '</p>';
  }
  return implode("\n", $out);
}
