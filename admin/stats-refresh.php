<?php
// Background fetch for admin > Stats (Nov 28). The Stats page calls this after it has already shown the saved numbers,
// so opening Stats never waits on YouTube, Meta and TikTok. Releases the session first so other admin pages stay fast.
require __DIR__ . '/../lib/analytics.php';
$user = dtm_require_admin();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok' => false]); exit; }
dtm_check_csrf();
session_write_close();
ignore_user_abort(true);
$before = (int)(dtm_an_state()['fetched'] ?? 0);
$errors = dtm_an_refresh(false);
try { require_once __DIR__ . '/../lib/episodes.php'; dtm_episodes_autopublish(); } catch (Throwable $t) {}
$after = (int)(dtm_an_state()['fetched'] ?? 0);
echo json_encode(['ok' => true, 'fetched' => $after > $before, 'problems' => array_keys($errors)]), "\n";
