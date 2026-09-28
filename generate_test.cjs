const fs = require('fs');
const routes = JSON.parse(fs.readFileSync('routes.json'));
let k6Script = `import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    vus: 5,
    duration: '30s',
};

const BASE_URL = 'http://localhost:8000';
const TOKEN = __ENV.TOKEN || '';

export default function () {
    const params = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...(TOKEN ? { 'Authorization': \`Bearer \${TOKEN}\` } : {}),
        },
    };
`;

routes.filter(r => r.method.includes('GET')).forEach(r => {
    let uri = r.uri;
    // Replace parameter {id} or {model} with 1
    uri = uri.replace(/{[^}]+}/g, '1');
    k6Script += `
    // Route: ${r.name || r.uri}
    {
        let res = http.get(\`${BASE_URL}/${uri}\`, params);
        check(res, { 'status is 200 or 4xx': (r) => r.status < 500 });
    }
`;
});

k6Script += '}\n';
fs.writeFileSync('comprehensive_load_test.js', k6Script);
console.log('Generated comprehensive_load_test.js with ' + routes.filter(r => r.method.includes('GET')).length + ' GET routes.');
