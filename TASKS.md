# Defacing The Music tasks

One task per run, top to bottom. Mark done `[x]` with a one-line note and PR link.

## [x] DTM-ADMIN-1: Admin area with a "Pull latest" button that works over plain HTTP
Done: `/admin/` with first-run setup (deploy key as proof), password login with per-IP throttle, forgot-password email,
Google sign-in that turns on by itself under https, account page with sign-out, and a CSRF-protected Pull latest button
(shared deploy code in `lib/deploy.php`; `pull.php` still works with the key). PR: https://github.com/strummer95/defacing-the-music/pull/2

## [x] DTM-SITE-1: Real landing page
Done: Dillon's chosen "Gig Poster" home page ported to `index.php` + `assets/site.css` (mobile layout, OG tags, favicon, scrolling ticker),
episodes show "Coming soon", listen links show "soon" until URLs are filled in at the top of `index.php`,
Guest List form saves to `data/subscribers.json` via `signup.php`. PR: https://github.com/strummer95/defacing-the-music/pull/1

## [x] DTM-EPISODES-1: Episodes managed from the admin
Done: `data/episodes.json` (flock-locked via the existing `dtm_json_update`) with id, number, title, slug (auto from
title, forced unique), date, subject (band/artist), summary, embed URL, cover image. New `admin/episodes.php`
(add/edit/delete, CSRF-protected, slug collisions auto-suffixed, cover uploads validated with `getimagesize`
not just the extension, JPEG/PNG/WEBP only, 5MB cap, stored under `uploads/episodes/` with a `.htaccess`
blocking `.php`/`.phtml` execution there). `index.php`'s `$episodes` array is now `dtm_episodes()`; same
ticket-stub cards (alternating red/yellow shadow), plus an optional cover thumbnail and an "Episode details"
link to the new `episode.php?slug=`. First run seeds `data/episodes.json` with the three episodes that were
previously hardcoded (and are the real ones named in `CLAUDE.md`'s "The show" section), so they don't vanish
from the live site after this deploys — Dillon can edit them from the admin like any other episode from now on.
`php -l` clean on every file. Verified end-to-end against a local `php -S`: seed/list/add/edit/delete, slug
collision gets a `-2` suffix, bad date/bad embed URL/non-image "cover" all rejected with a visible error,
missing CSRF gets a 400, cover file is deleted when its episode is deleted, signed-out access to
`admin/episodes.php` redirects to login. Screenshotted the home page, an episode detail page (with and without
a cover + embed), and the admin page. PR: https://github.com/strummer95/defacing-the-music/pull/4

## [ ] DTM-SUBSCRIBERS-1: Guest List in the admin
Admin page listing `data/subscribers.json` (count, newest first) with a CSV download and a remove button (CSRF protected).

## [ ] DTM-STORIES-1: Story idea backlog in the admin
Admin-only page to log candidate stories (title, artist, year, one-line hook, source links, status idea/researching/recorded).
Seed it with: Limp Bizkit and the Woodstock '99 riot; Keith Moon blowing up his drum kit on The Smothers Brothers Comedy Hour;
Ozzy and the bat in Des Moines (1982); Mayhem (Norwegian black metal); the Bjork stalker case; the Montreux casino fire ("Smoke on the Water");
GG Allin; Metallica/Guns N' Roses in Montreal 1992.
