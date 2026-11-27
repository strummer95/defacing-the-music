# Defacing The Music: Editing Brief (Episode 1 and beyond)

From Duck, Nov 22, 2026 (updated the same day: ep1 source files, OBS track routing, style check before big renders). The rules for editing every episode.

Host goes by "Duck" (never Dillon in show copy). Co-host Pat reacts unscripted. Comedy music-history podcast. Tone: loose, two friends at a bar, let laughs breathe. Never use em dashes in anything written for Duck.

## Source files
- Episode 1 copies (SHA256-verified) on the shared computer: `/workspace/dtm-edit/raw/` duck-p1.mkv, duck-p2.mkv, pat-p1.mkv, pat-p2.mkv, room-p1.mkv (10.8GB), room-p2.mkv. Part 1 about 36:24, part 2 about 40:26. Not synced yet: sync cams by audio. Notes: `/workspace/dtm-edit/NOTES.md`
- Ep1 OBS program recordings' three tracks are also identical (routing was wrong). From Nov 22 on, OBS records Track 1 mix, Track 2 Duck only, Track 3 Pat only.
- Originals for ep1 on Duck's PC: `D:\DTM\Episode 1 Ozzy\` (do not modify). Episode 2 Woodstock raws: `D:\DTM\Episode 2 Woodstock\` (later).
- Duck's PC: `D:\DTM\Video\{DUCK,PAT,GROUP}\` ("DTM Duck ep1 part 1.mkv" etc.)
- Three cams per part, 30fps h264/AAC mkv: Duck 1920x1080, Pat 1080x1920 (portrait), Room 1920x1080. Ep1 is two parts (~36 and ~40 min).
- Each file carries the same single stereo mix. No isolated mic tracks for ep1.

## Workflow
0. Check with Duck on style before any big render; he wants to train the editor on his taste.
1. Transcribe the full episode with timestamps and Duck/Pat speaker labels.
2. Duck marks cuts in the transcript, plus cold open and clip picks.
3. Edit from the marked transcript.
4. Check every requested cut against the audio. If it would clip a word, step on cross-talk, or land mid-laugh, shift it to the nearest clean pause or hide it with a camera switch. If it can't be done smoothly, flag it and suggest an alternative.

## Cleanup
- Remove dead air, throat clears, nose blows, and flubbed lines (Duck restarts from the start of the sentence, so cut back to there).
- **Don't cut pauses by default (Duck, Nov 22).** A lot of pauses are on purpose: a beat after a point lands, room for Pat to react, a breath before a reveal. Keep them. Only cut a pause if I marked it, or if it's obviously dead air (lost my place, fumbling, a technical stop, waiting for something). The `>> PAUSE` flags in the transcript are information, not orders. Never trim the silence right after a punchline, a reveal or a laugh.
- Don't over-trim normal ums.
- Audio: dynamic leveling on the single mix. Boost Pat where he's low and keep both voices even. Tame spikes.

