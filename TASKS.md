# Defacing The Music tasks

One task per run, top to bottom. Mark done `[x]` with a one-line note and PR link.

## [ ] DTM-ADMIN-1: Admin area behind Google login with a "Pull latest" button
Build `admin/` (replace the placeholder): Google Identity Services sign-in button, server-side ID token verification,
PHP session, access only for emails in `admin_emails` from `../dtm-secrets.php` (Dillon only). Everyone else gets a polite 403.
Admin top bar: site name left, signed-in user's name right (links to `admin/account.php` with sign-out), and a
**"Pull latest"** button in the top right that POSTs (with a CSRF token) to a server-side admin endpoint, which calls
`pull.php` internally with the deploy key from the secrets file (the key never reaches the browser) and shows the output.
Session cookies Secure/HttpOnly/SameSite=Lax, throttle failed sign-ins. Document the exact `../dtm-secrets.php` shape in README.

## [ ] DTM-SITE-1: Real landing page
Replace the starter `index.php` with a proper one-page site: hero with wordmark and tagline, "what's the show" blurb,
episode list area (empty state "first episode coming soon"), listen-on links section (placeholders for Spotify, Apple, YouTube),
contact/email signup placeholder, footer. Brand per CLAUDE.md. Mobile first, fast, good meta/OG tags, favicon placeholder.

## [ ] DTM-EPISODES-1: Episodes managed from the admin
`data/episodes.json` (flock-locked writes) with title, slug, number, date, summary, story subject (band/artist), embed URL, cover image.
Admin CRUD page for episodes; public episode list on the home page and an `episode.php?slug=` page. Validate and escape everything.

## [ ] DTM-STORIES-1: Story idea backlog in the admin
Admin-only page to log candidate stories (title, artist, year, one-line hook, source links, status idea/researching/recorded).
Seed it with: Limp Bizkit and the Woodstock '99 riot; Keith Moon blowing up his drum kit on The Smothers Brothers Comedy Hour.
