import json

with open('routes.json') as f:
    routes = json.load(f)

k6_script = """import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    vus: 255,
    duration: '30s',
};

const BASE_URL = 'http://localhost:8000';
const TOKEN = __ENV.TOKEN || '';

export default function () {
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    };
    if (TOKEN) {
        headers['Authorization'] = `Bearer ${TOKEN}`;
    }
    const params = { headers: headers };
"""

get_routes = [r for r in routes if 'GET' in r.get('method', '')]

for r in get_routes:
    uri = r['uri']
    # basic replacing of param blocks with '1'
    import re
    uri = re.sub(r'\{[^}]+\}', '1', uri)
    name = r.get('name') or uri
    k6_script += f"""
    // Route: {name}
    {{
        let res = http.get(`${{BASE_URL}}/{uri}`, params);
        check(res, {{ 'status is 200 or 4xx for {name}': (r) => r.status < 500 }});
    }}
"""

k6_script += "}\n"

with open('comprehensive_load_test.js', 'w') as f:
    f.write(k6_script)

print(f"Generated comprehensive_load_test.js with {len(get_routes)} GET routes.")
