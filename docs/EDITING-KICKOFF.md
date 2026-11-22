# Kickoff: edit Defacing the Music, Episode 1 (Ozzy)

Paste everything below into a new **Local** Claude Code session opened in `D:\DTM`.

---

You're the editing assistant for my comedy music-history podcast, Defacing the Music. I'm Duck (the host). Pat is my co-host. Work only on my computer, in `D:\DTM`. **Never change, move or delete anything in `D:\DTM\Episode 1 Ozzy\`**: those are my originals. Put everything you make in `D:\DTM\Episode 1 Ozzy\edit\`.

Before you start, read the editing brief at the bottom of this message. It's the rules.

## Phase 1 (do this now, then stop and show me)
1. **Check the files.** List what's in `D:\DTM\Episode 1 Ozzy\` with length, resolution and size. Episode 1 is two parts. Each part has a Duck cam (1920x1080), a Pat cam (1080x1920 portrait) and a Room cam (1920x1080), plus OBS recordings that are only a backup. Every file has the same single stereo audio mix.
2. **Tools.** Use free tools: ffmpeg, Python and faster-whisper (or whisper). Install what's missing. Ask me before installing anything big, and tell me how much disk space it needs.
3. **Sync.** For each part, find the time offset of the Pat and Room cams against the Duck cam, using the audio. Save the offsets in `edit\sync.json` and tell me how confident you are.
4. **Transcribe** both parts with timestamps. There's only one mixed track, so tell Duck and Pat apart by voice (Duck tells the story and talks most; Pat reacts). Mark anything you're unsure of as `[?]`.
5. **Write `edit\transcript-part1.md` and `edit\transcript-part2.md`**, one line per sentence or exchange, like:
   `[12:34] DUCK: And then he bites the head off.`
   Also flag, on their own lines:
   - `>> PAUSE 4.2s` for any gap over 2 seconds
   - `>> FLUB?` where I restart a sentence
   - `>> BEER?` / `>> VAPE?` if you can see it on camera (check frames every few seconds with ffmpeg); give the camera and timestamp
6. Stop and tell me it's ready. Don't render anything.

## How I'll mark the transcript (Phase 2 input)
I mark it on the website: admin > **Edit an episode**. I drag in your `transcript-part1.md` and `transcript-part2.md`, mark lines there, and then paste you a private link (`.../admin/edit-export.php?id=...&t=...`). Download that link (no login needed) to get the marked transcript. It uses these at the start of lines:
- `CUT` to remove that line (or `CUT START` ... `CUT END` for a stretch)
- `COLD` for the lines that make the cold open
- `CLIP <name>` ... `CLIP END` around each short-form clip (a line inside a clip can also be `CUT` or `COLD`)
- `[-like this-]` inside a kept line: cut just those words. Find their exact times in the audio (word timestamps), cut cleanly, and flag any you can't.
- Anything else I write in `(( ))` is a note to you.
- **The transcript can be wrong, and I don't fix it.** I mark based on what I know we said, not on how the transcript spelled it. So never skip or second-guess a mark because the words don't match the audio. Find a marked line by its part and timestamp, and a marked word by its position in the line (the 3rd word is the 3rd word spoken there), checked against the audio around it. Only flag it if you truly can't tell which spoken words I meant.

## Phase 2 (only after I say go)
Turn my marked transcript into an **Adobe Premiere Pro** project I can open and fine-tune. **The episode is ONE video**: part 1 then part 2 back to back on a single timeline (the split was only a recording break; hide the join with a cut or camera switch). Make an XML (FCP7 xmeml) timeline with the three synced cameras, my cuts applied at clean pauses (follow step 4 of the brief: never clip a word or land mid-laugh; flag anything you couldn't do cleanly), a camera choice per section following the Layout rules, and the cold open, theme and title-card spots marked. Then the vertical clip timelines. **Check with me on style before any big render** (brief, step 0).

---

## The editing brief
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
- Remove dead air, long audible pauses, throat clears, nose blows, and flubbed lines (Duck restarts from the start of the sentence, so cut back to there).
- Don't over-trim normal ums.
- Audio: dynamic leveling on the single mix. Boost Pat where he's low and keep both voices even. Tame spikes.

## Layout
- No fixed 3-cam layout (that recording is only a backup).
- Mostly a side-by-side two-shot of Duck and Pat (crop Pat's portrait cam to match), cutting to a single person or the room cam for reactions and punchlines.
- Never show anyone vaping: cut to the non-vaping person or a clean room shot.
- Beer is OK in full episodes. Avoid it in short clips when easy, and flag beer moments in the transcript.

## Structure
Cold open (~30s teaser hits) > "I'm Duck, and due to [title], this is Defacing the Music." > theme > "Episode [n]. [subtitle]" title card > the story > "That Guy" segment (suspects, verdict, guitar squeal sting, "Because in every story there's a that guy. Don't be that guy.") > sign-off "I'm Duck. Due to unforeseen circumstances, I'll see you next time." > theme.

## Assets
- New Backstage theme video (full 39s song): `/workspace/dtm-edit/theme/dtm-theme-backstage.mp4` (+ `-vertical.mp4`). Tagline "TRUE STORIES. TERRIBLE DECISIONS."
- Title card PNGs and overlays: `/workspace/dtm-obs-backstage/` (includes ozzy-title.png and ozzy-subtitle.png)
- Note (Claude, Nov 22): the `/workspace/...` paths are on the shared editing computer, not in this repo or in cloud sessions. Upload them in admin > Uploads (or commit them under `brand/`) so any session can use them.
- Brand: stage #161518, floor #1E1D21, amber #FFB547, red #E4251B, paper #F6F3EC. Fonts Saira Stencil One, Oswald Bold, Inter, Permanent Marker. Never purple, teal, or pink.

## Deliverables
Full episode 1920x1080 for YouTube, then 3 to 5 vertical 1080x1920 clips for TikTok, Reels, Shorts. Nothing gets posted without Duck's approval.

## Hard rules
No political, racial, or polarizing content. Never mention Counterfeit or "my band".
