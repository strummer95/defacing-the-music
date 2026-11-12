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
  'admin_emails' => ['dillon@example.com'],   // Google accounts allowed into the admin (Google sign-in needs https)
  'site_url' => 'http://defacingthemusic.com', // used in password-reset emails; switch to https once SSL is on
];
```

## Admin (`/admin/`)
- First visit: `/admin/setup.php` creates the first admin (name, email, password). It asks for the `deploy_key` as proof you own the server, and locks itself once an admin exists.
- Sign in with email + password (works over plain http). The Google button appears by itself once the site is on https and `google_client_id` is set; only `admin_emails` get in.
- **Pull latest** (top right) copies GitHub `main` onto the server. The deploy key stays on the server.
- 5 wrong tries per IP locks login for 15 minutes. Forgot password emails a one-hour reset link (PHP `mail()`).
- Admin accounts, throttle, deploy log and Guest List live in `data/` (blocked from the web, never overwritten by a pull).
