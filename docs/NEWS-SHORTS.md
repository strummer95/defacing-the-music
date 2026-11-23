# News Shorts: "Due To This Week"

Duck, Nov 23: "i wonder if we should watch for crazy stories of the week, and maybe i can do shorts like 1-5 minutes here and there to just keep content fresh... but the news needs to be wild enough, not tragic, and not boring... i want stuff that anyone could find interesting, not just fans of who its about."

Quick vertical Shorts between full episodes. Claude finds the picks every week; Duck reads one on camera.

## What makes the cut (all of these)
- **Wild.** Somebody did something nobody sane would do, or something absurd happened. "Band cancels tour", "album leaks", "two rappers beef" is not wild.
- **Anyone would care.** The test: would your mom, your coworker who only listens to the radio, and a teenager all go "wait, what?" without knowing who the artist is? If the story only works when you're a fan, skip it. The weirdness has to carry it, not the name.
- **Not tragic.** Nobody died, nobody badly hurt, nobody's fresh crisis (overdose, illness, abuse, rehab). Small bruised egos and wrecked property are fine.
- **Not polarizing.** No politics, elections, causes, race, religion, culture-war fights, even if it's the week's biggest music story (CLAUDE.md hard rule).
- **Real and sourced.** At least one solid outlet (AP, Reuters, BBC, Billboard, Rolling Stone, local news). No rumor accounts, no "fans are saying". Ongoing legal stuff about living people: "reportedly", "according to", "police say", and name the outlet.
- **From the last 7 days** (an older story that just got a crazy new twist is fine). Every pick carries a **Happened:** date (Duck, Nov 23: "dates will be critical"), and the script says when it happened ("Last Saturday...", "November 7th...") so the Short still makes sense a month later. admin > Side Stories flags a Due To This Week story as aging after 4 days and stale after 7.

When a week has nothing that clears the bar, say so and give 0 or 1 picks. A boring Short hurts more than no Short.

## Format of each pick
- 45 to 90 seconds read aloud (about 110 to 220 words). Under 3 minutes always, or YouTube won't count it as a Short.
- **Line 1 is the hook** (see "Shorts, Reels, TikTok" in `docs/EDITING-BRIEF.md`): the single craziest specific fact, said cold. No "so this week", no intro.
- Then the story with one oddly specific detail (the rabbit-hole pillar, shrunk), a real quote if there's a good one (exact wording, outlet named), and a quick `[THAT GUY STING]` verdict to close on.
- Duck's voice: casual, blunt, funny. No em dashes. Prices get today's money only if it's an old price (news prices are already today's).
- Never mention Counterfeit or "my band".

## Where it goes
`lib/news/YYYY-MM-DD.md` (the Monday it was made), read in admin > News. Template:

```
# Due To This Week: <date range>

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
