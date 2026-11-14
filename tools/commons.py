#!/usr/bin/env python3
# Dev tool for Claude (not used by the site). Run: python3 -I tools/commons.py search "query" / add <seed> "File:..." "Title" slug
"""Wikimedia Commons helper for DTM cutaways.
  search "<query>" [n]                 list File: pages with license, size
  add <key> "File:Name.jpg" "Short title" [slug]
      checks license (CC BY / BY-SA / CC0 / PD only) and appends the credit plus a 1920px
      thumbnail URL to lib/cutaways/<key>.json (the site server downloads and caches the picture)
"""
import sys, json, re, os, io, html, urllib.parse, urllib.request
REPO = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
UA = {'User-Agent': 'DTM-cutaways/1.0 (podcast research)'}
API = 'https://commons.wikimedia.org/w/api.php?'

def get(url):
    import time, urllib.error
    for i in range(6):
        try:
            return urllib.request.urlopen(urllib.request.Request(url, headers=UA), timeout=60).read()
        except urllib.error.HTTPError as e:
            if e.code != 429 or i == 5: raise
            time.sleep(int(e.headers.get('retry-after') or 0) or 3 * (i + 1))

def api(**p):
    p['format'] = 'json'
    return json.loads(get(API + urllib.parse.urlencode(p)))

def fetch_file(url):
    import subprocess
    r = subprocess.run(['curl', '-sSfL', '--retry', '5', '--retry-delay', '4', '--max-time', '180', '-A', UA['User-Agent'], url], capture_output=True)
    if r.returncode != 0: sys.exit('download failed: ' + r.stderr.decode()[:200])
    return r.stdout

def strip(s):
    s = re.sub(r'<[^>]+>', '', s or '')
    return re.sub(r'\s+', ' ', html.unescape(s)).strip()

def ok_license(short):
    s = (short or '').strip()
    u = s.upper()
    if not s or 'NC' in re.split(r'[\s-]+', u) or 'ND' in re.split(r'[\s-]+', u) or 'FAIR' in u or 'NON-FREE' in u:
        return False
    return bool(re.match(r'^(CC[ -]BY|CC0|CC ZERO|PUBLIC DOMAIN|PD\b|PD-|NO RESTRICTIONS|ATTRIBUTION)', u))

def info(titles):
    r = api(action='query', titles='|'.join(titles), prop='imageinfo', iiprop='url|size|mime|extmetadata', iiurlwidth=1920)
    return list(r.get('query', {}).get('pages', {}).values())

def search(q, n=15):
    r = api(action='query', list='search', srsearch=q + ' filetype:bitmap', srnamespace=6, srlimit=n)
    titles = [x['title'] for x in r['query']['search']]
    if not titles: print('no results'); return
    for p in info(titles):
        ii = (p.get('imageinfo') or [{}])[0]; m = ii.get('extmetadata', {})
        lic = m.get('LicenseShortName', {}).get('value', '')
        flag = 'OK ' if ok_license(lic) and m.get('NonFree', {}).get('value', '') != 'true' else 'NO '
        print(f"{flag}{p['title']} | {lic} | {ii.get('width')}x{ii.get('height')} | {strip(m.get('ImageDescription',{}).get('value',''))[:110]}")

def slugify(s):
    return re.sub(r'[^a-z0-9]+', '-', s.lower()).strip('-')[:40] or 'image'

def add(key, title, short, slug=None):
    if not title.startswith('File:'): title = 'File:' + title
    p = info([title])[0]
    if 'missing' in p: sys.exit('missing file ' + title)
    ii = p['imageinfo'][0]; m = ii['extmetadata']
    lic = m.get('LicenseShortName', {}).get('value', '')
    if not ok_license(lic) or m.get('NonFree', {}).get('value', '') == 'true':
        sys.exit(f'REJECTED license: {lic!r} for {title}')
    if not ii.get('mime', '').startswith('image/'): sys.exit('not an image: ' + ii.get('mime', ''))
    restr = m.get('Restrictions', {}).get('value', '')
    author = strip(m.get('Artist', {}).get('value', '')) or strip(m.get('Credit', {}).get('value', '')) or 'Unknown author'
    attr_req = m.get('AttributionRequired', {}).get('value', '')
    mf = f'{REPO}/lib/cutaways/{key}.json'; os.makedirs(os.path.dirname(mf), exist_ok=True)
    man = json.load(open(mf)) if os.path.exists(mf) else {'images': []}
    if any(x['source'] == ii['descriptionurl'] for x in man['images']): sys.exit('already added')
    if len(man['images']) >= 10: sys.exit('already 10 images for ' + key)
    thumb = (ii.get('thumburl') or ii['url']).split('?')[0]
    ext = os.path.splitext(urllib.parse.urlparse(thumb).path)[1].lower().lstrip('.')
    if ext not in ('jpg', 'jpeg', 'png', 'gif', 'webp'): ext = 'jpg'
    fn = f"{len(man['images'])+1:02d}-{slug or slugify(short)}.{ext}"
    entry = {'file': fn, 'title': short, 'commons_title': title[5:], 'source': ii['descriptionurl'],
             'author': author[:200], 'license': lic, 'license_url': m.get('LicenseUrl', {}).get('value', ''),
             'fetch': thumb, 'width': ii.get('thumbwidth') or ii.get('width'), 'height': ii.get('thumbheight') or ii.get('height')}
    if restr: entry['restrictions'] = restr
    if attr_req: entry['attribution_required'] = attr_req
    man['images'].append(entry)
    json.dump(man, open(mf, 'w'), indent=1, ensure_ascii=False); open(mf, 'a').write('\n')
    print(f"ADDED {key}/{fn} | {lic} | {author[:60]} | {entry['width']}x{entry['height']}" + (f' | restrictions: {restr}' if restr else ''))

if __name__ == '__main__':
    a = sys.argv[1:]
    if a and a[0] == 'search': search(a[1], int(a[2]) if len(a) > 2 else 15)
    elif a and a[0] == 'add': add(*a[1:5])
    else: print(__doc__)
