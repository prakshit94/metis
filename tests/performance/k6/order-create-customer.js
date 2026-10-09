import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = (__ENV.K6_BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const CUSTOMER_ID = encodeURIComponent(__ENV.K6_CUSTOMER_ID || '81349');
const PEAK_VUS = Number(__ENV.K6_PEAK_VUS || 68);
const THINK_TIME = Number(__ENV.K6_THINK_TIME || 0.2);
const ORDER_CREATE_URL = `${BASE_URL}/orders/create?customer_id=${CUSTOMER_ID}`;

export const options = {
  scenarios: {
    customer_prefilled_order_create: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '30s', target: 5 },
        { duration: '30s', target: 10 },
        { duration: '30s', target: 20 },
        { duration: '1m', target: PEAK_VUS },
        { duration: '30s', target: 0 },
      ],
      gracefulRampDown: '10s',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<2500', 'p(99)<5000'],
    checks: ['rate>0.99'],
  },
  summaryTrendStats: ['avg', 'med', 'p(90)', 'p(95)', 'p(99)', 'max'],
};

export function setup() {
  const email = __ENV.K6_EMAIL;
  const password = __ENV.K6_PASSWORD;
  if (!email || !password) {
    throw new Error('Set K6_EMAIL and K6_PASSWORD for a dedicated load-test account.');
  }

  const loginPage = http.get(`${BASE_URL}/login`, { tags: { kind: 'auth', path: '/login' } });
  if (loginPage.status !== 200) {
    throw new Error(`Cannot open ${BASE_URL}/login (HTTP ${loginPage.status}).`);
  }

  const token = String(loginPage.body).match(/name="_token"\s+value="([^"]+)"/);
  if (!token) throw new Error('Could not read the Laravel CSRF token from the login page.');

  const login = http.post(
    `${BASE_URL}/login`,
    { _token: token[1], email, password },
    { redirects: 0, tags: { kind: 'auth', path: '/login' } },
  );
  if (![302, 303].includes(login.status)) {
    throw new Error(`Load-test account login failed (HTTP ${login.status}).`);
  }

  const cookies = http.cookieJar().cookiesForURL(BASE_URL);
  const cookieHeader = Object.entries(cookies)
    .map(([name, values]) => `${name}=${values[0]}`)
    .join('; ');
  if (!cookieHeader) throw new Error('Login did not create an authenticated session cookie.');

  const probe = http.get(`${BASE_URL}/`, {
    headers: { Cookie: cookieHeader },
    tags: { kind: 'auth', path: '/' },
  });
  if (probe.status !== 200) {
    throw new Error(`Load-test account cannot open the dashboard (HTTP ${probe.status}).`);
  }

  return { cookieHeader };
}

export default function (data) {
  const response = http.get(ORDER_CREATE_URL, {
    headers: { Cookie: data.cookieHeader },
    tags: { kind: 'page', path: '/orders/create?customer_id={customer}' },
  });

  check(response, {
    'customer order-create page returns HTTP 200': (r) => r.status === 200,
    'customer order-create page returns HTML': (r) =>
      String(r.headers['Content-Type'] || '').includes('text/html'),
  });
  sleep(THINK_TIME);
}
