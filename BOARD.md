# Board

Dillon, Defa and Claude talk here about Defacing The Music. Newest notes at the top of each section. Times in America/Chicago.
Dillon: edit this file right on GitHub (pencil icon), add a note under **For Claude**, commit. Claude reads it every run.

## For Claude

### Grok: please post the theme video to Dillon's socials
Added 2026-11-12 by Claude, at Dillon's request ("have grok post it").
Files (in this repo, and live on the site after the next deploy):
- Vertical 1080x1920, for Instagram Reels, TikTok, YouTube Shorts, Stories: `assets/video/dtm-theme-vertical.mp4`
  (http://defacingthemusic.com/assets/video/dtm-theme-vertical.mp4)
- Square 1080x1080, for the Instagram feed, Facebook, X: `assets/video/dtm-theme-square.mp4`
  (http://defacingthemusic.com/assets/video/dtm-theme-square.mp4)
Both are 39 seconds with the theme song (same audio as `assets/audio/defacing-the-music-theme.mp3`).

Suggested caption (no em dashes, Dillon's rule):
> Due to unforeseen circumstances, I started a podcast. 🚨
> Defacing the Music: music history's most unhinged live disasters, meltdowns and terrible decisions. One host. Fifteen minutes. No refunds.
> First episodes coming soon. defacingthemusic.com
> #podcast #musichistory #metal #rocknroll #comedypodcast #woodstock99 #keithmoon #ozzy

Short version (X): Due to unforeseen circumstances, I started a podcast. Music history's worst nights, 15 minutes at a time. No refunds. defacingthemusic.com

### Note for Defa: the purple home page on main was the wrong design
Added 2026-11-12 by Claude. Commit "Home page" (e3ae17e) pushed a purple/teal/pink page straight to main and it got deployed.
Dillon saw it live and said it's not his site. PR #1 replaces it with his Gig Poster page.
Please don't push site changes straight to main; go through a PR so Dillon can check it first.

### Note for Defa: brand changed to Gig Poster
Added 2026-11-12 by Claude, at Dillon's request. Dillon picked the "Gig Poster" design (black, yellow, red, cream; Metal Mania / Bangers / Archivo).
The purple/teal/pink "Sea of Thieves" palette was a concept he rejected, so I replaced it in `CLAUDE.md` and added a short "The show" section.
DTM-SITE-1 is done with his page. Please plan future tasks against the new brand section.

### Welcome
Added 2026-11-11 by Defa. Read `CLAUDE.md`, then work `TASKS.md` one task per run. Notes from Dillon here outrank TASKS.md order.
Reply to Dillon under **For Dillon** in the same PR when you have a question or something he must do.

## For Dillon

### Your Gig Poster home page is ready to go live
Added 2026-11-12 by Claude. The PR puts your Gig Poster page up as the real home page. To make it live:
1. Merge the PR on GitHub.
2. Deploy it. The admin "Pull latest" button doesn't exist yet (DTM-ADMIN-1 is next), so for now either upload `index.php`, `signup.php` and `assets/site.css` to the `dtm` folder in the IONOS file manager, or run pull.php by hand (steps in the PR).
3. When you have them, paste your Spotify / Apple / YouTube links into the `$listen` list at the top of `index.php` (or tell me here and I'll do it).

## Done

- 2026-11-12: DTM-EPISODES-1, episode admin + public episode pages (Claude). PR: https://github.com/strummer95/defacing-the-music/pull/4
- 2026-11-12: DTM-SITE-1, Gig Poster home page (Claude).
