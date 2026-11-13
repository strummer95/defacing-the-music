<?php
// Episode scripts for the teleprompter. Stored in data/scripts.json (flock-locked, never overwritten by a pull).
require_once __DIR__ . '/auth.php';

// Scripts that ship with the site (lib/seed/*.txt). Each is added once: if it was already added
// (even if Duck deleted or edited it since) or a script with that episode number exists, it's skipped.
const DTM_SEEDS = [
  'episode-00' => ['0', 'Due To Unforeseen Circumstances', 'Who Gave This Guy a Mic?'],
  'episode-01' => ['1', 'Due To Excessive Flash Powder', 'Who Gave The Who Explosives?'],
  'episode-02' => ['2', 'Due To The Venue Being Ripped Apart', 'Who Asked Fred Durst to Be a Therapist?'],
  'episode-03' => ['3', 'Due To a Possible Rabies Infection', 'Who Let That Guy Bring a Bat?'],
];

// sha1 of earlier shipped versions of each seed. If a script on the server still matches one of these
// exactly (Duck never edited it), it gets upgraded to the current seed text. Edited scripts are never touched.
const DTM_SEED_OLD = [
  'episode-00' => ['0a3c62561acfefa9b47142542b2566c6c40aaa2a', '7232a021773d9aa2a230518fa393f16447343a59', 'd61ecbce297a5442a2a42112b2a583f61d133e1b', '9fb19500807901e9cf1d32c3ebff9918e42ad46c', 'e40daceeba30539686c8fbe484a7c645b2e688fb'],
  'episode-01' => ['0ad617e1ddbffcc280e0a6c9760e8268d0fe2717', '7b3dbd18cefdbf8b0f5b173c5b673be91be4aeee', '305bfaa521a7e92ea540da27d1e71e68b29afc18', '4c534b033ad2e436832c01d045a51a5a74f49e1f', '6b7e320eed0fe6ac0402a18be46009af1e8e1442'],
  'episode-02' => ['9699dcc012bd232b28aec8f657c1482d538d61f5', '687fbe72cc2458ddad679552cf3aa2290c9f9e48', 'b76c280a63359aa13c883bc417f09f6adddab2c5', '6cd32e7d91cb8997871fa61fe41049777ec0633b', '753f5b89fd82ccc4eabdfa52a5f06e4ff26b92f9'],
  'episode-03' => ['a95c81044af6ccae27de7c120ab6ccbf6f91c375', 'c4f5e7c8a43992869c40ad4fae986b8143646781', 'c02bf5dcf4b3383c30fad282fe5470e99eec82f1', '49c09096b60f68d41f99caf79d6547a3d0d968c2', '5d356b15f96b61936f8fda8b601284b2f4dafc4e'],
];

function dtm_upgrade_seeds(): void {
  $list = dtm_json_read('scripts.json');
  $need = false;
  foreach ($list as $s) {
    foreach (DTM_SEED_OLD as $key => $old) {
      if ((string)$s['episode'] === DTM_SEEDS[$key][0] && in_array(sha1((string)$s['body']), $old, true)) { $need = true; break 2; }
    }
  }
  if (!$need) return;
  dtm_json_update('scripts.json', function ($list) {
    foreach ($list as &$s) {
      foreach (DTM_SEED_OLD as $key => $old) {
        if ((string)$s['episode'] !== DTM_SEEDS[$key][0] || !in_array(sha1((string)$s['body']), $old, true)) continue;
        $body = (string)@file_get_contents(__DIR__ . "/seed/$key.txt");
        if ($body !== '') { $s['body'] = $body; $s['updated'] = date('c'); }
      }
    }
    return $list;
  });
}

// Seeded episodes that predate subtitles get theirs once. A subtitle Duck set (even blank) is kept.
function dtm_seed_subtitles(): void {
  $map = [];
  foreach (DTM_SEEDS as $row) $map[$row[0]] = $row[2];
  $need = false;
  foreach (dtm_json_read('scripts.json') as $s) if (!array_key_exists('subtitle', $s) && isset($map[(string)$s['episode']])) { $need = true; break; }
  if (!$need) return;
  dtm_json_update('scripts.json', function ($list) use ($map) {
    foreach ($list as &$s) if (!array_key_exists('subtitle', $s) && isset($map[(string)$s['episode']])) $s['subtitle'] = $map[(string)$s['episode']];
    return $list;
  });
}

function dtm_seed_scripts(): void {
  dtm_upgrade_seeds();
  dtm_seed_subtitles();
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
      [$ep, $title, $subtitle] = DTM_SEEDS[$key];
      if (in_array($ep, $eps, true)) continue;
      $body = (string)@file_get_contents(__DIR__ . "/seed/$key.txt");
      if ($body === '') continue;
      $list[] = ['id' => bin2hex(random_bytes(6)), 'episode' => $ep, 'title' => $title, 'subtitle' => $subtitle, 'body' => $body, 'updated' => date('c')];
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
