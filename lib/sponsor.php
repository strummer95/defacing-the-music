<?php
// Sponsor page / media kit (defacingthemusic.com/sponsor/) and what brands send through it.
// data/sponsor.json: the numbers and prices Duck fills in at admin > Sponsors (shown only once filled in).
// data/sponsor-inquiries.json: messages from the page's contact form.
require_once __DIR__ . '/episodes.php';

const DTM_SPONSOR_FIELDS = [
  'listens' => 'Average listens per episode (audio + video, first 30 days)',
  'youtube_subs' => 'YouTube subscribers',
  'followers' => 'Social followers (all platforms)',
  'rate_pre' => 'Pre-roll price (about 30 seconds)',
  'rate_mid' => 'Mid-roll price (about 60 seconds)',
  'rate_video' => 'YouTube video integration price',
  'rate_short' => 'Shorts / Reels / TikTok mention price',
  'email' => 'Email for sponsors (optional, shown on the page)',
];

function dtm_sponsor_cfg(): array { return dtm_json_read('sponsor.json') + array_fill_keys(array_keys(DTM_SPONSOR_FIELDS), ''); }
function dtm_sponsor_episodes_out(): int { return count(array_filter(dtm_episodes(), fn($e) => $e['status'] === 'published')); }
