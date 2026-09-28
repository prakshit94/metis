import http from 'k6/http';
import { check, sleep } from 'k6';

// k6 run load_test.js
export const options = {
    stages: [
        { duration: '30s', target: 20 },  // Ramp up to 20 users over 30 seconds
        { duration: '1m', target: 20 },   // Stay at 20 users for 1 minute
        { duration: '30s', target: 0 },   // Ramp down to 0 users over 30 seconds
    ],
    thresholds: {
        http_req_duration: ['p(95)<500'], // 95% of requests must complete below 500ms
        http_req_failed: ['rate<0.01'],   // Error rate must be less than 1%
    },
};

const BASE_URL = 'http://localhost:8000'; // Change this to your local or staging URL

// If you need authentication, you can fetch a token here or pass it via environment variables
// Example: k6 run -e TOKEN=your_bearer_token load_test.js
const TOKEN = __ENV.TOKEN || '';

export default function () {
    const params = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...(TOKEN ? { 'Authorization': `Bearer ${TOKEN}` } : {}),
        },
    };

    // 1. Test Home/Welcome Page
    let res = http.get(`${BASE_URL}/`, params);
    check(res, {
        'home page status is 200': (r) => r.status === 200,
    });
    sleep(1);

    // 2. Test an API endpoint (e.g., fetching targets or orders)
    // Replace this with an actual endpoint from your route list
    res = http.get(`${BASE_URL}/api/targets`, params);
    check(res, {
        'targets API status is 200': (r) => r.status === 200,
        // Optional: Check if the response contains specific data
        // 'has data': (r) => JSON.parse(r.body).data !== undefined,
    });
    sleep(1);

    // 3. Example of a POST request (e.g., creating a record)
    /*
    const payload = JSON.stringify({
        name: 'Load Test Target',
        value: 100,
    });
    res = http.post(`${BASE_URL}/api/targets`, payload, params);
    check(res, {
        'target created status is 201': (r) => r.status === 201,
    });
    sleep(1);
    */
}
