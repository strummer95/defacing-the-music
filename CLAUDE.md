# Defacing The Music: notes for Claude

Site for **Defacing The Music**, Dillon Johnson's comedy podcast about the craziest stories in music history
(Limp Bizkit and the Woodstock '99 riot, Keith Moon blowing up his drums on the Smothers Brothers, and so on).
Live at https://defacingthemusic.com behind Cloudflare (SSL mode Flexible: visitors get https, Cloudflare talks plain http to IONOS, so PHP sees http; use X-Forwarded-Proto / `dtm_is_https()`). `lib/https.php` 301s http visitors to https, served from the `dtm` folder on Dillon's IONOS webspace (shared PHP hosting, no database assumed; use JSON files under `data/` with flock locking).

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
Source of truth for these rules: Duck's own decisions, most recently `docs/HANDOFF-2026-11-14-show-notes.md` (read it in full before writing or editing scripts; it overrides older guidance where they conflict).

### Basics
- Solo host, goes by **Duck** on the show (Chicagoland, 32 years playing guitar). NEVER mention his tribute band, Counterfeit, or "my band" in any script or show copy. Dark-comedy narrative history in the style of The Dollop.
- Episode titles: "Due To ..." (like a cancellation notice). Subtitles: a "Who ...?" question that assigns blame and basically names the suspect (e.g. "Who Gave The Who Explosives?"). Keep titles under ~40 characters.
- Ep 0 Due To Unforeseen Circumstances / Who Gave This Guy a Mic? Ep 1 Due To Excessive Flash Powder / Who Gave The Who Explosives? Ep 2 Due To The Venue Being Ripped Apart / Who Asked Fred Durst to Be a Therapist? Ep 3 Due To a Possible Rabies Infection / Who Let That Guy Bring a Bat?
- **Episode 0 is published and cannot be changed.** Any fix that touches ep 0 has to happen in later episodes instead. (Ep 0 says the Keith Moon show was "live network TV" (it was taped Sept 15, 1967, aired Sept 17), states "about ten times the flash powder" as fact, and states that the band "got asked to calm down a quarter million people" as fact. Later episodes must get these right, not repeat them.)

### Story selection rule (every episode)
- Every episode must be EITHER a story everybody knows told with new information, OR a story people don't really know.
- Duck: "I want people to listen to an episode, and even if they know the story, get something new or fresh or interesting that then makes them want to listen to the other stories. Like, oh yeah, I knew Ozzy bit the head off a bat, but I wonder what else is with that story."
- "New information" means deeper details: how it got handled, how it affected people's personal lives, how it really played out. The basic story alone is not enough.
- The show is NOT about things merely going wrong. Duck: "It's about crazy things or really funny parts of crazy stories." A story that's just "something went badly" (e.g. Dylan going electric) doesn't qualify unless there's something crazy or funny in it.

### Honesty and legends
- Every story is pitched as "so wild it sounds fake, but it's real", so never invent facts in scripts or site copy. Unverified details get framed as "the story goes" / "the legend goes".
- Legends are fine IF they're called legends and the episode plays up how wild the retellings got. Duck: "the legends do work, as long as it's treated like that and we highlight how crazy the stories about it got." A "how the legend grew" episode is a valid format (e.g. Keith Moon's car in the pool).
- People who got hurt or died are never the joke; the joke is the guy who decided it was fine.

### Length (overrides the old "about 15 minutes")
- **15 minutes is the MINIMUM. Closer to 30 is probably ideal.** Scripts that run 16+ minutes before riffing are "totally cool". **Do NOT trim scripts for length.**
- Reason: Duck doesn't know yet how good he'll be at riffing, so the script alone must hit the minimum. He'll edit down later once he figures out recording; right now it's a single camera on him, one take, with no natural way to cut without it looking odd.
- Measure spoken length as words outside [brackets] at about 150 words per minute. Shorter scripts (eps 1 to 3 run about 13 to 15 min) should get MORE material, not less.

### Episode format (Duck's description, confirmed)
1. **Cold open:** a wild, brief teaser, just a few sentences about what the episode's about. (Scripts also carry a short "Today" summary before the intro line.)
2. **"I'm Duck, and this is Defacing the Music."**
3. **Theme song** (`[THEME SONG, hard stop]`).
4. **Coming out of the theme:** Duck starts the episode, announces the title ("Due To ... Or, Who ...?"), and gets into it. Library scripts don't say an episode number (Duck numbers them when he makes them episodes in the admin).
5. The story, with `## ` section headings. A straight `## THE SERIOUS PART` when people were hurt or died.
6. **"That Guy"** (`## THAT GUY`), every episode: the point where Duck names the one who caused it or is to blame. "Because in every story there's a 'that guy.' Don't be that guy." Plant it early ("somewhere in this story there's a guy"), drop suspects, give a verdict; if blame is disputed, say so; when it's genuinely a group, say so (but see crutches below).
7. **Sign-off:** something like "I'm Duck. Due to unforeseen circumstances, I'll see you next week." Then back into the theme song and done. Don't name the next episode in library scripts (order isn't decided).
- Every `[RIFF: ...]` must be followed by a written fallback Duck can read ("Or use this:"). Optional asides go in `[FILL: ...]`.

