# Defacing The Music tasks

One task per run, top to bottom. Mark done `[x]` with a one-line note and PR link.

## [x] DTM-ADMIN-1: Admin area with a "Pull latest" button that works over plain HTTP
Done: `/admin/` with first-run setup (deploy key as proof), password login with per-IP throttle, forgot-password email,
Google sign-in that turns on by itself under https, account page with sign-out, and a CSRF-protected Pull latest button
(shared deploy code in `lib/deploy.php`; `pull.php` still works with the key). PR: https://github.com/strummer95/defacing-the-music/pull/2

**2026-11-22, self-picked adversarial security review (no Dillon ask):** gave DTM-ADMIN-1 its first dedicated adversarial
pass (login throttle, Google sign-in, CSRF, password reset, account page, Pull latest). Checked for: unlocked
read-check-write races on the throttle counter, Google-account-linking races, allowlist checks skipped at link time,
`==`/`!=` instead of `hash_equals` on CSRF/reset tokens, and missing `aud`/`exp`/`email_verified` checks on Google ID
tokens. CSRF, reset tokens and Google verification were all already solid (`hash_equals` throughout, `aud`/`iss`/
`email_verified`/`exp` all checked in `dtm_google_verify()`). Found and fixed a real one: `dtm_json_update()` in
`lib/auth.php` (the shared flock-locked JSON read-modify-write every data file goes through, not just the throttle)
held its exclusive lock on the data file itself, but the write path swaps the file in with `rename()`, which points the
filename at a brand-new inode; a lock taken via `flock()` on an fd opened before the rename doesn't bind to whatever
the filename points at later, so any process that opened the same path fresh right after a rename got the new inode
with no contention at all, even while the renaming process was still logically inside its locked section. Proved it
with real concurrent `php` CLI processes hammering an isolated copy of the store: a 60-process "increment a shared
counter by 1" test landed on 40/60 (not 60) before the fix, and a 60-process burst of simulated wrong-password login
attempts against the per-IP throttle (limit 5) let up to 48 of them through before being blocked. Fixed by locking a
stable sidecar `data/<name>.json.lock` file (never renamed) instead of the data file itself, so every caller contends
for the same inode for the whole read-check-write. Same repro after the fix: counter test landed on 60/60 every time
across 5 trials; the throttle burst test topped out at 5-6 allowed across 10 trials of 60 concurrent requests (the
decisive, unbounded bypass is gone; the remaining +/-1 is the much smaller, expected slop from `dtm_throttled()` and
`dtm_fail()` still being two separate calls at the login.php call site, not a reopened version of the same bug).
Verified with `php -l lib/auth.php` (clean) and a local smoke test (`php -S` + curl through setup -> create admin ->
login -> account page -> a wrong-password POST that correctly bumps `data/throttle.json`). PR:
https://github.com/strummer95/defacing-the-music/pull/REPLACE_ME

## [x] DTM-UPLOADS-1: File uploads in the admin
Done: admin > Uploads. Chunked uploads (big video works on shared hosting), progress + retries, files in uploads/media
(blocked from the web), download/stream via admin/file.php for admins or a per-file secret link ("Copy link for Claude").

## [x] DTM-PROMPTER-1: Teleprompter in the admin
Done: Scripts section in the admin (create, edit, delete, seeded with Episode 0) and a black-screen reader with
size +/-, auto-scroll with speed, space to play/pause, full screen, mirror mode, settings remembered per device.

## [x] DTM-SITE-1: Real landing page
Done: Dillon's chosen "Gig Poster" home page ported to `index.php` + `assets/site.css` (mobile layout, OG tags, favicon, scrolling ticker),
episodes show "Coming soon", listen links show "soon" until URLs are filled in at the top of `index.php`,
Guest List form saves to `data/subscribers.json` via `signup.php`. PR: https://github.com/strummer95/defacing-the-music/pull/1

## [x] DTM-EPISODES-1: Episodes + scripts library in the admin
Done (replaces the earlier PR #4, which should not be merged): admin > Scripts is an unnumbered library (status idea/draft/ready/recorded);
"Make it an episode" gives a script the next number. admin > Episodes lists numbered episodes (planned/recorded/published,
publish date, YouTube/Spotify links, notes, linked script). Public episode list on the home page is still hardcoded (next step).

## [x] DTM-IMAGES-1: Cutaway images, public Sources & Credits pages, description line (BOARD note, Nov 14)
Done: admin Images page per script (Commons pictures, commercial-safe licenses, cached by the server), `/ep/N` public pages with
sources + image credits, copy-ready description line in admin > Episodes. PR: https://github.com/strummer95/defacing-the-music/pull/45

## [x] DTM-VOICE-1114: Duck's Nov 14 evening voice session (BOARD note)
Done: rules into CLAUDE.md/STORIES.md, Ozzy/Woodstock/Disco/Jerry Lee reworked, every cold open ~75 words, decision chains and crutch cleanup across the library, seed upgrade hashes. Questions for Duck under "For Dillon". PR: https://github.com/strummer95/defacing-the-music/pull/47

## [ ] DTM-SUBSCRIBERS-1: Guest List in the admin
Admin page listing `data/subscribers.json` (count, newest first) with a CSV download and a remove button (CSRF protected).

## [ ] DTM-STORIES-1: Story idea backlog in the admin
Admin-only page to log candidate stories (title, artist, year, one-line hook, source links, status idea/researching/recorded).
Seed it with: Limp Bizkit and the Woodstock '99 riot; Keith Moon blowing up his drum kit on The Smothers Brothers Comedy Hour;
Ozzy and the bat in Des Moines (1982); Mayhem (Norwegian black metal); the Bjork stalker case; the Montreux casino fire ("Smoke on the Water");
GG Allin; Metallica/Guns N' Roses in Montreal 1992.
