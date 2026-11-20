<?php
// Guest List: emails collected by signup.php into data/subscribers.json ({email, at}, flock-locked).
require_once __DIR__ . '/auth.php';

function dtm_subscribers(): array {
  $list = dtm_json_read('subscribers.json');
  usort($list, fn($a, $b) => strcmp((string)($b['at'] ?? ''), (string)($a['at'] ?? '')));
  return $list;
}

function dtm_remove_subscriber(string $email): void {
  $email = strtolower(trim($email));
  dtm_json_update('subscribers.json', fn($l) => array_values(array_filter($l, fn($r) => strtolower((string)($r['email'] ?? '')) !== $email)));
}
