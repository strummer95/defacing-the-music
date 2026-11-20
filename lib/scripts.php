<?php
// Episode scripts for the teleprompter. Stored in data/scripts.json (flock-locked, never overwritten by a pull).
require_once __DIR__ . '/auth.php';

// Scripts that ship with the site (lib/seed/*.txt). Each is added once: if it was already added
// (even if Duck deleted or edited it since) or a script with that episode number exists, it's skipped.
const DTM_SEEDS = [
  'episode-00' => ['0', 'Due To Unforeseen Circumstances', 'Who Gave This Guy a Mic?'],
  'episode-01' => ['1', 'Due To Excessive Flash Powder', 'Who Gave The Who Explosives?'],
  'episode-02' => ['2', 'Due To Not Giving Peace a Chance', 'Who Asked Fred Durst to Be a Therapist?'],
  'episode-03' => ['3', 'Due To a Possible Rabies Infection', 'Who Let That Guy Bring a Bat?'],
  // Library scripts: no episode number until Duck makes them an episode.
  'story-disco-demolition'      => ['', 'Due To Excessive Disco', 'Who Let a DJ Blow Up Center Field?'],
  'story-dmb-bus'               => ['', 'Due To a Septic Tank Over the River', 'Who Let the Bus Driver Find the Bridge?'],
  'story-fyre-festival'         => ['', 'Due To a Cheese Sandwich', 'Who Sold Them Paradise?'],
  'story-milli-vanilli'         => ['', 'Due To a Skipping Track', 'Who Let the Tape Run?'],
  'story-sex-pistols-grundy'    => ['', 'Due To Language', 'Who Dared the Sex Pistols?'],
  'story-jerry-lee-graceland'   => ['', 'Due To Great Balls of Firearms', 'Who Let Jerry Lee Back to Graceland?'],
  'story-montreux-fire'         => ['', 'Due To a Flare Gun Indoors', 'Who Brought a Flare Gun to a Zappa Show?'],
  'story-metallica-gnr-montreal'=> ['', 'Due To Pyro and a Sore Throat', 'Who Stood James Hetfield on the Flamethrower?'],
  'story-gg-allin'              => ['', 'Due To Everything', 'Who Kept Booking GG Allin?'],
];

