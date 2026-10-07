import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Counter, Trend } from 'k6/metrics';
const API_ROUTE_TEMPLATES = JSON.parse(open('./api-routes.json'));

const BASE_URL = (__ENV.K6_BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const PROFILE = (__ENV.K6_PROFILE || 'normal').toLowerCase();
const THINK_TIME = Number(__ENV.K6_THINK_TIME || 0);
const DEFAULT_ID = __ENV.K6_API_DEFAULT_ID || '1';
const apiRouteDuration = new Trend('api_route_duration', true);
const apiRouteStatuses = new Counter('api_route_statuses');

const SPECIAL_PARAMS = {
  type: {
    'api/customer-settings/{type}': 'crop',
    'api/hr-settings/{type}': 'designations',
    'api/order-reasons/{type}': 'reschedule',
  },
};

function pathFor(template) {
  let path = template;
  const params = [...template.matchAll(/\{([^}]+)\}/g)].map((match) => match[1]);
  for (const name of params) {
    const special = SPECIAL_PARAMS[name]?.[template];
    const envName = `K6_API_${name.toUpperCase()}_ID`;
    const value = special || __ENV[envName] || DEFAULT_ID;
    path = path.replace(`{${name}}`, encodeURIComponent(value));
  }
  return `/${path}`;
}

const API_ROUTES = API_ROUTE_TEMPLATES.map(({ uri, action }) => ({
  template: `/${uri}`,
  path: pathFor(uri),
  action,
}));
const API_LOAD_ROUTES = API_ROUTES.filter(({ path }) =>
  !/(\/export(?:\/|$)|\/bulk-print(?:\/|$)|\/pdf(?:\/|$)|\/import-template(?:\/|$)|\/preview(?:\/|$))/.test(path),
);

const peakVus = Number(__ENV.K6_PEAK_API_VUS || (PROFILE === 'stress' ? 20 : 5));
const loadStages = PROFILE === 'stress'
  ? [
      { duration: '30s', target: 2 },
      { duration: '30s', target: 5 },
      { duration: '30s', target: 10 },
      { duration: '1m', target: peakVus },
      { duration: '30s', target: 0 },
    ]
  : [
      { duration: '30s', target: 2 },
      { duration: '1m', target: peakVus },
      { duration: '30s', target: 0 },
    ];

export const options = {
  scenarios: {
    api_route_sweep: {
      executor: 'shared-iterations',
      vus: 1,
      iterations: API_ROUTES.length,
      maxDuration: '5m',
      exec: 'routeSweep',
    },
    ...(__ENV.K6_SWEEP_ONLY === 'true' ? {} : { api_stress: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: loadStages,
      gracefulRampDown: '10s',
      exec: 'apiLoad',
    } }),
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: PROFILE === 'stress' ? ['p(95)<5000', 'p(99)<10000'] : ['p(95)<3000', 'p(99)<6000'],
    checks: ['rate>0.99'],
  },
  summaryTrendStats: ['avg', 'med', 'p(90)', 'p(95)', 'p(99)', 'max'],
};

const expectedApiStatuses = http.expectedStatuses(200, 201, 202, 204, 301, 302, 304, 400, 404, 422);

function headers(data) {
  const result = {
    Accept: 'application/json',
    Origin: BASE_URL,
    Referer: `${BASE_URL}/`,
    'X-Requested-With': 'XMLHttpRequest',
  };
  if (data.cookieHeader) result.Cookie = data.cookieHeader;
  if (data.apiToken) result.Authorization = `Bearer ${data.apiToken}`;
  return result;
}

function request(route, data) {
  return http.get(`${BASE_URL}${route.path}`, {
    headers: headers(data),
    responseCallback: expectedApiStatuses,
    tags: { kind: 'api', route: route.template },
  });
}

function checkResponse(response, route) {
  const routeTag = route.template;
  const statusTag = String(response.status);
  apiRouteDuration.add(response.timings.duration, { route: routeTag, status: statusTag });
  apiRouteStatuses.add(1, { route: routeTag, status: statusTag });
  check(response, {
    [`${route.template} did not return a server error`]: (r) => r.status < 500,
  });
}

export function setup() {
  const apiToken = __ENV.K6_API_TOKEN || '';
  let cookieHeader = '';

  if (!apiToken) {
    const email = __ENV.K6_EMAIL;
    const password = __ENV.K6_PASSWORD;
    if (!email || !password) {
      throw new Error('Set K6_EMAIL and K6_PASSWORD, or provide K6_API_TOKEN.');
    }

    const loginPage = http.get(`${BASE_URL}/login`, { tags: { kind: 'auth', path: '/login' } });
    if (loginPage.status !== 200) {
      throw new Error(`Cannot open ${BASE_URL}/login (HTTP ${loginPage.status}).`);
    }

    const tokenMatch = String(loginPage.body).match(/name="_token"\s+value="([^"]+)"/);
    if (!tokenMatch) throw new Error('Could not read the Laravel CSRF token from the login page.');

    const loginResponse = http.post(
      `${BASE_URL}/login`,
      { _token: tokenMatch[1], email, password },
      { redirects: 0, tags: { kind: 'auth', path: '/login' } },
    );
    if (![302, 303].includes(loginResponse.status)) {
      throw new Error(`Load-test account login failed (HTTP ${loginResponse.status}).`);
    }

    const cookies = http.cookieJar().cookiesForURL(BASE_URL);
    cookieHeader = Object.entries(cookies)
      .map(([name, values]) => `${name}=${values[0]}`)
      .join('; ');
    if (!cookieHeader) throw new Error('Login did not create an authenticated session cookie.');
  }

  const data = { cookieHeader, apiToken };
  const probe = http.get(`${BASE_URL}/api/dashboard`, {
    headers: headers(data),
    responseCallback: expectedApiStatuses,
    tags: { kind: 'auth', path: '/api/dashboard' },
  });
  if (probe.status !== 200) {
    throw new Error(`API authentication probe failed (HTTP ${probe.status}); no load was started.`);
  }
  return data;
}

export function routeSweep(data) {
  const route = API_ROUTES[__ITER];
  group(`API route sweep ${route.template}`, () => {
    const response = request(route, data);
    if (response.status >= 400 || response.timings.duration > 1000) {
      console.log(`API_ROUTE ${route.template} status=${response.status} duration_ms=${Math.round(response.timings.duration)}`);
    }
    checkResponse(response, route);
  });
  if (THINK_TIME > 0) sleep(THINK_TIME);
}

export function apiLoad(data) {
  const route = API_LOAD_ROUTES[(__VU + __ITER) % API_LOAD_ROUTES.length];
  checkResponse(request(route, data), route);
  if (THINK_TIME > 0) sleep(THINK_TIME);
}
