<?php
// The Episodes and Side Stories lists (Duck, Nov 23): published first, then ready, then drafts.
// Every row carries what the row buttons need (script id, episode id, transcript project).
require_once __DIR__ . '/cutaways.php';
require_once __DIR__ . '/edits.php';

const DTM_BOARD_GROUPS = ['published' => 'Published', 'ready' => 'Ready', 'draft' => 'Drafts'];

function dtm_board(string $which): array {
  $out = ['published' => [], 'ready' => [], 'draft' => []];
  $scripts = dtm_scripts();
  $byId = []; foreach ($scripts as $s) $byId[$s['id']] = $s;
  $edits = []; foreach (dtm_edits() as $p) if (($p['script_id'] ?? '') !== '') $edits[$p['script_id']] = $p['id'];
  $row = fn(?array $s, ?array $e) => [
    'script' => $s, 'episode' => $e,
    'title' => $e['title'] ?? $s['title'] ?? '', 'subtitle' => $e['subtitle'] ?? $s['subtitle'] ?? '',
    'kind' => $s['kind'] ?? 'episode', 'youtube' => ($e['youtube'] ?? '') ?: ($s['youtube'] ?? ''), 'date' => ($e['date'] ?? '') ?: ($s['date'] ?? ''),
    'status' => $e ? (DTM_EPISODE_STATUSES[$e['status']] ?? 'Planned') : (DTM_SCRIPT_STATUSES[$s['status']] ?? 'Draft'),
    'edit' => $s ? ($edits[$s['id']] ?? '') : '',
    'happened' => (string)($s['story_date'] ?? ''),
  ];
  if ($which === 'main') {
    foreach (dtm_episodes() as $e) {
      $s = $byId[$e['script_id']] ?? null;
      $out[$e['status'] === 'published' ? 'published' : 'ready'][] = $row($s, $e);
    }
    foreach ($scripts as $s) {
      if ($s['kind'] !== 'episode' || (string)$s['episode'] !== '') continue;
      $out[in_array($s['status'], ['ready', 'recorded', 'published'], true) ? 'ready' : 'draft'][] = $row($s, null);
    }
    usort($out['published'], fn($a, $b) => strnatcmp((string)$b['episode']['number'], (string)$a['episode']['number'])); // newest first
  } else {
    foreach ($scripts as $s) {
      if (!in_array($s['kind'], DTM_SIDE_KINDS, true)) continue;
      $g = $s['status'] === 'published' ? 'published' : (in_array($s['status'], ['ready', 'recorded'], true) ? 'ready' : 'draft');
      $out[$g][] = $row($s, null);
    }
    usort($out['published'], fn($a, $b) => strcmp((string)$b['date'], (string)$a['date']));
    // News goes stale: unpublished side stories with a "happened on" date sort newest story first.
    foreach (['ready', 'draft'] as $g) usort($out[$g], fn($a, $b) => strcmp($b['happened'], $a['happened']));
  }
  return $out;
}

// One row of the list: number or type tag, title, status, and the buttons.
function dtm_board_row(array $r): string {
  $s = $r['script']; $e = $r['episode'];
  $tag = $e ? 'EP ' . str_pad((string)$e['number'], 2, '0', STR_PAD_LEFT) : ($r['kind'] === 'episode' ? 'NO #' : strtoupper(DTM_SCRIPT_KINDS[$r['kind']] ?? 'Side'));
  $o = '<li><span class="ep bang' . ($e ? '' : ' ep-side') . '">' . h($tag) . '</span>';
  $o .= '<span class="ttl">' . h($r['title']) . ($r['subtitle'] !== '' ? '<em class="sub">' . h($r['subtitle']) . '</em>' : '');
  $o .= '<em class="meta"><span class="status">' . h($r['status']) . '</span>' . ($r['date'] !== '' ? ' Out ' . h(date('M j, Y', strtotime($r['date']))) : '');
  if ($r['happened'] !== '') {
    $days = (int)floor((strtotime(date('Y-m-d')) - strtotime($r['happened'])) / 86400);
    $age = $days <= 0 ? 'today' : ($days === 1 ? 'yesterday' : "$days days ago");
    $stale = $r['kind'] === 'week' && $r['date'] === '' && $days > 7;
    $o .= ' <span class="happened' . ($stale ? ' stale' : ($r['kind'] === 'week' && $r['date'] === '' && $days > 4 ? ' aging' : '')) . '">Happened ' . h(date('M j', strtotime($r['happened']))) . ' (' . h($age) . ')' . ($stale ? ': getting stale' : '') . '</span>';
  }
  $o .= '</em></span>';
  $o .= '<span class="acts">';
  if ($s) {
    $sid = urlencode($s['id']);
    $o .= '<a class="pull-btn bang link-btn" href="prompter.php?id=' . h($sid) . '">Prompter</a>';
    $o .= '<a class="pull-btn bang link-btn" href="images.php?id=' . h($sid) . '">Images</a>';
    $o .= '<a class="pull-btn bang link-btn" href="edit.php?' . ($r['edit'] !== '' ? 'id=' . h($r['edit']) : 'script=' . h($sid)) . '">Transcript</a>';
  }
  $o .= '<span class="more">';
  if ($e) $o .= '<a href="episodes.php?edit=' . h(urlencode($e['id'])) . '">Details</a>';
  if ($s) $o .= '<a href="scripts.php?edit=' . h(urlencode($s['id'])) . '">Edit script</a>';
  if ($r['youtube'] !== '') $o .= '<a href="' . h($r['youtube']) . '" target="_blank" rel="noopener">YouTube</a>';
  return $o . '</span></span></li>';
}

function dtm_board_html(array $groups, array $empty): string {
  $o = '';
  foreach (DTM_BOARD_GROUPS as $k => $label) {
    $o .= '<h2 class="bang saved-h board-h">' . h($label) . ' <span class="small">(' . count($groups[$k]) . ')</span></h2>';
    if (!$groups[$k]) { $o .= '<p class="small">' . h($empty[$k]) . '</p>'; continue; }
    $o .= '<ul class="script-list board">' . implode('', array_map('dtm_board_row', $groups[$k])) . '</ul>';
  }
  return $o;
}
