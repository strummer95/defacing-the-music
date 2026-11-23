<?php
// Episode editing projects (admin > Edit). Duck drops in the transcripts the editing session on his PC made,
// marks lines CUT / COLD (cold open) / CLIP right on the site, and the editor pulls the marked transcript
// back through a private link to build the Premiere timeline.
// Stored in data/edits.json: {"projects": {id: project}}. A project:
//   {id, name, created, token, parts: [{name, file, audio (upload id), lines: [{i, t, ts, who, text, flag}]}],
//    marks: {"<part>:<line>": {"m": "cut"|"cold"|"trim", "clip": "name", "note": "...", "words": [cut word indexes]}}}
//    (clip is separate: a line can be both; words = single words cut inside a kept line)
require_once __DIR__ . '/uploads.php';
require_once __DIR__ . '/scripts.php';

const DTM_EDIT_MARKS = ['cut', 'cold', 'clip', 'trim']; // trim = shorten (a pause): keep a natural beat, don't remove it

// The words of a line, the same way the page splits them (word cuts are stored as indexes into this).
function dtm_edit_words(string $text): array { return array_values(array_filter(preg_split('/\s+/u', trim($text)), 'strlen')); }

// One transcript line: "[12:34] DUCK: text", "[29:01] DUCK [?]: text" (unsure speaker), "[1:02:03.5] [?]: text".
function dtm_edit_line_match(string $l): ?array {
  if (!preg_match('/^\[(\d{1,3}(?::\d{1,2}){1,2}(?:[.,]\d+)?)\]\s*([A-Za-z][A-Za-z ]{0,20}?)?\s*(\[\?\])?\s*:\s*(.*)$/u', $l, $m)) return null;
  $who = strtoupper(trim($m[2] ?? ''));
  if ($who === '' && ($m[3] ?? '') === '') return null;
  if (($m[3] ?? '') !== '') $who .= '?';
  $t = 0.0; foreach (array_map('floatval', explode(':', str_replace(',', '.', $m[1]))) as $x) $t = $t * 60 + $x;
  return ['t' => $t, 'ts' => $m[1], 'who' => $who, 'text' => $m[4]];
}

// Lines imported before "[?]" speakers were understood were stored as plain text. Read them as timestamped
// lines now, keeping each line's index (marks are keyed by it) and shifting any word cuts past the prefix.
function dtm_edit_normalize(?array $p): ?array {
  if (!$p) return $p;
  foreach ($p['parts'] ?? [] as $pi => $part) foreach ($part['lines'] as $li => $ln) {
    if (($ln['ts'] ?? '') !== '' || !empty($ln['flag'])) continue;
    $hit = dtm_edit_line_match(trim((string)$ln['text']));
    if (!$hit) continue;
    $shift = count(dtm_edit_words((string)$ln['text'])) - count(dtm_edit_words($hit['text']));
    $p['parts'][$pi]['lines'][$li] = ['t' => $hit['t'], 'ts' => $hit['ts'], 'who' => $hit['who'], 'text' => $hit['text'], 'flag' => false, 'i' => $ln['i']];
    $k = "$pi:{$ln['i']}";
    if (!empty($p['marks'][$k]['words'])) {
      $w = array_values(array_filter(array_map(fn($x) => $x - $shift, $p['marks'][$k]['words']), fn($x) => $x >= 0));
      if ($w) $p['marks'][$k]['words'] = $w; else unset($p['marks'][$k]['words']);
    }
  }
  return $p;
}

function dtm_edits(): array { return array_map('dtm_edit_normalize', dtm_json_read('edits.json')['projects'] ?? []); }
// The transcript project hooked to a script (admin > Episodes / Side Stories > Transcript), or null.
function dtm_edit_for_script(string $scriptId): ?array {
  if ($scriptId === '') return null;
  foreach (dtm_edits() as $p) if (($p['script_id'] ?? '') === $scriptId) return $p;
  return null;
}
function dtm_edit(string $id): ?array { $p = dtm_edits()[$id] ?? null; return is_array($p) ? $p : null; }
function dtm_edit_update(string $id, callable $fn): ?array {
  $out = null;
  dtm_json_update('edits.json', function ($all) use ($id, $fn, &$out) {
    $all['projects'] = $all['projects'] ?? [];
    $p = dtm_edit_normalize($all['projects'][$id] ?? null);
    $p = $fn($p);
    if ($p === null) unset($all['projects'][$id]); else $all['projects'][$id] = $p;
    $out = $p;
    return $all;
  });
  return $out;
}

