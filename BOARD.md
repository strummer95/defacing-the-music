# Board

Dillon, Defa and Claude talk here about Defacing The Music. Newest notes at the top of each section. Times in America/Chicago.
Dillon: edit this file right on GitHub (pencil icon), add a note under **For Claude**, commit. Claude reads it every run.

## For Claude

### Teleprompter: script reader in the admin
Added 2026-11-12 by Defa, at Dillon's request. Do this next, before other tasks.
Dillon wants to read episode scripts from the site admin while recording, so his screen doesn't wash out his face on camera.
- Admin: add a Scripts section (create, edit, delete). Each script has an episode number, title and body. Body is plain text with simple markup: lines starting with `## ` are section headings, and `[RIFF: ...]`, `[FILL: ...]`, `[THEME SONG ...]` cues are shown highlighted.
- Reader view per script (admin only, behind the login): pure black background, white text, no other UI except tiny controls. Text scales to the window width (CSS clamp/vw), with buttons or keys to make it bigger or smaller (+/-) and optional auto-scroll with a speed control (space to start/stop). Full-screen button. Remember size and speed in localStorage.
- Cues in yellow #FFD21F, headings dimmed. No bright elements anywhere in the reader.
- Seed it with Episode 0 below.
- Open a PR (don't push to main). You may merge it when done; Dillon deploys with Pull latest.
- No em dashes in copy.

Episode 0 script (seed):
```
## COLD OPEN
A drummer blows up his kit on live TV.
A festival burns down while the band on stage plays a song called "Fire."
A man bites the head off a bat because he thought it was rubber.
Every one of those nights ended with some guy in a suit taping a piece of paper to a door. "Due to unforeseen circumstances."
This show is about everything that happened before he grabbed the tape.
[THEME SONG, hard stop]
I'm Dillon. This is Defacing the Music.
## WHO I AM
I play guitar in a Limp Bizkit tribute band called Counterfeit.
Counterfeit was Limp Bizkit's first single, in 1997. So every night we play it, a fake band is playing a song called Counterfeit. Nobody has ever found that as funny as I do.
[RIFF: worst or weirdest Counterfeit gig. Keep it short.]
So I'm not here as an expert. I'm here as a guy who has stood on a stage and watched a bad decision happen in real time. Sometimes it was mine.
## WHAT THIS IS
One story an episode. Fifteen minutes. A real night where a band, a promoter, a label, or a crowd took a bad idea all the way to the end.
It's all real. That's the point. If a detail is one of those things everybody repeats and nobody can prove, I'll tell you. If it's "the legend goes," that's me saying I wasn't there, and neither was the guy who told me.
When real people got hurt, I'll say so, and they're not the joke. The joke is the guy who decided this was fine. There's always a guy.
## WHAT'S COMING
Episode one. 1967. The Who play live on The Smothers Brothers Comedy Hour on CBS. Keith Moon packs his bass drum with explosives for the finish. Way more than he was supposed to. It goes off on live television, singes Pete Townshend's hair, and Pete has blamed that night for part of his hearing loss ever since.
[RIFF: one line about Keith Moon.]
Episode two. Woodstock '99. A concrete Air Force base in upstate New York, in July, with four-dollar water. On the last night the place is on fire, and the Red Hot Chili Peppers are on stage playing a Jimi Hendrix song called "Fire." Limp Bizkit was there too, so I've got a conflict of interest. I'll be fine.
Episode three. Des Moines, Iowa, 1982. A kid throws a bat on stage at an Ozzy show. Ozzy thinks it's rubber. It's not rubber. And it wasn't even his first animal.
After that: the night a casino burned down during a Frank Zappa show and a band across the lake wrote "Smoke on the Water" about it. Metallica and Guns N' Roses in Montreal, 1992. And a few that are less funny and more "what is wrong with people."
## OUT
One question to chew on until next time. On that same 1967 tour, The Who weren't the headliner. They were opening. For who? The answer's in episode one, and it's worse than you think.
[RIFF: defacingthemusic.com, Guest List signup, socials.]
I'm Dillon. Due to unforeseen circumstances, I'll see you next week.
```
(Quiz answer for episode 1: Herman's Hermits.)

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

- 2026-11-12: DTM-SITE-1, Gig Poster home page (Claude).
