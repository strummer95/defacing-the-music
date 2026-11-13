# Defacing The Music: notes for Claude

Site for **Defacing The Music**, Dillon Johnson's comedy podcast about the craziest stories in music history
(Limp Bizkit and the Woodstock '99 riot, Keith Moon blowing up his drums on the Smothers Brothers, and so on).
Live at http://defacingthemusic.com (NO SSL for now; everything must work over plain http), served from the `dtm` folder on Dillon's IONOS webspace (shared PHP hosting, no database assumed; use JSON files under `data/` with flock locking).

## How work happens
- Start every run by reading `BOARD.md` (Dillon talks to you there; his notes outrank TASKS.md order; answer him under "For Dillon"). Your work queue is `TASKS.md` in this repo. Each run: pick the **first** open `[ ]` task, do **only that one task**, then stop.
- Work on a branch `claude/<task-id-lowercase>` and open a pull request against `main`. Never push to `main`.
- In the same PR, mark the task `[x]` in `TASKS.md` with a one-line note (and the PR link once you know it).
- If a task needs something only Dillon can do (Google console, IONOS login, a secret), finish everything else, and list his steps at the top of the PR body under "Dillon needs to".
- Never print, commit, or log secrets. Secrets live only in `../dtm-secrets.php` on the server (one level above the site folder), never in git.
- Never publish live yourself. Deploys happen when Dillon presses "Pull latest" in the admin (it runs `pull.php`, which copies `main` onto the server).

## Stack and conventions
- Plain PHP 8 + HTML/CSS/vanilla JS. No build step, no Composer, no npm at runtime. Everything in the repo is deployed as-is.
- `pull.php` must keep working and must never hold a secret. Files/folders it never overwrites: `.htaccess`, `data/`, `uploads/`.
- Writing style for site copy: casual, funny, blunt. No em dashes.

## Brand ("Gig Poster", chosen by Dillon Nov 2026; the purple/teal/pink "Sea of Thieves / High Seas" look was rejected, never use it)
- Tone: aggressive but fun and cartoony, rock/metal gig-poster energy. Not cutesy, not graffiti, nothing Ozzy/bat-themed in branding.
- Look: black base, yellow halftone hero, red accents, cream cards, thick black outlines, offset cartoon drop shadows, slight rotations.
- Palette: black `#111111`, yellow `#FFD21F`, red `#E4251B`, cream `#F2E8D5`; illustration extras `#FF8A00`, `#FFE23B`.
- Fonts (Google Fonts): Metal Mania (wordmark/headings), Bangers (labels, buttons, stickers), Archivo (body).
- `index.php` + `assets/site.css` are the reference implementation. Reuse their classes for new pages.
- Logo: yellow warning sign with a music-note exclamation mark, bullet hole and burnt corner. Transparent `assets/logo.png` (512), `assets/logo-192.png`, favicons, `assets/og-image.png`; untouched original `assets/logo-original.png` (1024, cream background). Pair it with the Metal Mania wordmark. Host photos for thumbnails/art: `brand/photos/` (see its README; never use the Counterfeit one). Vector master from Illustrator: `brand/DTM_Logo.pdf` (+ `brand/DTM_Logo.svg`) for print, merch and cover art; the site uses the PNG because the vector trace flattens the halftone and bands the burn.

## The show
- Solo host, goes by **Duck** on the show (Chicagoland, 32 years playing guitar), about 15 minutes per episode. NEVER mention his tribute band, Counterfeit, or "my band" in any script or show copy. Dark-comedy narrative history: live-show disasters, meltdowns, terrible decisions.
- Episode titles: "Due To ..." (like a cancellation notice). Subtitles: a "Who ...?" question that assigns blame (e.g. "Who Gave The Who Explosives?"). Keep titles under ~40 characters.
- Ep 0 Due To Unforeseen Circumstances / Who Gave This Guy a Mic? Ep 1 Due To Excessive Flash Powder / Who Gave The Who Explosives? Ep 2 Due To The Venue Being Ripped Apart / Who Asked Fred Durst to Be a Therapist? Ep 3 Due To a Possible Rabies Infection / Who Let That Guy Bring a Bat?
- Every story is pitched as "so wild it sounds fake, but it's real", so never invent facts in site copy. Unverified legends get framed as "the story goes".

## Google sign-in (shared across Dillon's sites)
- One OAuth Web client for all projects (Google Cloud project `duck-andrabbit-co`, client `dnr-sites-signin`).
  Client ID (public): `189196968664-684fp4dk94n94hq26ovccm65450457b1.apps.googleusercontent.com`
- Read it from `google_client_id` in `../dtm-secrets.php`; admin emails from `admin_emails` there.
- Verify ID tokens server-side (Google tokeninfo or JWKS), check `aud` and `email_verified`, and allow only `admin_emails`.
- Top-bar rule: show only the signed-in user's name, linking to an account page; sign-out lives on that page.
- Origins `https://defacingthemusic.com` and `https://www.defacingthemusic.com` are already on the OAuth client (Nov 11). Google sign-in only works over https, so until SSL exists the admin uses password login (see DTM-ADMIN-1).
