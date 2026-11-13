# Board

Dillon, Defa and Claude talk here about Defacing The Music. Newest notes at the top of each section. Times in America/Chicago.
Dillon: edit this file right on GitHub (pencil icon), add a note under **For Claude**, commit. Claude reads it every run.

## For Claude

### DONE: Script pacing pass (Episode 1 rewrite + redo 0, 2, 3 to match)
Done 2026-11-13 by Claude (second pass, with the voice reference and the no-tribute-band rule). Episode 1 is the latest board version word for word. Episode 0 is cleaned up from the recorded transcript (32 years of guitar, the Foghat roadie, "that guy is usually awesome", ears ringing). Episodes 2 and 3: tribute-band material removed, voice loosened to match. Host bio on the home page no longer mentions the tribute band. Note: Episode 1 opens with a pop quiz answer, but the recorded Episode 0 never asked it; Dillon may want to tweak that opener. Unedited server copies upgrade on Pull latest; edited ones are left alone. Nothing more to do here.
Added 2026-11-12 11:20 PM by Defa, at Dillon's request.
Dillon reviewed the teleprompter scripts: they blaze through too fast and read like a tight summary. He wants the tone of The Dollop or How Did This Get Made: casual, conversational, room to breathe, dry and NOT cliche, corny or hokey. He records alone, so bare RIFF cues leave him improvising into silence.

