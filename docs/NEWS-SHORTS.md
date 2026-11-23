# News Shorts: "Fresh Tracks"

Name picked by Duck (Nov 23). On a Short, the label reads like "Fresh Tracks: StubHub sold a concert that doesn't exist."

Duck, Nov 23: "i wonder if we should watch for crazy stories of the week, and maybe i can do shorts like 1-5 minutes here and there to just keep content fresh... but the news needs to be wild enough, not tragic, and not boring... i want stuff that anyone could find interesting, not just fans of who its about."

Quick vertical Shorts between full episodes. Claude finds the picks every week; Duck reads one on camera.

## What makes the cut (all of these)
- **Wild.** Somebody did something nobody sane would do, or something absurd happened. "Band cancels tour", "album leaks", "two rappers beef" is not wild.
- **Anyone would care.** The test: would your mom, your coworker who only listens to the radio, and a teenager all go "wait, what?" without knowing who the artist is? If the story only works when you're a fan, skip it. The weirdness has to carry it, not the name.
- **Not tragic.** Nobody died, nobody badly hurt, nobody's fresh crisis (overdose, illness, abuse, rehab). Small bruised egos and wrecked property are fine.
- **Not polarizing.** No politics, elections, causes, race, religion, culture-war fights, even if it's the week's biggest music story (CLAUDE.md hard rule).
- **Real and sourced.** At least one solid outlet (AP, Reuters, BBC, Billboard, Rolling Stone, local news). No rumor accounts, no "fans are saying". Ongoing legal stuff about living people: "reportedly", "according to", "police say", and name the outlet.
- **From the last 7 days** (an older story that just got a crazy new twist is fine). Every pick carries a **Happened:** date (Duck, Nov 23: "dates will be critical"), and the script says when it happened ("Last Saturday...", "November 7th...") so the Short still makes sense a month later. admin > Side Stories flags a Fresh Tracks story as aging after 4 days and stale after 7.

## Duck's calls on the first batch (Nov 23): learn from these
- **Yes:** StubHub selling a concert that doesn't exist. Ye locking a composer in a high school band room to write an opera ("kinda solid for DTM"; Duck's call to allow it: tell the lawsuit story only, say "allegedly", nothing about Ye's views or politics). The Beach Boys touring with no original members, **if** it's built as a chain: this one left, then this, then this, and now nobody's left but they can still use the name.
- **Maybe:** the cruise pianist rescued by marlin fishermen. Slayer's show stopped because fans wrecked the venue (depends on what actually happened).
- **No:** a metal band playing a Denny's ("not even a big deal... isnt even that crazy"). Fred Armisen's sound-effects Grammy nod ("not really shit going real wrong"). A $500k guitar theft ("not that interesting"). Kate Nash's tour losses. Brian Eno not flying (an irony one-liner, not a story).
- **So the bar is:** something has to genuinely go wrong or be insane, ideally with a chain of decisions (the How Did This Get Made side). Quirky, cute or "huh, neat" doesn't make it. A story that's a sequence of events building to something absurd beats a single odd fact.

When a week has nothing that clears the bar, say so and give 0 or 1 picks. A boring Short hurts more than no Short.

## Format: one weekly video, cut into Shorts (Duck, Nov 23)
Duck: "maybe they should just be 5 minutes... with the plan to make a short or 2 of them?... this was intended for shorts but 3 minutes is very short." So:
- **One solo Fresh Tracks video a week, about 5 minutes, with 2 or 3 stories** read back to back. Posts as a regular YouTube video.
- **Each story stands alone at about 90 seconds (200 to 250 words)**: its own hook line, its own That Guy. The editor cuts each story out as its own Short (with the end card). One recording = one weekly video + 2 or 3 Shorts.
- **The filter still applies per story:** if it isn't wild or funny in the first line and paying off within about a minute, it doesn't make the week.
- **Video wrapper:** a 2-line open ("This is Fresh Tracks, the music news that went wrong this week.") and a 1-line close, kept out of the story blocks so each Short starts on its own hook.
- Line 1 of every story is the hook (see "Shorts, Reels, TikTok" in `docs/EDITING-BRIEF.md`): the craziest specific fact, said cold. Then a rapid chain (one line per beat), one oddly specific detail, a real quote (exact, outlet named), and a quick `[THAT GUY STING]`. Say when it happened. No TALK cues (solo). Duck's voice, no em dashes, never Counterfeit.

## Where it goes
`lib/news/YYYY-MM-DD.md` (the Monday it was made), read in admin > News. Template:

```
# Fresh Tracks: <date range>

## 1. <short label>
**Happened:** YYYY-MM-DD (the day it happened or first got reported; required, news goes stale fast)
**Wild because:** one line. **Why anyone cares:** one line.
**On-screen hook text:** <6 to 10 words>
**Title:** <the weird specific thing>
**Sources:** [Outlet: headline](url), ...

<script, about 150 words, line 1 = hook>

## 2. ...
## Close calls (skipped)
- <story>: skipped because <tragic / political / only fans care / not wild enough>
```