// sha1 of earlier shipped versions of each seed. If a script on the server still matches one of these
// exactly (Duck never edited it), it gets upgraded to the current seed text. Edited scripts are never touched.
const DTM_SEED_OLD = [
  'episode-00' => ['0a3c62561acfefa9b47142542b2566c6c40aaa2a', '7232a021773d9aa2a230518fa393f16447343a59', 'd61ecbce297a5442a2a42112b2a583f61d133e1b', '9fb19500807901e9cf1d32c3ebff9918e42ad46c', 'e40daceeba30539686c8fbe484a7c645b2e688fb'],
  'episode-01' => ['0ad617e1ddbffcc280e0a6c9760e8268d0fe2717', '7b3dbd18cefdbf8b0f5b173c5b673be91be4aeee', '305bfaa521a7e92ea540da27d1e71e68b29afc18', '4c534b033ad2e436832c01d045a51a5a74f49e1f', '6b7e320eed0fe6ac0402a18be46009af1e8e1442', 'fa7f326e9b310ee92d5a88bb9c4772ea4ac5d313', '0c7dc51dce8b66aad0ddd26579234aa52e38ccd7', '91ea8b21657d392a16d1ab471d2625abacba505c', '2f5511ac3b078a4619f203d1928f2c566149ebbd', 'adfd9d5a2a7b8580ef155ee7a85e6bdd525eab67'],
  'episode-02' => ['9699dcc012bd232b28aec8f657c1482d538d61f5', '687fbe72cc2458ddad679552cf3aa2290c9f9e48', 'b76c280a63359aa13c883bc417f09f6adddab2c5', '6cd32e7d91cb8997871fa61fe41049777ec0633b', '753f5b89fd82ccc4eabdfa52a5f06e4ff26b92f9', '3ac5d60d055afac5ed18ffb2f767e0875e240f08', '8959ceeb759ab1b8783254ff339726735f07546d', '7a89853673f1480bc1808326f4e584396607f523', '2e7ac65438c3d6c86fe5fb8ae2ade3cc1c87ce4c', '447f1afc31457162070dbda3cbf53a9e5479bb99', 'b2d5e48ee734a3100bfefdb58bba16c605d14971', '940d2899c4cfbf8e05619caf278fc63e7349e041', '1a1cc66ea5721695bd21f501d3bb17d97dfb4f59', '5b0961c8bef94983a55600990b7b1b6c0b253556', '85319a50de877ee834acd790034855c3afefe3bf', '742e4da6dd6c55d1ea3dab3ca99e3764c8b4852b', '0c5f40fefe115df3c103a98dbe2eaa2d74119e43', 'a572618a70a64e6939410843e521578e0a894262', '6d3373d80a0a4ec97dba3167e3831184de2a0dfe', '451a3835f1044e8dc955dcdeffa5d73650319413'],
  'episode-03' => ['a95c81044af6ccae27de7c120ab6ccbf6f91c375', 'c4f5e7c8a43992869c40ad4fae986b8143646781', 'c02bf5dcf4b3383c30fad282fe5470e99eec82f1', '49c09096b60f68d41f99caf79d6547a3d0d968c2', '5d356b15f96b61936f8fda8b601284b2f4dafc4e', '14e7d40dc153a8f1521d67111baebb1d61af5b53', '8b5f17270f933b7cf89deaa61943ff90fe1bf8ef', '2f2852fde320f56711139f4bc1829026736f6d74', 'ebfe75b0f7632178d048e303a4ee16b802eef276', '13aae67f970a3671b0e0ca56850d8dd953f53d95', '79daf04ce6eda2b843d49dbcefd387efe4ccf523', 'e234d0aae612747824b57da95529b19bf6b70743', 'e910aa0f16b0c5be0a412c6ae78f5843013dc0f2', '8a33fb9ec3d6733dd1adea71bb825c607f76f3c7', 'fd596e0a5457f75e4a9e6e472d452e3d4c560c83', 'c0e47664159b27ce034606e02cc452fd1e48c0ae', '06f3d5c670b7b74c4a4a9f2876a40cfb1821553d'],
  'story-disco-demolition' => ['729fae1af1b9f6c002f0fa941b70633784660f2d', 'e982882cd6f38b653862f6af2ec02d40b924c16d', '4d2027e67c2217fe71be9535c09c3c08d3e89f80', '70f8d0a76bf402fe83f3a1bed8bf44eb304ae7d8', '92dcaec97507f05e2b60519cca40416ecb7754c0', 'a5b697ea2511b5e3a9f983892e3ca2f544c8dd9b', 'f79782385ab397529c03ab85fc555df0d3a3ada1', '23efa643d8897dc451879aedf55a2bd61d7ffefd'],
  'story-dmb-bus' => ['b688ec518f0b47630373d1373f73ec663a3fa1b1', '95b8bed82284c7992a91680dcf1bf22d935e3f79', '0772952fa1b1ea245a6f60c6cf36eec9a92110ce', 'cf8600e2cffa7d404e995fffc47a189d2bd5e7e3'],
  'story-fyre-festival' => ['2a104530ee95bc40c824b8fa646b6d07fd332ce6', 'd6d84255abf2bdd50c7419c2e7681c05d5cd4f12', '2adb1165cbd085e79ab9b68439466f342cfafd24', 'd38c4d727776020cccfe682ead2417fe2dbbf362'],
  'story-gg-allin' => ['4c037d575f180c786c9ba74e76abd5b7a9cf82ff', '08d68fc6e643fa0a31d7204e9853d0e31a1daa14', 'c6d78629c027f7367de686490b60db2c3ba8622c', '90265d8390cf7486ab2454cfafec27842d547765'],
  'story-jerry-lee-graceland' => ['c529afbd373824cb730f0ad76505b7cd6851bf9a', 'c882b610870cd66067ab08db6e193e8f3b2a4bc0', 'ecad2fd6ef45f1e1192b76897e08f72104d8154a', '1b2c151a998f05b7e7efbbfbee64bf7c1b659415', 'f6de35ccd95827299cbbcee7387c994cadb9bb50'],
  'story-metallica-gnr-montreal' => ['3ca77605ef5cc95a6b854130d6efa2422797e165', 'a7dad0ed695be61f20b2973d8288e7ec8c86cf48', '618a52da21fb82d6f1880b2443a24b90466e65ed', 'df2bf8d04a5effe13d6528ac68fcb6aef09dd107'],
  'story-milli-vanilli' => ['40e13115fb21719ba6100eca5ee905ff4ba62384', '3c2e6640338309ad8ba074c89d160eb2bf08144b', '80426cefcd98d892351591172fdacd4a23d26a78', '3456c1323858db2d7e76d640f2bad87b30e4b92e'],
  'story-montreux-fire' => ['5795a5ca24f88ae52027cb4cb2579ca6ebf50faa', '78f9ce091756b4cc9e7696706ca53b389d320439', '71bf05cb3d1b659c0261af7e7ed05642421432bd', 'ff58b6e50727ed12ec11b233df789e6c0c4cc65b'],
  'story-sex-pistols-grundy' => ['6b05c0ad3511e5f9901dbdfc35bc7cdea9082a7b', '59f6cbf0d89ca110853ff09a843bae56945f9d3e', '7993347285dfc4560bee02198181b5a242952274', '8d5defe843f17f645d9ef7e49a652b35c6582bcf'],
];

