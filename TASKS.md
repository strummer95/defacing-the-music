# Defacing The Music tasks

One task per run, top to bottom. Mark done `[x]` with a one-line note and PR link.

## [ ] DTM-ADMIN-1: Admin area with a "Pull latest" button that works over plain HTTP
The site has NO SSL for now (Dillon's call, Nov 11): everything must work on http://defacingthemusic.com.
Google Identity Services refuses non-https origins, so copy how strummer95/counterfeit's admin worked while
counterfeitchicago.com had no SSL: its `login.php` + `lib/security.php` pattern (password_hash login, per-IP throttle,
forgot-password by email, session). Build `admin/` (replace the placeholder) that way:
- Primary login: username/email + password (`password_hash`), credentials stored in `../dtm-secrets.php` or a
  server-side `data/` file outside what pull.php overwrites. First-run setup page if no admin exists yet, locked once set.
  Throttle failed logins per IP, session cookie HttpOnly + SameSite=Lax (Secure only when the request is https).
- Google sign-in: include it, but only render the button when the request is https AND `google_client_id` is set;
  verify ID tokens server-side, allow only `admin_emails`. It lights up automatically once SSL is added later.
- Admin top bar: site name left; right side shows the signed-in user's name (links to `admin/account.php` with sign-out
  and password change) and a **"Pull latest"** button in the top right. It POSTs with a CSRF token to a server-side admin
  endpoint that calls `pull.php` internally with `deploy_key` from `../dtm-secrets.php` (the key never reaches the browser)
  and shows the output.
- Document the exact `../dtm-secrets.php` shape in README, and put any server steps for Dillon under "Dillon needs to" in the PR.

## [x] DTM-SITE-1: Real landing page
Done: Dillon's chosen "Gig Poster" home page ported to `index.php` + `assets/site.css` (mobile layout, OG tags, favicon, scrolling ticker),
episodes show "Coming soon", listen links show "soon" until URLs are filled in at the top of `index.php`,
Guest List form saves to `data/subscribers.json` via `signup.php`.

## [ ] DTM-EPISODES-1: Episodes managed from the admin
`data/episodes.json` (flock-locked writes) with title, slug, number, date, summary, story subject (band/artist), embed URL, cover image.
Admin CRUD page for episodes; public episode list on the home page (replace the hardcoded `$episodes` array and keep the ticket-stub card look) and an `episode.php?slug=` page. Validate and escape everything.

## [ ] DTM-SUBSCRIBERS-1: Guest List in the admin
Admin page listing `data/subscribers.json` (count, newest first) with a CSV download and a remove button (CSRF protected).

## [ ] DTM-STORIES-1: Story idea backlog in the admin
Admin-only page to log candidate stories (title, artist, year, one-line hook, source links, status idea/researching/recorded).
Seed it with: Limp Bizkit and the Woodstock '99 riot; Keith Moon blowing up his drum kit on The Smothers Brothers Comedy Hour;
Ozzy and the bat in Des Moines (1982); Mayhem (Norwegian black metal); the Bjork stalker case; the Montreux casino fire ("Smoke on the Water");
GG Allin; Metallica/Guns N' Roses in Montreal 1992.
