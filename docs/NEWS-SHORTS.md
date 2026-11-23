# News Shorts: "Fresh Tracks"

Name picked by Duck (Nov 23). On a Short, the label reads like "Fresh Tracks: StubHub sold a concert that doesn't exist."

Duck, Nov 23: "i wonder if we should watch for crazy stories of the week, and maybe i can do shorts like 1-5 minutes here and there to just keep content fresh... but the news needs to be wild enough, not tragic, and not boring... i want stuff that anyone could find interesting, not just fans of who its about."

A one-off solo video when something big and wild happens in music. Duck reads it on camera; a Short or two get cut from it.

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

When nothing clears the bar, say so. A boring video hurts more than no video.

## Format: one story, only when it's worth it (Duck, Nov 23)
Duck: "this isnt gonna be regular or a set thing... its just if theres a big wild thing in music, i throw an episode out there... they arent gonna be mixed like the OTR episodes."
- **One story per video. No schedule.** Only when something big and wild happens in music. Never a weekly roundup, never mixed with other stories.
- **Solo, up to about 5 minutes** (no Pat, so reading time is the finished length: up to about 650 words; most land at 2 to 4 minutes). Then **a Short or two** cut from it (the hook moment, with the end card).
- **Open:** the hook line cold, then "This is Fresh Tracks. I'm Duck." **Close:** a punchline, then "This was a Fresh Track, and due to unforeseen circumstances, I'll see you next time." (Duck's sign-off, Nov 23) **No That Guy and no sting**: that's for the main episodes only (Duck, Nov 23).
- **The filter:** wild or funny in the first line, and it has to genuinely go wrong or be insane. If it can't be, skip it, however big the news.
- Story shape: hook, the chain of events (one line per beat), oddly specific details, real quotes (exact, outlet named), a closing punchline. Say when it happened. No TALK cues (solo). Duck's voice, no em dashes, never Counterfeit.
- Claude doesn't run a weekly scan by default. When Duck asks "anything wild lately?", search the last week or two and bring picks under these rules.

## Where it goes
Straight into admin > Side Stories > Fresh Tracks as a **Draft** (Duck, Nov 23: "there is no news page. just put them in fresh tracks and drafts"). Each story is its own seed: `lib/seed/ft-<slug>.txt` added to `DTM_SEEDS` (title "Fresh Tracks: <short title>"), `DTM_SEED_KINDS` (`week`), `DTM_SEED_DRAFT` and `DTM_SEED_DATES` (the happened-on date), plus `lib/sources/ft-<slug>.json` and `research/ft-<slug>.notes.md`. Script body: a bracketed note (happened date, anything to be careful about), `## OPEN` with `[CLIP: ...]` and the hook line, "This is Fresh Tracks. I'm Duck.", `## THE STORY`, `## SIGN-OFF`, `[END CARD]`.