// Earlier shipped titles. When an unedited seed is upgraded and its title is still one of these, it gets the new one.
const DTM_SEED_OLD_TITLES = [
  'episode-02' => ['Due To The Venue Being Ripped Apart'],
  'story-jerry-lee-graceland' => ['Due To an Unannounced Visitor'],
];

// Earlier shipped subtitles. When an unedited seed is upgraded and its subtitle is still one of these, it gets the new one.
const DTM_SEED_OLD_SUBTITLES = [
  'story-jerry-lee-graceland' => ['Who Let Jerry Lee Drive to Graceland?'],
];

// Which seed a script row came from. New rows carry 'seed'; older rows are matched by their episode number.
function dtm_row_is_seed(array $s, string $key): bool {
  if (isset($s['seed'])) return $s['seed'] === $key;
  // Rows without a seed tag are only matched by episode number for the original numbered seeds,
  // never for library seeds (number ''), so Duck's own scripts can't be mistaken for a seed.
  $n = DTM_SEEDS[$key][0];
  return $n !== '' && (string)($s['episode'] ?? '') === $n;
}

// A script still counts as unedited if only trailing whitespace changed (saving the admin form can drop the final newline).
function dtm_body_matches(string $body, array $old): bool {
  return in_array(sha1($body), $old, true) || in_array(sha1(rtrim($body) . "\n"), $old, true);
}

function dtm_upgrade_seeds(): void {
  $list = dtm_json_read('scripts.json');
  $need = false;
  foreach ($list as $s) {
    foreach (DTM_SEED_OLD as $key => $old) {
      if (dtm_row_is_seed($s, $key) && dtm_body_matches((string)$s['body'], $old)) { $need = true; break 2; }
    }
  }
  if (!$need) return;
  dtm_json_update('scripts.json', function ($list) {
    foreach ($list as &$s) {
      foreach (DTM_SEED_OLD as $key => $old) {
        if (!dtm_row_is_seed($s, $key) || !dtm_body_matches((string)$s['body'], $old)) continue;
        $body = (string)@file_get_contents(__DIR__ . "/seed/$key.txt");
        if ($body !== '') { $s['body'] = $body; $s['updated'] = date('c'); }
        if (in_array((string)($s['subtitle'] ?? ''), DTM_SEED_OLD_SUBTITLES[$key] ?? [], true)) $s['subtitle'] = DTM_SEEDS[$key][2];
        if (in_array((string)($s['title'] ?? ''), DTM_SEED_OLD_TITLES[$key] ?? [], true)) $s['title'] = DTM_SEEDS[$key][1];
      }
    }
    return $list;
  });
}

