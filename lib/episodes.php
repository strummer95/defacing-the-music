<?php
// Episode storage for Defacing The Music: data/episodes.json (flock-locked, see dtm_json_update in auth.php).
// Covers go to uploads/episodes/ (never overwritten by pull.php, publicly servable).
require_once __DIR__ . '/auth.php';

const DTM_COVER_MAX_BYTES = 5 * 1024 * 1024;
const DTM_COVER_TYPES = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];

// Seeded once, the first time data/episodes.json is read and the file doesn't exist yet, so the three
// episodes already approved in CLAUDE.md/index.php don't vanish from the live site after this deploys.
function dtm_episode_seed(): array {
  return [
    ['id' => 'seed-01', 'number' => '01', 'title' => 'Due To Excessive Flash Powder', 'slug' => 'due-to-excessive-flash-powder',
     'date' => '1967-09-15', 'subject' => 'Keith Moon',
     'summary' => "Live TV, 1967. Keith Moon's bass drum gets loaded with way more flash powder than any human needed. Pete Townshend's hair catches fire, and his ears never forgive anybody.",
     'embed' => '', 'cover' => ''],
    ['id' => 'seed-02', 'number' => '02', 'title' => 'Due To The Venue Being Ripped Apart', 'slug' => 'due-to-the-venue-being-ripped-apart',
     'date' => '1999-07-25', 'subject' => 'Fred Durst',
     'summary' => '200,000 people, an old Air Force base, four dollar water, and a nu-metal band that did not calm anybody down.',
     'embed' => '', 'cover' => ''],
    ['id' => 'seed-03', 'number' => '03', 'title' => 'Due To a Possible Rabies Infection', 'slug' => 'due-to-a-possible-rabies-infection',
     'date' => '1982-01-20', 'subject' => 'Ozzy Osbourne',
     'summary' => "A kid from Des Moines throws a bat on stage. Ozzy assumes it's rubber. It is not rubber.",
     'embed' => '', 'cover' => ''],
  ];
}

function dtm_slugify(string $s): string {
  $s = strtolower(trim($s));
  $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
  $s = trim($s, '-');
  return substr($s, 0, 80);
}

// All episodes, oldest-numbered first. Seeds data/episodes.json on first call if it's never been written.
function dtm_episodes(): array {
  $path = dtm_data_dir() . '/episodes.json';
  if (!is_file($path)) {
    dtm_json_update('episodes.json', fn($cur) => $cur ?: dtm_episode_seed());
  }
  $rows = dtm_json_read('episodes.json');
  usort($rows, fn($a, $b) => [(float)($a['number'] ?? 0), $a['date'] ?? ''] <=> [(float)($b['number'] ?? 0), $b['date'] ?? '']);
  return $rows;
}

function dtm_episode_by_id(string $id): ?array {
  foreach (dtm_episodes() as $e) if ($e['id'] === $id) return $e;
  return null;
}

function dtm_episode_by_slug(string $slug): ?array {
  foreach (dtm_episodes() as $e) if ($e['slug'] === $slug) return $e;
  return null;
}

function dtm_episode_unique_slug(string $base, string $exceptId): string {
  $slug = $base !== '' ? $base : 'episode';
  $rows = dtm_episodes();
  $taken = fn($s) => array_filter($rows, fn($e) => $e['slug'] === $s && $e['id'] !== $exceptId);
  if (!$taken($slug)) return $slug;
  for ($i = 2; $i < 1000; $i++) {
    $try = substr($slug, 0, 76) . '-' . $i;
    if (!$taken($try)) return $try;
  }
  return $slug . '-' . bin2hex(random_bytes(3));
}