## Layout
- No fixed 3-cam layout (that recording is only a backup).
- Mostly a side-by-side two-shot of Duck and Pat (crop Pat's portrait cam to match), cutting to a single person or the room cam for reactions and punchlines.
- Never show anyone vaping: cut to the non-vaping person or a clean room shot.
- Beer is OK in full episodes. Avoid it in short clips when easy, and flag beer moments in the transcript.

## Structure
**First 3 minutes (Nov 27, Ep 1 retention):** half of viewers left in the first 30 seconds and the rest settled by minute 3, then held flat to the end. So: frame one is Duck mid-line on the cold open (no logo hold, no fade-in, no setup shot); captions on from the start; the theme is the 22-second short theme (see Assets), never the full 39-second song; no dead air between the theme and the first line after it.
Cold open (~30s teaser hits) > "I'm Duck, and due to [title], this is Defacing the Music." > theme > "Episode [n]. [subtitle]" title card > the story > "That Guy" segment (suspects, verdict, guitar squeal sting, "Because in every story there's a that guy. Don't be that guy.") > sign-off "I'm Duck. Due to unforeseen circumstances, I'll see you next time." > theme.

## Assets
- New Backstage theme video (full 39s song): `/workspace/dtm-edit/theme/dtm-theme-backstage.mp4` (+ `-vertical.mp4`). Tagline "TRUE STORIES. TERRIBLE DECISIONS."
- **Short theme, used in every episode from now on (Duck, Nov 27, picked version D):** 22.4 seconds = the song's intro 0:00 to 0:09.60, then straight to the big hit at 0:26.53 through the hard-stop ending (cuts land on bar lines, 113 BPM; 30 ms crossfade at the join). The audio is `assets/audio/defacing-the-music-theme-short.mp3`. For the video, cut `dtm-theme-backstage.mp4` at the same two points (0:00 to 0:09.60 plus 0:26.53 to the end) so picture and music match. The "it's all real" lyric is in the dropped middle; Duck is fine losing it. The full 39-second version stays for trailers and promos.
- Title card PNGs and overlays: `/workspace/dtm-obs-backstage/` (includes ozzy-title.png and ozzy-subtitle.png)
- Note (Claude, Nov 22): the `/workspace/...` paths are on the shared editing computer, not in this repo or in cloud sessions. Upload them in admin > Uploads (or commit them under `brand/`) so any session can use them.
- Brand: stage #161518, floor #1E1D21, amber #FFB547, red #E4251B, paper #F6F3EC. Fonts Saira Stencil One, Oswald Bold, Inter, Permanent Marker. Never purple, teal, or pink.

## Deliverables
Full episode 1920x1080 for YouTube, then 3 to 5 vertical 1080x1920 clips for TikTok, Reels, Shorts. Nothing gets posted without Duck's approval.

## Shorts, Reels, TikTok: win the first second (Duck, Nov 23)
Duck: "we need to maximize our potential." The first Short (Ozzy bat, 21s) got 49 views in two hours with only 30% "stayed to watch" (70% swiped away in the first second or two). Shorts that keep getting pushed sit around 60 to 70%+. So every clip is built around the opening:
- **Frame one is the hook.** Start on the craziest, most specific line of the clip, mid-energy. No setup, no "so", no breath, no logo, no intro card. Trim any lead-in, even half a second.
- **Hook on what people don't know.** Everybody knows the bat; the dove in the boardroom, the frog, the cow's head are the hook. The famous part is the payoff, not the opener.
- **Big on-screen text from the first frame** (most people scroll with the sound off): a short line that promises something weird, in the brand look (Saira Stencil One / Oswald, paper on dark, red or amber accent), kept clear of the bottom fifth and right edge where the app buttons sit.
- **Burned-in captions all the way through**, big, a few words at a time.
- **Keep it tight:** 20 to 45 seconds. Cut every pause that isn't a punchline beat (in clips, pauses are shorter than in the full episode).
- **End on a laugh or a punchline**, not a trailing sentence. A clean loop back to the first line is a bonus.
- **Title and caption** say the weird specific thing ("Ozzy bit a dove's head off in a record label meeting"), never just "the Ozzy bat story".
- **End card (Duck, Nov 23):** every Short, Reel and TikTok ends on `brand/shorts/shorts-end-card-2s.mp4` (or the still `shorts-end-card.png`, held about 1.5 to 2 seconds): logo, name, defacingthemusic.com, "Full episodes on YouTube". Cut to it hard right after the last laugh or punchline; no fade-out of the story, no "thanks for watching". Keep it to 2 seconds or less (a long ending gets swiped and hurts the loop). Its text sits clear of the right-side buttons and the bottom caption area.
- **Related video:** when posting any Short, set its Related video to the full episode it came from (Fresh Tracks: the newest full episode).
- **Volume:** post several Shorts per episode, one every day or two around release. Each one is a fresh test; new channels need a handful before one catches.
- For each clip, give Duck 2 or 3 opening-line options (with timestamps) and the on-screen hook text, so he can pick the strongest. Check the "stayed to watch" number after each one and tell Duck what worked.

## Segments: the middle size (Duck, Nov 23: "we need some more digestable content... these first 2 episodes are like an hour and i really cant get them cut down shorter")
The full episode stays full (an hour is normal for this kind of show). Every full episode also gets cut into **2 to 4 segments**: standalone 5 to 15 minute YouTube videos, each one complete chapter of the story with its own title and thumbnail ("The Dove Incident: Ozzy vs a Record Label", "The Night Ozzy Peed on the Alamo"). They sit between Shorts (seconds) and the full episode (an hour).
- **Mark them in the transcript** as clips named `SEGMENT: <title>` (same CLIP marks as Shorts; the name tells you which is which).
- **A segment starts like a Short:** cold, on its best line, then gives just enough setup to stand alone (no "as we said earlier"). Keep the jokes and Pat's reactions; trim side trips.
- **Ends with a pointer to the full episode** ("the whole story's in the full episode, link's up top") and the end card.
- **YouTube chapters on the full episode too:** timestamps in the description (0:00 Cold open, 2:10 ..., etc.) so people can jump to the part they want. Chapters need at least 3 timestamps starting at 0:00.
- Post order around a release: full episode, then one segment and Shorts every day or two after it.

## Export settings (every video, every platform)
Spotify rejected episode 1 (Nov 23): "The color details (color range, space, primaries, and/or transfer characteristics) of the video frames change during your video." One camera (likely a phone in HDR) was tagged differently from the others. YouTube hides it; Spotify refuses. So every export:
- **One color setting for the whole video: Rec. 709, SDR, no HDR.** Premiere: Sequence Settings > Working Color Space Rec. 709 (HDR/auto tone-mapping off); any phone clips: Modify > Interpret Footage > color space override Rec. 709 (tone-map real HDR/HLG footage first); export H.264, Rec. 709 output, HDR off.
- **Check before upload:** `ffprobe` the export and confirm color primaries, transfer and matrix are all bt709 and the range is tv, with nothing changing partway through.
- **Fix for a file that's already exported:** `ffmpeg -i in.mp4 -vf "scale=out_color_matrix=bt709:out_range=tv,format=yuv420p" -c:v libx264 -preset medium -crf 18 -color_primaries bt709 -color_trc bt709 -colorspace bt709 -color_range tv -c:a aac -b:a 192k -movflags +faststart out.mp4`
- The same file goes to YouTube and Spotify (one export, both platforms).

## One paste per release
Duck (Nov 23): bouncing between sessions "ruins the flow of everything." The cloud session hands the PC session **one block per release** covering everything that needs the video files: the edit, segments, Shorts (with end card), exports and the checks above. Report back once, at the end, with every file and anything that needs Duck's call.

## Hard rules
No political, racial, or polarizing content. Never mention Counterfeit or "my band".
