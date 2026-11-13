<?php
// Episode scripts for the teleprompter. Stored in data/scripts.json (flock-locked, never overwritten by a pull).
require_once __DIR__ . '/auth.php';

function dtm_scripts(): array {
  $list = dtm_json_read('scripts.json');
  if (!$list && !is_file(dtm_data_dir() . '/scripts.seeded')) {
    // First run: seed Episode 0 once.
    $body = (string)@file_get_contents(__DIR__ . '/seed/episode-00.txt');
    $list = dtm_json_update('scripts.json', function ($l) use ($body) {
      if ($l) return null;
      return [['id' => bin2hex(random_bytes(6)), 'episode' => '0', 'title' => 'Due To Unforeseen Circumstances', 'body' => $body, 'updated' => date('c')]];
    });
    @touch(dtm_data_dir() . '/scripts.seeded');
  }
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