// Validates and saves an episode from admin POST fields. Pass $id to update, null to create.
// Returns [ok, error-or-null, episode-or-null].
function dtm_save_episode(array $in, ?string $id): array {
  $title = trim((string)($in['title'] ?? ''));
  $number = trim((string)($in['number'] ?? ''));
  $date = trim((string)($in['date'] ?? ''));
  $subject = trim((string)($in['subject'] ?? ''));
  $summary = trim((string)($in['summary'] ?? ''));
  $embed = trim((string)($in['embed'] ?? ''));
  $slugIn = dtm_slugify((string)($in['slug'] ?? ''));

  if ($title === '' || strlen($title) > 200) return [false, 'Title is required (up to 200 characters).', null];
  if ($number === '' || strlen($number) > 10) return [false, 'Episode number is required.', null];
  $d = DateTime::createFromFormat('Y-m-d', $date);
  if (!$d || $d->format('Y-m-d') !== $date) return [false, 'Date must be a valid date.', null];
  if ($summary === '' || strlen($summary) > 600) return [false, 'Summary is required (up to 600 characters).', null];
  if (strlen($subject) > 120) return [false, 'Story subject is too long.', null];
  if ($embed !== '') {
    if (strlen($embed) > 500 || !filter_var($embed, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $embed)) {
      return [false, 'Embed URL must be a valid http(s) link.', null];
    }
  }

  $slugBase = $slugIn !== '' ? $slugIn : dtm_slugify($title);
  $slug = dtm_episode_unique_slug($slugBase, $id ?? '');

  $cover = null; // null = leave whatever's already stored untouched
  if (!empty($in['remove_cover'])) $cover = '';
  if (!empty($_FILES['cover']['tmp_name']) && is_uploaded_file($_FILES['cover']['tmp_name'])) {
    [$ok, $errOrPath] = dtm_save_cover($_FILES['cover']);
    if (!$ok) return [false, $errOrPath, null];
    $cover = $errOrPath;
  }

  $eid = $id ?? bin2hex(random_bytes(6));
  $rows = dtm_json_update('episodes.json', function ($rows) use ($eid, $number, $title, $slug, $date, $subject, $summary, $embed, $cover) {
    $found = false;
    foreach ($rows as &$r) {
      if ($r['id'] === $eid) {
        $r = ['id' => $eid, 'number' => $number, 'title' => $title, 'slug' => $slug, 'date' => $date,
              'subject' => $subject, 'summary' => $summary, 'embed' => $embed,
              'cover' => $cover === null ? ($r['cover'] ?? '') : $cover];
        $found = true;
      }
    }
    unset($r);
    if (!$found) {
      $rows[] = ['id' => $eid, 'number' => $number, 'title' => $title, 'slug' => $slug, 'date' => $date,
                 'subject' => $subject, 'summary' => $summary, 'embed' => $embed, 'cover' => $cover ?? ''];
    }
    return $rows;
  });
  foreach ($rows as $r) if ($r['id'] === $eid) return [true, null, $r];
  return [false, 'Could not save the episode.', null];
}

function dtm_delete_episode(string $id): bool {
  $existing = dtm_episode_by_id($id);
  dtm_json_update('episodes.json', function ($rows) use ($id) {
    return array_values(array_filter($rows, fn($r) => $r['id'] !== $id));
  });
  if ($existing && !empty($existing['cover'])) {
    $f = DTM_ROOT . '/' . $existing['cover'];
    if (is_file($f) && str_starts_with(realpath($f) ?: '', realpath(DTM_ROOT . '/uploads/episodes') ?: "\0")) @unlink($f);
  }
  return true;
}

// Validates an uploaded cover image (real image bytes, not just the claimed mime/extension) and stores it
// under uploads/episodes/ with a generated name. Returns [ok, relative-path-or-error].
function dtm_save_cover(array $file): array {
  if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return [false, 'Cover image upload failed.'];
  if (($file['size'] ?? 0) > DTM_COVER_MAX_BYTES) return [false, 'Cover image must be under 5MB.'];
  $info = @getimagesize($file['tmp_name']);
  if (!$info || !isset(DTM_COVER_TYPES[$info[2]])) return [false, 'Cover image must be a JPEG, PNG or WEBP.'];
  $ext = DTM_COVER_TYPES[$info[2]];
  $dir = DTM_ROOT . '/uploads/episodes';
  if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return [false, 'Could not create the uploads folder.'];
  if (!is_file("$dir/.htaccess")) @file_put_contents("$dir/.htaccess", "<FilesMatch \"\\.ph(p|tml|p\\d)\$\">\nRequire all denied\n</FilesMatch>\n");
  $name = bin2hex(random_bytes(8)) . '.' . $ext;
  if (!@move_uploaded_file($file['tmp_name'], "$dir/$name")) return [false, 'Could not save the cover image.'];
  return [true, "uploads/episodes/$name"];
}