// Seeded episodes that predate subtitles get theirs once. A subtitle Duck set (even blank) is kept.
function dtm_seed_subtitles(): void {
  $sub = function (array $s): ?string {
    foreach (DTM_SEEDS as $key => $row) if (dtm_row_is_seed($s, $key)) return $row[2];
    return null;
  };
  $need = false;
  foreach (dtm_json_read('scripts.json') as $s) if (!array_key_exists('subtitle', $s) && $sub($s) !== null) { $need = true; break; }
  if (!$need) return;
  dtm_json_update('scripts.json', function ($list) use ($sub) {
    foreach ($list as &$s) if (!array_key_exists('subtitle', $s) && ($t = $sub($s)) !== null) $s['subtitle'] = $t;
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
    foreach ($todo as $key) {
      [$ep, $title, $subtitle] = DTM_SEEDS[$key];
      foreach ($list as $s) if (dtm_row_is_seed($s, $key)) continue 2;
      $body = (string)@file_get_contents(__DIR__ . "/seed/$key.txt");
      if ($body === '') continue;
      // New scripts land in the library (no episode number); Duck decides when they become episodes.
      $list[] = ['id' => bin2hex(random_bytes(6)), 'seed' => $key, 'episode' => '', 'status' => 'ready', 'title' => $title, 'subtitle' => $subtitle, 'body' => $body, 'updated' => date('c')];
    }
    return $list;
  });
  @file_put_contents($marker, json_encode(array_values(array_unique(array_merge($done, $todo)))));
}

const DTM_SCRIPT_STATUSES = ['idea' => 'Idea', 'draft' => 'Draft', 'ready' => 'Ready', 'recorded' => 'Recorded'];

// One-time move to "scripts library + episodes": tags seeded rows with their seed key, gives every script a
// status, keeps Episode 0 as an episode, and returns the other seeded scripts to the library (no number).
function dtm_scripts_migrate_v2(): void {
  $marker = dtm_data_dir() . '/scripts.v2';
  if (is_file($marker)) return;
  dtm_json_update('scripts.json', function ($list) {
    foreach ($list as &$s) {
      if (!isset($s['seed'])) foreach (DTM_SEEDS as $key => $row) if ($row[0] !== '' && (string)($s['episode'] ?? '') === $row[0]) { $s['seed'] = $key; break; }
      $ep = (string)($s['episode'] ?? '');
      if (!isset($s['status'])) $s['status'] = $ep === '0' ? 'recorded' : 'ready';
      if (isset($s['seed']) && $s['seed'] !== 'episode-00') $s['episode'] = '';
    }
    return $list;
  });
  @file_put_contents($marker, date('c'));
}

function dtm_scripts(): array {
  dtm_seed_scripts();
  dtm_scripts_migrate_v2();
  $list = dtm_json_read('scripts.json');
  foreach ($list as &$s) { $s += ['episode' => '', 'status' => 'draft', 'subtitle' => '']; }
  usort($list, function ($a, $b) {
    $ea = (string)$a['episode']; $eb = (string)$b['episode'];
    if ($ea !== '' && $eb !== '') return strnatcmp($ea, $eb);
    if ($ea !== $eb) return $ea === '' ? 1 : -1;
    return strcmp((string)($b['updated'] ?? ''), (string)($a['updated'] ?? ''));
  });
  return $list;
}

function dtm_script(string $id): ?array {
  foreach (dtm_scripts() as $s) if (hash_equals($s['id'], $id)) return $s;
  return null;
}

// Scripts say "Episode [NUMBER]." after the theme. Once the script is on an episode, the reader shows the real number;
// until then [NUMBER] stays as a yellow cue.
function dtm_fill_episode_number(string $body, string $episode): string {
  return $episode === '' ? $body : str_replace('[NUMBER]', $episode, $body);
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
