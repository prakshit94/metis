"""Exercise built shared CSS in headless Chrome; no application database required."""
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile

root = Path(__file__).resolve().parents[2]
manifest = json.loads((root / 'public/build/manifest.json').read_text())
css = (root / 'public/build' / manifest['resources/scss/main.scss']['file']).as_uri()
chrome = shutil.which('google-chrome') or shutil.which('chromium')
if not chrome:
    raise SystemExit('Chrome/Chromium required for responsive CSS checks')
with tempfile.TemporaryDirectory(prefix='metis-responsive-') as tmp:
    for width in (320, 375, 768, 1280):
        for theme in ('light', 'dark'):
            html = f'''<!doctype html><html data-bs-theme="{theme}"><head>
<meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="{css}"></head>
<body class="admin-layout"><aside class="admin-sidebar"></aside><main class="admin-main"><div class="container-fluid">
<input class="form-control"><div class="table-responsive"><table class="table" style="min-width:1600px"><tr><td>Orders</td></tr></table></div>
</div></main><script>window.addEventListener('load',()=>{{
const main=document.querySelector('main'), table=document.querySelector('.table-responsive');
const issues=[];const w=window.innerWidth;
if(w<992 && getComputedStyle(main).marginLeft!=='0px') issues.push('sidebar margin');
if(w<768 && parseFloat(getComputedStyle(document.querySelector('input')).fontSize)<16) issues.push('input zoom');
if(document.documentElement.scrollWidth>w) issues.push('page overflow');
if(table.clientWidth>w || table.scrollWidth<=table.clientWidth) issues.push('table scrolling');
if(w<768 && document.querySelector('aside').getBoundingClientRect().width>w-40) issues.push('sidebar width');
parent.document.body.append(Object.assign(parent.document.createElement('output'),{{textContent:issues.length?'FAIL '+issues.join(','):'PASS viewport='+w+' theme={theme}'}}));
}});</script></body></html>'''
            fixture = Path(tmp) / 'fixture.html'
            (Path(tmp) / 'frame.html').write_text(html)
            fixture.write_text(f'<html><body><iframe src="frame.html" style="border:0;width:{width}px;height:900px"></iframe></body></html>')
            run = subprocess.run([chrome, '--headless', '--no-sandbox', '--disable-gpu', '--disable-dev-shm-usage', '--no-proxy-server', '--allow-file-access-from-files', f'--user-data-dir={tmp}/profile-{width}-{theme}', '--window-size=1500,1000', '--virtual-time-budget=3000', '--dump-dom', fixture.as_uri()], capture_output=True, text=True, timeout=30)
            output = run.stdout.split('<output>')[-1].split('</output>')[0]
            if run.returncode or not output.startswith('PASS'):
                raise SystemExit(f'CSS check failed at {width}/{theme}: {output[-400:]}\n{run.stderr[-500:]}')
            print(output, flush=True)
