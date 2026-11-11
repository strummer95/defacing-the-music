# defacing-the-music

Website for the Defacing The Music podcast, https://defacingthemusic.com (IONOS webspace, `dtm` folder).

- Plain PHP, no build. `main` is what's live after "Pull latest".
- `pull.php` copies `main` onto the server. It holds no secret; it reads `../dtm-secrets.php` (server only).
- Claude's work queue: `TASKS.md`. Notes for Claude: `CLAUDE.md`. Board for talking to Claude: `BOARD.md`. Builder: the hourly Claude runner in strummer95/bridge (`dtm/TASKS.md`).

## Server secrets file (`../dtm-secrets.php`, never in git)
```php
<?php return [
  'deploy_key' => 'long-random-string',
  'google_client_id' => '189196968664-684fp4dk94n94hq26ovccm65450457b1.apps.googleusercontent.com',
  'admin_emails' => ['dillon@example.com'],
];
```