// "[12:34] DUCK: text" / "[1:02:03.5] PAT: text"; ">> PAUSE 4.2s" style flag lines keep the time of the line before.
function dtm_edit_parse(string $text): array {
  $lines = []; $last = 0.0;
  foreach (preg_split('/\R/', $text) as $raw) {
    $l = trim($raw);
    if ($l === '' || $l[0] === '#') continue;
    if ($hit = dtm_edit_line_match($l)) {
      $last = $hit['t'];
      $lines[] = $hit + ['flag' => false];
    } elseif (str_starts_with($l, '>>')) {
      $lines[] = ['t' => $last, 'ts' => '', 'who' => '', 'text' => trim(substr($l, 2)), 'flag' => true];
    } else {
      $lines[] = ['t' => $last, 'ts' => '', 'who' => '', 'text' => $l, 'flag' => false];
    }
  }
  foreach ($lines as $i => &$x) $x['i'] = $i;
  return $lines;
}

// The marked transcript in the format the editing session expects (docs/EDITING-KICKOFF.md):
// CUT / COLD line prefixes, CLIP <name> ... CLIP END around clips, notes in (( )).
function dtm_edit_export(array $p): string {
  $out = ['# ' . $p['name'] . ' (marked on defacingthemusic.com, ' . date('Y-m-d H:i') . ')', ''];
  foreach ($p['parts'] as $pi => $part) {
    $out[] = '## PART ' . ($pi + 1) . ': ' . $part['name'];
    $open = null;
    foreach ($part['lines'] as $ln) {
      $mk = $p['marks']["$pi:{$ln['i']}"] ?? null;
      $clip = isset($mk['clip']) ? (string)$mk['clip'] : null;
      if ($open !== null && $clip !== $open) { $out[] = 'CLIP END'; $open = null; }
      if ($clip !== null && $open === null) { $out[] = 'CLIP ' . $clip; $open = $clip; }
      $text = $ln['text'];
      if (!empty($mk['words'])) { // cut words shown as [-...-]; neighbors merged into one bracket
        $ws = dtm_edit_words($text); $cut = array_flip($mk['words']); $o = []; $run = [];
        foreach ($ws as $n => $w) { if (isset($cut[$n])) { $run[] = $w; continue; } if ($run) { $o[] = '[-' . implode(' ', $run) . '-]'; $run = []; } $o[] = $w; }
        if ($run) $o[] = '[-' . implode(' ', $run) . '-]';
        $text = implode(' ', $o);
      }
      $body = $ln['flag'] ? '>> ' . $text : ($ln['ts'] !== '' ? "[{$ln['ts']}] {$ln['who']}: {$text}" : $text);
      $pre = match ($mk['m'] ?? '') { 'cut' => 'CUT ', 'cold' => 'COLD ', 'trim' => 'SHORTEN ', default => '' };
      $note = trim((string)($mk['note'] ?? ''));
      $out[] = $pre . $body . ($note !== '' ? " (( $note ))" : '');
    }
    if ($open !== null) $out[] = 'CLIP END';
    $out[] = '';
  }
  return implode("\n", $out) . "\n";
}

function dtm_edit_counts(array $p): array {
  $c = ['cut' => 0, 'cold' => 0, 'clip' => 0, 'note' => 0, 'lines' => 0, 'words' => 0, 'trim' => 0];
  foreach ($p['parts'] as $part) $c['lines'] += count($part['lines']);
  foreach ($p['marks'] ?? [] as $mk) {
    if (in_array($mk['m'] ?? '', ['cut', 'cold', 'trim'], true)) $c[$mk['m']]++;
    if (isset($mk['clip'])) $c['clip']++;
    if (trim((string)($mk['note'] ?? '')) !== '') $c['note']++;
    $c['words'] += count($mk['words'] ?? []);
  }
  return $c;
}
