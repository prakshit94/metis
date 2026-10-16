"""Summarize k6 samples and Linux PHP resource samples without dependencies."""
import collections
import csv
import json
import os
import pathlib
import sys

root = pathlib.Path(sys.argv[1])
lines = ['# Isolated HTTP load results', '', 'Synthetic fixtures; local PHP development server. These measurements do not establish production capacity.', '']
for profile in ('site', 'apis'):
    summary = json.loads((root / f'{profile}-summary.json').read_text())['metrics']
    failed = [name for name, metric in summary.items() if any(value is True for value in metric.get('thresholds', {}).values())]
    lines += [f'## {profile}', '', 'Threshold failures: '+(', '.join(failed) if failed else 'none')+'.', '', '| Requests | HTTP failure rate | p95 (ms) | p99 (ms) |', '| ---: | ---: | ---: | ---: |',
              f"| {summary['http_reqs']['count']} | {summary['http_req_failed']['value']:.2%} | {summary['http_req_duration']['p(95)']:.2f} | {summary['http_req_duration']['p(99)']:.2f} |", '']
    resources = list(csv.DictReader((root / f'{profile}-resources.csv').open()))
    elapsed = float(resources[-1]['elapsed_seconds']) - float(resources[0]['elapsed_seconds'])
    cpu = (int(resources[-1]['php_cpu_ticks']) - int(resources[0]['php_cpu_ticks'])) / os.sysconf('SC_CLK_TCK')
    peak_rss = max(int(r['php_rss_pages']) for r in resources) * os.sysconf('SC_PAGE_SIZE') / 1048576
    lines += [f'PHP process-group CPU: {cpu:.2f} CPU seconds over {elapsed:.2f} wall seconds ({100*cpu/elapsed:.1f}% of one core on average). Peak summed RSS: {peak_rss:.1f} MiB; shared pages may be counted more than once. Database CPU is not included.', '']
    routes = collections.defaultdict(lambda: {'durations': [], 'statuses': collections.Counter()})
    for line in (root / f'{profile}-samples.ndjson').open():
        point = json.loads(line)
        if point.get('type') != 'Point':
            continue
        metric, data = point['metric'], point['data']
        tags = data.get('tags', {})
        if metric != 'http_req_duration' or tags.get('kind') not in ('page', 'api'):
            continue
        route = tags.get('route') or tags.get('path') or tags.get('name', '')
        routes[route]['durations'].append(data['value'])
        routes[route]['statuses'][tags.get('status', 'unknown')] += 1
    lines += ['| Route | Samples | Status counts | p95 (ms) | Max (ms) |', '| --- | ---: | --- | ---: | ---: |']
    for route, values in sorted(routes.items()):
        durations = sorted(values['durations'])
        p95 = durations[int((len(durations)-1)*.95)]
        statuses = ', '.join(f'{k}: {v}' for k, v in sorted(values['statuses'].items()))
        lines += [f'| `{route}` | {len(durations)} | {statuses} | {p95:.2f} | {max(durations):.2f} |']
    lines += ['']
(root / 'report.md').write_text('\n'.join(lines))
print(root / 'report.md')
