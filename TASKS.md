# Defacing The Music tasks

One task per run, top to bottom. Mark done `[x]` with a one-line note and PR link.

## [x] DTM-ADMIN-1: Admin area with a "Pull latest" button that works over plain HTTP
Done: `/admin/` with first-run setup (deploy key as proof), password login with per-IP throttle, forgot-password email,
Google sign-in that turns on by itself under https, account page with sign-out, and a CSRF-protected Pull latest button
(shared deploy code in `lib/deploy.php`; `pull.php` still works with the key). PR: https://github.com/strummer95/defacing-the-music/pull/2

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

## [ ] DTM-SUBSCRIBERS-1: Guest List in the admin
Admin page listing `data/subscribers.json` (count, newest first) with a CSV download and a remove button (CSRF protected).

## [ ] DTM-STORIES-1: Story idea backlog in the admin
Admin-only page to log candidate stories (title, artist, year, one-line hook, source links, status idea/researching/recorded).
Seed it with: Limp Bizkit and the Woodstock '99 riot; Keith Moon blowing up his drum kit on The Smothers Brothers Comedy Hour;
Ozzy and the bat in Des Moines (1982); Mayhem (Norwegian black metal); the Bjork stalker case; the Montreux casino fire ("Smoke on the Water");
GG Allin; Metallica/Guns N' Roses in Montreal 1992.