1. Replace the Episode 1 script (lib/seed/episode-01.txt) with the version below, word for word. Add the sha1 of the current shipped episode-01 text to DTM_SEED_OLD so an unedited copy on the server upgrades automatically.
2. Rewrite Episodes 0, 2 and 3 to match Episode 1's pacing and style, and update their seeds the same way (old sha1s into DTM_SEED_OLD). Use Episode 1 as the template:
   - Target about 2,000 to 2,400 words each (about 15 minutes read aloud before riffs). Episode 0 can be shorter, around 1,200 to 1,500.
   - Slow walk-ups before punchlines, with [Pause.] beats. Don't jump fact to fact.
   - Context for people who weren't there (who/what/why it mattered then), tangents, and direct asides to the listener ("I need you to picture this", "I looked this up twice").
   - Every [RIFF: ...] gets a written fallback right after it that Duck can read, change or skip. Optional asides go in [FILL: ...].
   - Keep the honesty rule: anything unproven goes in "the story goes" / "the legend goes" pile. People who got hurt are never the joke; the joke is the guy who decided it was fine. "There's always a guy."
   - Order stays: COLD OPEN (teaser of that episode's story), TODAY summary, "I'm Duck, and this is Defacing the Music.", [THEME SONG, hard stop], episode title line. Episode 0's cold open stays generic, show-wide.
   - Host is Duck. No em dashes anywhere.
   - NEW RECURRING SEGMENT (Dillon, 11:33 PM): every episode names "THAT GUY", the one person to blame for it all. Plant it early ("somewhere in this story is a guy, keep an eye out"), drop suspects along the way, then a "## THAT GUY" section near the end, right before OUT: Duck lays out the suspects, then gives the verdict on who's really to blame and why, and admits it when it's a whole office of guys. Honest: if blame is disputed, say so. Ep 1: the stagehand who loaded the extra flash powder (and Keith). Ep 2: the promoters vs Fred. Ep 3: the kid with the bat vs Ozzy vs the guy who started throwing meat. Episode 0's "that guy" line should set this up as a show thing.
   - Episode 0 tease block (replace "What's coming"; no episode numbers): "Here's some of what's coming. A drummer who packed his bass drum with about ten times the flash powder he was supposed to, on live network TV, and singed his guitarist's hair off. A band that got asked to calm down a quarter million people at Woodstock '99, and played 'Break Stuff' instead. Ozzy and the bat. Plus the meeting a year earlier, with a dove, that explains a lot. The night in 1976 when Jerry Lee Lewis drove up to the gates of Graceland at three in the morning, drunk, with a loaded gun, demanding to see Elvis. Elvis was home. He did not come down. And a baseball promotion right here in Chicago, Disco Demolition Night in 1979. They blew up a crate of disco records in center field at Comiskey Park between games of a doubleheader, the crowd rushed the field, and the White Sox had to forfeit the second game."
   - VOICE REFERENCE: Duck already recorded Episode 0 (transcript below). This is how he actually talks: casual, swears naturally, self-aware, "really bad decisions that were fucking awesome", the roadie-for-Foghat guy, "that guy is usually awesome." Write every script to sound like this, minus the ums. Episode 0 is recorded, so its seed should be cleaned up to match what he said (drop the old WHO I AM / Counterfeit text; fix "deaf in one ear" to "his ears have been ringing ever since"). He's played guitar 32 years; use that, not the tribute band.
   - NEW RULE from Dillon (11:25 PM): never mention his Limp Bizkit tribute band, Counterfeit, or "my band" in any script or show copy, and never do the "nobody gets the name" joke. Remove the Counterfeit material from Episode 0's WHO I AM (leave a short [FILL: who Duck is, in his words] placeholder there for now) and drop Episode 2's CONFLICT OF INTEREST section and any "as the Counterfeit guy" / "a guy who plays it live" riffs. Limp Bizkit as a band in the Woodstock story is fine.
3. Specific fixes:
   - Woodstock '99 is a Limp Bizkit / "Break Stuff" story, not a Red Hot Chili Peppers story. Episode 0's cold open and "What's coming" must use: "A band plays 'Break Stuff' to a quarter million angry people, and they take it as instructions." Remove the RHCP "Fire" line from Episode 0.
   - Episode 2's cold open must open on Fred, the Alanis "mellow out" line and "Break Stuff." The Sunday candles/fire and RHCP "Fire" stay later as the aftermath. Note the HBO doc (Woodstock 99: Peace, Love, and Rage, 2021) came before Netflix's Trainwreck (2022). Put "Fred surfs on plywood" in the "story goes" pile unless sourced.
   - Episode 3's cold open should lead with the bat in Des Moines; the dove becomes the "and this wasn't his first time" turn.
   - Episode 0 also keeps: "They were not unforeseen. Somebody saw every one of them coming." and the out "I'm Duck. Due to unforeseen circumstances, I'll see you next week."
4. Open a PR (don't push to main). You may merge it when done; Dillon deploys with Pull latest. Leave a note here when done.

Episode 0 as recorded (voice reference, transcript):
```
A drummer blows up a kit live on TV, a band plays a song called Break Stuff to a quarter million angry people and they take it as instructions, and a man bites the head off a bat on purpose, sort of. Each one of these nights ended at least figuratively with a guy in a suit taping a sign to a door that said, "Due to unforeseen circumstances," and it shut everything down.
So that's what this podcast is about. It's about the crazy stories that, uh, happened in the music industry. So without further ado, I'm Duck, and this is Defacing the Music. Due to unforeseen circumstances
All right. So like I said, I'm Duck. Quick intro about me. I've been playing guitar for 32 years and been in the scene for pretty much all of those years. Um, heard a lot of stories. Um, I'm not an expert, but, um, I've stood on enough stages and played with enough bands to see some people make some really bad decisions that were fucking awesome.
Um, I've met enough guys that were like, "I used to be a roadie for Foghat in the '80s. Uh, this is a story that happened," and it's usually bullshit. Um, but that's fine. And, uh, yeah, there's just a lot of good stories out there with the music industry. Um, you've heard a lot of them. Um, I'm gonna talk about those ones.
Um, there's a lot you haven't heard of. I'm gonna try and talk about those, too. Um, but the biggest thing is I'm gonna try and bring in new details that you might not know and might not have heard, um, to bring a little bit something new to it. Uh, so every episode's gonna be one story, uh, roughly 15 minutes long, uh, about a real night where a band, a promoter, a label, or a crowd, um, took a bad idea all the way to the end.
Um, so it's all real. That's the point. Um, if there's anything of these stories that everybody just repeats and nobody can prove, then I'm gonna tell you, um, that it's just how the legend goes. Um, so that way, uh, you're not having to decipher, um, what's bullshit and what's not. I'm gonna do that for you. Um, when real people get hurt, I'll let you know that it was real, uh, because that's not funny It sometimes it is.
Um, and then we're gonna talk about that guy, um, because at every one of these, there's that guy, the one that's like, that guy fucked it up for everybody. Um, and that guy is usually awesome. Um, and that's, uh, kinda what makes this whole show work. Um, episode one is gonna be about, uh, 1967 when The Who were on the Smother Brothers Comedy Hour.
And, uh, long story short, explosion. Um, Pete Townsend, guitarist, deaf in one ear, uh, permanently. Uh, another one's gonna be about Limp Bizkit at Woodstock when they were like, "Hey Fred, uh, the crowd's getting kinda wild. Can you, uh, calm them down?" He's like, "No," like, uh, "Fuck that. We're gonna break stuff." Um, and people did, um, and it was really bad, but, uh, it was also kinda funny, um, especially now.
Um, we're gonna talk about Ozzy and the bat story, and a piece of that story most people don't know, um, that makes it make a little more sense. Um, also, if you've ever picked up a guitar, um, or even thought about wanting to learn guitar, then you know Smoke on the Water. Um, that is about a pretty crazy story where a fan at a Frank Zappa show decided to shoot a flare gun, and a venue burned down, and song made about it.
So, uh, that's pretty much it. Um, that's what this show's gonna be about. Um, if you have ideas, um, about stuff that you wanna hear, comment or send a message, and, uh, we'll see if we can go over it. So, uh, that's about it for episode zero. I'm Duck, and this is Defacing the Music. Due to unforeseen circumstances, this, this, this, show is ca- ca- canceled.
```

Episode 1 script (new, use word for word):
```
## COLD OPEN
September 1967. CBS Television City, Los Angeles. A Sunday night comedy show.
Tommy Smothers walks over to a British rock band with a microphone and a big friendly smile, and asks them a few easy questions. Where are you from. What's your name. Nice stuff.
About ninety seconds later the drum kit explodes, the guitar player's hair is smoking, the drummer is bleeding, and Tommy Smothers is holding an acoustic guitar that is about to stop being a guitar.
And this aired. On a Sunday night. Into living rooms. Where families were eating dinner.
## TODAY
Today: a drummer, a bass drum, way too much flash powder, a guitarist with noticeably less hair than he started the day with, and a TV host who loses his own guitar on his own show.
Also, the biggest band in this story was opening for Herman's Hermits. We'll get to that. It's important to me that we get to that.
I'm Duck, and this is Defacing the Music.
[THEME SONG, hard stop]
Episode one. Due To Excessive Flash Powder. Or, Who Let Keith Moon Near the Pyro.

## POP QUIZ ANSWER
Okay. Last episode I left you with a question, and if you remember it, thank you, you're the one.
In 1967 The Who are touring America. They are not the headliner. They are the opening act. For who?
Herman's Hermits.
[Pause. Let that sit.]
I need you to really line these two bands up next to each other, because I don't think the answer lands until you do.
The Who. "My Generation." "I hope I die before I get old." A guitar player who windmills his arm and then smashes the guitar into the amp until it's in pieces. A drummer who kicks the whole kit off the riser every night like it owes him money.
Herman's Hermits. "Mrs. Brown, You've Got a Lovely Daughter." "I'm Henery the Eighth, I Am." Peter Noone, who looked like he would help your grandma with her groceries and then ask if she needed anything else.
That's a tour. Somebody booked that. Somebody sat at a desk and went, "Yeah. Same night. Same stage. This makes sense."
[FILL: I looked this up more than once because I didn't believe it. It's real. The Who spent a chunk of summer 1967 warming up crowds for Herman's Hermits.]
So picture the crowd. You're twelve. You're in Ohio. Your mom drove you three hours in a station wagon with no air conditioning so you could see Peter Noone smile in your general direction. You have a sign. You made the sign.
And before Peter comes out, four guys from London walk on stage and murder their equipment in front of you. Feedback. Smoke. A guitar in splinters. Your mom has her hand over your eyes.
And then they leave, and a nice young man comes out and sings about Mrs. Brown's daughter like none of that just happened.
[RIFF: opening for a band the crowd didn't come for. Or use this:]
Every musician has played that show. The one where you can see the whole room looking past you at the door. But most of us didn't do it by setting our gear on fire in front of children.
Anyway. That's where The Who are in the summer of 1967. Big in England. Kind of a weird rumor in America. And in the middle of that tour, somebody books them on national television.

## WHO IS KEITH MOON
Before we get to the TV show, I have to tell you about Keith Moon. Because "drummer" doesn't cover it. "Drummer" is the job title on paper. The actual job was something closer to "weather event."
Keith Moon is the guy who walked into a five star hotel room and started thinking about how to get it down to one star. Not in a mean way. In a curious way. Like a scientist.
His favorite thing was blowing up toilets. With cherry bombs, and later with stronger stuff. And I want to be clear, that is not one of those legends I'm going to put an asterisk on. That was a documented hobby. Hotel chains knew his name the way your mailman knows which house has the dog.
[FILL: Think about the hotel manager who has to call the front office. "Hi, yes, it's the toilet again. No, a different one. No, the same guy."]
And here's my favorite Keith Moon story from this exact tour, and this one does go in the "the story goes" pile, so hold it loosely.
August 1967. Flint, Michigan. A Holiday Inn. Keith is turning twenty one, or says he is, which with Keith is about as good as you're getting.
There's a party. There's a cake. The cake does not survive. There's a cake fight, there's a fire extinguisher situation, and the legend says that by the end of the night a car ended up at the bottom of the hotel pool.
Did a car end up in the pool? Nobody's ever proven it. People have gone looking. People have argued about it for fifty years.
Did The Who get a very large bill from that Holiday Inn? Yes. That part's real.
[RIFF: the car in the pool. Is it better if it's true or better if it's not? Your call.]
So that's our guy. Twenty one, maybe. Loves explosions. Recently banned from a Holiday Inn, allegedly. And now CBS is going to put him on live television with a pyrotechnic in his bass drum.
What could possibly.

## THE SMOTHERS BROTHERS
Quick thing for anybody under fifty, because I had to look some of this up too.
The Smothers Brothers Comedy Hour was a Sunday night variety show on CBS. Tom and Dick Smothers. Brothers. Folk singers who did comedy. Tommy played the dumb one, Dick played the straight man, and the bit was mostly Tommy losing an argument with his brother. "Mom always liked you best." That was their line.
And on paper that sounds like the safest show on television. Two guys in matching sweaters doing folk songs.
But they were actually the cool show. They booked rock bands. They got in fights with CBS about politics and the war almost every week. Network censors basically had a desk with their name on it.
[FILL: So, to recap the booking: a network that's already nervous about this show lets it bring on the band that destroys its own equipment for a living. And then says, "Can you make the ending bigger?"]

## THE SETUP
The Who come on to do two songs. "I Can See for Miles," which was brand new, and is going to become their biggest American hit. And "My Generation," which is the one where they wreck everything.
And for TV, the wrecking has to be bigger. It's a stage show, but now it's a stage show on a screen the size of a toaster. So the plan is a small explosive charge in Keith's bass drum. A flash. A pop. A little puff of smoke. Great television. Everybody goes home.
Now here's where the story gets murky, and I'm going to be straight with you about it, because nobody agrees on exactly what happened.
The most common version: Keith pays off a stagehand to load extra flash powder. Some versions say the crew loaded the normal amount, and Keith added more on top when nobody was watching. One account says it ended up being around ten times what it should've been.
[FILL: Ten times. Not "a little extra." Not "a splash more." Ten times. That's not a recipe adjustment, that's a different recipe.]
And I keep thinking about the stagehand. Because in every version of this, there's a guy. There's a crew guy holding a can of flash powder, and a twenty one year old Englishman with a reputation for blowing up toilets walks up and goes, "Mate. A bit more."
And the guy says yes.
[RIFF: the stagehand. What's in his head. What does he tell his wife that night. "How was work?" "Well."]
There's always a guy. We're gonna find the guy. We're not gonna find the guy. Nobody knows his name. But he's out there. In spirit.

## THE BOOM
Okay. So. They play "I Can See for Miles," it's great, nobody's on fire yet.
Then Tommy Smothers comes out and does a little interview with them. And you can watch this, it's on YouTube, and it's honestly one of the best things on the internet. Tommy's being friendly, asking names, making small talk, and the band is not really meeting him halfway. Tommy is doing his best.
[FILL: Watch Tommy in that clip. He has no idea. He's the guy in a horror movie saying "I'll be right back."]
Then they go into "My Generation." And it's building, and building, and Pete starts smashing his guitar, and Keith kicks over the drums, and then somebody hits the charge.
And it is not a pop.
[Pause.]
It is a bomb. On a soundstage. The camera shakes. There's a wall of smoke where the drums used to be.
Keith gets knocked backward off his riser. A piece of cymbal slices his arm open. Pete Townshend is standing right in front of the bass drum when it goes, and it singes his hair. Pete comes out of the smoke looking like a cartoon. Like he lost a fight with a cigar.
[RIFF: describe Pete's face in the clip. Then tell people to go watch it after the episode.]
And here's the part that's not funny, and I'll say it once. Pete has said for years that this night is part of why his hearing has been bad ever since. He's lived with tinnitus most of his life.
To be fair to Keith, and I can't believe I'm being fair to Keith, Pete also stood in front of the loudest amplifiers on Earth for sixty years. So it's not all one night. But Pete has pointed at this one. He remembers which night it was.

## THE GUITAR
Now. My favorite part. And this is on tape, so no asterisk.
The smoke is still clearing. Pete's hair is still smoking. The drummer is bleeding. The audience doesn't know if this is the bit or a real emergency.
And Tommy Smothers walks back out. With an acoustic guitar. Smiling. Because the show must go on, and he's got a little comedy bit planned.
[Pause.]
Pete takes the guitar out of his hands. And smashes it on the floor.
[FILL: Not angry. Not a fight. Just, "Oh, I'll take that." Like a waiter clearing a plate.]
And Tommy just kind of stands there. Holding the air where his guitar was. On his own show. In front of the whole country.
[RIFF: Tommy's reaction. "I was going to play that later." Or what you'd do if a guy smashed your guitar at a gig.]
I think about that a lot. A guy built an entire career on being the nice brother who loses arguments, and then he loses his guitar to a British art student covered in soot. That's the most Tommy Smothers thing that could have possibly happened.

## THE LEGEND
One more piece of this, and this one gets the biggest asterisk of the episode.
The story goes that Bette Davis was backstage that night, waiting to go on. Bette Davis. Old Hollywood royalty. All About Eve. And when the bass drum went off, she was so shaken up that she fainted. Into the arms of Mickey Rooney.
[Pause.]
Is that true? I can't prove it. It shows up in a lot of retellings, and every time I try to nail it down it slips out of my hand.
But I'm telling it anyway, because if it's not true, somebody made up "Bette Davis faints into Mickey Rooney," and that person deserves something. An award. A plaque.
[RIFF: picture it. Smoke, a bleeding drummer, and Bette Davis going down like a folding chair into Mickey Rooney.]

## THE FALLOUT
So what happened to everybody?
Keith Moon got bandaged up and went right back to being Keith Moon for another eleven years. If anything, the explosion was great for business. It's one of the most famous clips in rock and roll.
Pete Townshend got a story he's told for over fifty years, and ears that still ring.
"I Can See for Miles" became The Who's biggest hit single in America. So, in a way, the explosion worked. Two months later nobody was asking who opened for Herman's Hermits.
[FILL: Herman's Hermits, meanwhile. Wonderful band. Lovely people. Did not explode on television. And nobody's making a podcast about them.]
And the Smothers Brothers? CBS canceled the show in 1969. Not because of The Who. They had a long fight with the network over politics and censorship, and the network finally just pulled the plug. But I'd like to think somebody at CBS had a folder, and the drum kit was in it.

## OUT
So that's it. One drummer. One bass drum. Way too much flash powder. A stagehand who said yes. And a man who walked out to do a bit and lost his guitar.
And the lesson, if there is one: when the guy who blows up hotel toilets for fun asks for a little more, the answer is no. Write it down. Put it on the wall.
[RIFF: one last thought. What it says about 1967 that this was Sunday night family viewing.]
Next time: Woodstock '99. A concrete Air Force base in July, four dollar water, and a song called "Break Stuff" played to a quarter million people who took it as instructions.
[RIFF: defacingthemusic.com, Guest List, subscribe, socials. Keep it short.]
I'm Duck. Due to unforeseen circumstances, I'll see you next week.
```

### DONE: Teleprompter: script reader in the admin
Done 2026-11-13 by Claude: admin > Scripts > Read. (Original request below.)
Added 2026-11-12 by Defa, at Dillon's request.
Dillon wants to read episode scripts from the site admin while recording, so his screen doesn't wash out his face on camera.
- Admin: add a Scripts section (create, edit, delete). Each script has an episode number, title and body. Body is plain text with simple markup: lines starting with `## ` are section headings, and `[RIFF: ...]`, `[FILL: ...]`, `[THEME SONG ...]` cues are shown highlighted.
- Reader view per script (admin only, behind the login): pure black background, white text, no other UI except tiny controls. Text scales to the window width (CSS clamp/vw), with buttons or keys to make it bigger or smaller (+/-) and optional auto-scroll with a speed control (space to start/stop). Full-screen button. Remember size and speed in localStorage.
- Cues in yellow #FFD21F, headings dimmed. No bright elements anywhere in the reader.
- Seed it with Episode 0 below. On the show Dillon goes by "Duck" (not Dillon) in all scripts and show copy.
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
I'm Duck. This is Defacing the Music.
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
I'm Duck. Due to unforeseen circumstances, I'll see you next week.
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

- 2026-11-13: Teleprompter in the admin (Scripts > Read), seeded with Episode 0 (Claude).
- 2026-11-12: DTM-SITE-1, Gig Poster home page (Claude).
