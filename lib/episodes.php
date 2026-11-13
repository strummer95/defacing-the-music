<?php
// Episodes: numbered entries in data/episodes.json, each optionally linked to a script in the library.
// "Make it an episode" turns a library script into the next numbered episode.
require_once __DIR__ . '/scripts.php';

const DTM_EPISODE_STATUSES = ['planned' => 'Planned', 'recorded' => 'Recorded', 'published' => 'Published'];

// First run: create Episode 0 (already out) and an episode for any script that already carries a number.
function dtm_episodes_bootstrap(): void {
  if (is_file(dtm_data_dir() . '/episodes.json')) return;
  $scripts = dtm_scripts();
  dtm_json_update('episodes.json', function ($eps) use ($scripts) {
    if ($eps) return null;
    $rows = [];
    foreach ($scripts as $s) {
      $n = (string)$s['episode'];
      if ($n === '') continue;
      $isZero = $n === '0';
      $rows[] = [
        'id' => bin2hex(random_bytes(6)), 'number' => $n, 'title' => $s['title'], 'subtitle' => $s['subtitle'] ?? '',
        'status' => $isZero ? 'published' : 'planned', 'date' => $isZero ? '2026-11-13' : '',
        'youtube' => $isZero ? 'https://youtu.be/FQK-XMkoajA' : '',
        'spotify' => $isZero ? 'https://open.spotify.com/show/2WZWOUIt4e7I7IIQRvh6fx' : '',
        'script_id' => $s['id'], 'notes' => '', 'updated' => date('c'),
      ];
    }
    if (!$rows) $rows[] = [
      'id' => bin2hex(random_bytes(6)), 'number' => '0', 'title' => 'Due To Unforeseen Circumstances', 'subtitle' => 'Who Gave This Guy a Mic?',
      'status' => 'published', 'date' => '2026-11-13', 'youtube' => 'https://youtu.be/FQK-XMkoajA',
      'spotify' => 'https://open.spotify.com/show/2WZWOUIt4e7I7IIQRvh6fx', 'script_id' => '', 'notes' => '', 'updated' => date('c'),
    ];
    return $rows;
  });
}

function dtm_episodes(): array {
  dtm_episodes_bootstrap();
  $list = dtm_json_read('episodes.json');
  usort($list, fn($a, $b) => strnatcmp((string)$a['number'], (string)$b['number']));
  return $list;
}

function dtm_episode(string $id): ?array {
  foreach (dtm_episodes() as $e) if (hash_equals($e['id'], $id)) return $e;
  return null;
}

function dtm_next_episode_number(): string {
  $max = -1;
  foreach (dtm_episodes() as $e) if (is_numeric($e['number'])) $max = max($max, (int)$e['number']);
  return (string)($max + 1);
}

// Keeps a script's "episode" field in sync with the episode it belongs to ('' = back in the library).
function dtm_script_set_episode(string $scriptId, string $number): void {
  if ($scriptId === '') return;
  dtm_json_update('scripts.json', function ($l) use ($scriptId, $number) {
    foreach ($l as &$s) if ($s['id'] === $scriptId) { $s['episode'] = $number; $s['updated'] = date('c'); }
    return $l;
  });
}

// Library script -> next numbered episode. Returns the new episode id, or null if the script is gone/already used.
function dtm_make_episode_from_script(string $scriptId): ?string {
  $s = dtm_script($scriptId);
  if (!$s || (string)$s['episode'] !== '') return null;
  $num = dtm_next_episode_number();
  $id = bin2hex(random_bytes(6));
  dtm_json_update('episodes.json', function ($l) use ($s, $num, $id) {
    $l[] = ['id' => $id, 'number' => $num, 'title' => $s['title'], 'subtitle' => $s['subtitle'] ?? '', 'status' => 'planned',
            'date' => '', 'youtube' => '', 'spotify' => '', 'script_id' => $s['id'], 'notes' => '', 'updated' => date('c')];
    return $l;
  });
  dtm_script_set_episode($s['id'], $num);
  return $id;
}

// Validates admin POST fields and saves. Returns [ok, error, id].
function dtm_save_episode(array $in, string $id): array {
  $ep = dtm_episode($id);
  if (!$ep) return [false, 'That episode no longer exists.', null];
  $num = trim((string)($in['number'] ?? ''));
  $title = trim((string)($in['title'] ?? ''));
  $subtitle = trim((string)($in['subtitle'] ?? ''));
  $status = (string)($in['status'] ?? 'planned');
  $date = trim((string)($in['date'] ?? ''));
  $yt = trim((string)($in['youtube'] ?? ''));
  $sp = trim((string)($in['spotify'] ?? ''));
  $notes = trim((string)($in['notes'] ?? ''));
  $scriptId = (string)($in['script_id'] ?? '');
  if ($num === '' || mb_strlen($num) > 10) return [false, 'Episode number is required.', null];
  foreach (dtm_episodes() as $o) if ($o['id'] !== $id && (string)$o['number'] === $num) return [false, "Episode $num already exists.", null];
  if ($title === '' || mb_strlen($title) > 160) return [false, 'Title is required (160 characters max).', null];
  if (mb_strlen($subtitle) > 160) return [false, 'Subtitle is too long.', null];
  if (!isset(DTM_EPISODE_STATUSES[$status])) $status = 'planned';
  if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return [false, 'Date should look like 2026-12-04.', null];
  foreach (['YouTube' => $yt, 'Spotify' => $sp] as $label => $u) {
    if ($u !== '' && !preg_match('#^https://#i', $u)) return [false, "$label link should start with https://", null];
  }
  if ($scriptId !== '') {
    $sc = dtm_script($scriptId);
    if (!$sc) return [false, 'That script no longer exists.', null];
    if ((string)$sc['episode'] !== '' && $sc['id'] !== $ep['script_id']) return [false, 'That script already belongs to episode ' . $sc['episode'] . '.', null];
  }
  if (mb_strlen($notes) > 2000) return [false, 'Notes are too long.', null];
  $oldScript = (string)$ep['script_id'];
  dtm_json_update('episodes.json', function ($l) use ($id, $num, $title, $subtitle, $status, $date, $yt, $sp, $notes, $scriptId) {
    foreach ($l as &$e) if ($e['id'] === $id) {
      $e = array_merge($e, ['number' => $num, 'title' => $title, 'subtitle' => $subtitle, 'status' => $status, 'date' => $date,
        'youtube' => $yt, 'spotify' => $sp, 'notes' => $notes, 'script_id' => $scriptId, 'updated' => date('c')]);
    }
    return $l;
  });
  if ($oldScript !== '' && $oldScript !== $scriptId) dtm_script_set_episode($oldScript, '');
  if ($scriptId !== '') dtm_script_set_episode($scriptId, $num);
  return [true, null, $id];
}

// Deleting an episode puts its script back in the library; the script itself is kept.
function dtm_delete_episode(string $id): void {
  $ep = dtm_episode($id);
  if (!$ep) return;
  dtm_json_update('episodes.json', fn($l) => array_values(array_filter($l, fn($e) => $e['id'] !== $id)));
  dtm_script_set_episode((string)$ep['script_id'], '');
}