### "That Guy" sound sting
- Duck's worry: sound effects can sound hokey and cheesy. So: ONE signature sting, used the same way every time. No stock effects (air horns, laugh tracks, cartoon boings).
- It's a guitar pinch-harmonic squeal Duck records himself on his own guitar (less hokey than a library sound). About one second, short and ugly; he records a few versions with different notes and gain and picks the one that makes him laugh. Too long or too clean starts to feel like a bit. (His vibe reference: something like Aqua Teen Hunger Force, unconfirmed.)
- In scripts, mark it as `[THAT GUY STING]` at the moment That Guy is named.

### Optional segments (only when the story has it, not every episode)
- **Legend or Fact:** near the end, call out which parts are real and which grew over the years. Only when a story has those questions.
- **Where Are They Now:** a closer on what happened to the people involved afterward. Duck: "a very good idea", and a good way to add content and length.

### Social clips (important to Duck)
- Every episode needs moments that can be cut for TikTok, Shorts and Reels: things people can consume quickly to get hooked and get the vibe without listening to a whole episode.
- Write scripts so there are several 30 to 60 second standalone moments per episode. The "That Guy" moment is a natural one. Mark them in scripts with `[CLIP: short label]` at the start of the moment so they're easy to find when editing.

### Writing crutches to avoid
- "A whole office full of guys": save it for when it's genuinely a group (it was in 9 of 13 scripts).
- `[Pause.]`: fewer, better placed (10 to 15 per script is too many to mean anything).
- Repeated phrases across scripts: "I looked this up twice", "I need you to picture", "Honesty check", "Remember that name / hold onto that". Vary them.
- No em dashes anywhere in Duck's copy. No AI filler words ("absolute", "literal", "pure unfiltered", "masterclass").

### Later: listener submissions
- If enough listeners send in stories, do special episodes, about half an hour, where Duck reads submitted stories, under a slightly different title: "Defacing the Music: [something]". Name not chosen yet. Options: "Local Opener" (Claude's top pick), "Dive Bar Edition", "Basement Show", "Open Mic Night", "Battle of the Bands", "Incident Reports", "Complaints Department".

### Recording order (suggested, not locked)
- Eps 1 to 3 as planned, then Disco Demolition, then the Dave Matthews bus (two Chicago stories early, easiest for Duck to riff on).

## Google sign-in (shared across Dillon's sites)
- One OAuth Web client for all projects (Google Cloud project `duck-andrabbit-co`, client `dnr-sites-signin`).
  Client ID (public): `189196968664-684fp4dk94n94hq26ovccm65450457b1.apps.googleusercontent.com`
- Read it from `google_client_id` in `../dtm-secrets.php`; admin emails from `admin_emails` there.
- Verify ID tokens server-side (Google tokeninfo or JWKS), check `aud` and `email_verified`, and allow only `admin_emails`.
- Top-bar rule: show only the signed-in user's name, linking to an account page; sign-out lives on that page.
- Origins `https://defacingthemusic.com` and `https://www.defacingthemusic.com` are already on the OAuth client (Nov 11). Google sign-in only works over https, so until SSL exists the admin uses password login (see DTM-ADMIN-1).
