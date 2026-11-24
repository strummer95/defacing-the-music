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

## [x] DTM-IMAGES-1: Cutaway images, public Sources & Credits pages, description line (BOARD note, Nov 14)
Done: admin Images page per script (Commons pictures, commercial-safe licenses, cached by the server), `/ep/N` public pages with
sources + image credits, copy-ready description line in admin > Episodes. PR: https://github.com/strummer95/defacing-the-music/pull/45

## [x] DTM-VOICE-1114: Duck's Nov 14 evening voice session (BOARD note)
Done: rules into CLAUDE.md/STORIES.md, Ozzy/Woodstock/Disco/Jerry Lee reworked, every cold open ~75 words, decision chains and crutch cleanup across the library, seed upgrade hashes. Questions for Duck under "For Dillon". PR: https://github.com/strummer95/defacing-the-music/pull/47

## [x] DTM-SUBSCRIBERS-1: Guest List in the admin
Admin page listing `data/subscribers.json` (count, newest first) with a CSV download and a remove button (CSRF protected).
Done (Nov 23): admin > Guest List (list, CSV, remove) plus emailing the list through Duck's Gmail (app password saved into ../dtm-secrets.php), batched sends, unsubscribe links.

## [x] DTM-STORIES-1: Story idea backlog in the admin
Superseded, not built separately (bridge run, 2026-11-24): this task is from the repo's very first commit
(Nov 11), before admin > Scripts/Episodes/Side Stories existed. That system, built out since under
DTM-EPISODES-1 and Duck's Nov 23 admin-layout notes, now covers everything this task asked for and more:
every script already carries a title, subtitle (the one-line hook), and a status of idea/draft/ready/
recorded/published (`DTM_SCRIPT_STATUSES` in `lib/scripts.php`), plus a `kind` (main episode or a side
story), a `story_date`, and per-shipped-script sources (`lib/sources/<seed>.json`). `STORIES.md` is the
plain-text idea backlog Duck and Claude already maintain by hand, with a table of story / rating / notes.
Checked every seed story this task named against the current repo rather than assuming: all 8 are already
tracked. Limp Bizkit/Woodstock '99, Keith Moon/Smothers Brothers, Ozzy/Des Moines, the Montreux casino
fire, GG Allin, and Metallica/GN'R Montreal are all full "Ready" scripts in `data/scripts.json` (episode-01,
episode-02, episode-03, story-montreux-fire, story-gg-allin, story-metallica-gnr-montreal) with their own
sources files. Mayhem (Norwegian black metal) and Bjork's 1996 stalker case are both already listed in
`STORIES.md`'s "Darker list" table with Duck's rating. A dedicated admin page with separate
title/artist/year fields would just duplicate the richer system that already exists and fork the backlog
across two places — not built. Doc-only change (this note), no functional code touched.
