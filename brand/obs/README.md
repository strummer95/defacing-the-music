# OBS layout pieces (Gig Poster)

Separate layers so Duck can move each one around in OBS without making a new image.
Stack them in OBS Sources, top to bottom:

1. `titles/<episode>-title.png` and `titles/<episode>-subtitle.png`: the episode title (red) and the "Who ...?" subtitle (cream), transparent. Scale and place them wherever.
2. `show-name-yellow.png` (or `show-name-cream.png`): "Defacing the Music" wordmark, transparent.
3. The logo (Duck already has it; also `assets/logo.png` in the repo).
4. `camera-frame-1280x720.png` / `camera-frame-960x540.png` (16:9), `camera-frame-square-1080.png` / `camera-frame-square-720.png` (square video), `camera-frame-vertical-608x1080.png` (9:16 phone clips), `camera-frame-4x3-960x720.png`: thick black border with the red offset shadow, see-through in the middle. Put each one directly ABOVE its camera source and size the camera to fit inside the border.
5. The cameras.
6. A background at the bottom: `bg-yellow-bands-1920x1080.png` (black bands, red stripes, yellow halftone), `bg-yellow-full-1920x1080.png`, or `bg-black-1920x1080.png`.

Fonts used: Metal Mania (show name) and Bangers (titles), same as the site.
Remake them (new episode titles) with Claude; the generator is a small PIL script.
