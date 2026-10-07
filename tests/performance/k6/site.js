import http from 'k6/http';
import { check, group, sleep } from 'k6';

const BASE_URL = (__ENV.K6_BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const PROFILE = (__ENV.K6_PROFILE || 'normal').toLowerCase();
const THINK_TIME = Number(__ENV.K6_THINK_TIME || 0.2);

const PAGE_ROUTES = [
  '/', '/admin/audit-logs', '/analytics', '/attendances', '/calendar',
  '/call-tags-admin', '/catalog/attributes', '/catalog/brands',
  '/catalog/categories', '/catalog/hsn-codes', '/catalog/products',
  '/catalog/tax-rates', '/catalog/uom', '/catalog/warehouses', '/chat',
  '/complaints', '/credit-notes', '/customer-settings', '/customers',
  '/departments', '/elements', '/elements/alerts', '/elements/badges',
  '/elements/buttons', '/elements/cards', '/elements/forms', '/elements/modals',
  '/elements/tables', '/files', '/forms', '/help', '/inventory/adjustments',
  '/inventory/dashboard', '/inventory/dashboard/activities',
  '/inventory/stock-management', '/inventory/stock-transfers', '/invoices',
  '/leaves', '/messages', '/order-reasons', '/orders', '/orders/create',
  '/payments', '/procurement/goods-receipts', '/procurement/purchase-orders',
  '/procurement/suppliers', '/promotions/coupons', '/promotions/offers',
  '/promotions/referral-programs', '/refunds', '/reports', '/returns',
  '/roles-permissions', '/security', '/shipping/services', '/shipping/settings',
  '/shipping/shipments', '/targets', '/targets/create', '/targets/import',
  '/teams', '/users', '/villages',
];

const API_ROUTES = [
  '/api/dashboard', '/api/reports', '/api/orders?limit=10',
  '/api/call-tags',
  '/api/products?per_page=10', '/api/customers?per_page=10',
  '/api/warehouses?per_page=10', '/api/complaints?per_page=10',
  '/api/invoices?per_page=10', '/api/payments?per_page=10',
  '/api/returns?per_page=10', '/api/inventory/stocks?per_page=10',
  '/api/targets?per_page=10', '/api/users?per_page=10',
];

const stress = PROFILE === 'stress';
const peakPageVus = Number(__ENV.K6_PEAK_PAGE_VUS || (stress ? 40 : 10));
const peakApiVus = Number(__ENV.K6_PEAK_API_VUS || (stress ? 20 : 5));
const stages = stress
  ? [
      { duration: '30s', target: 5 },
      { duration: '30s', target: 10 },
      { duration: '30s', target: 20 },
      { duration: '1m', target: peakPageVus },
      { duration: '30s', target: 0 },
    ]
  : [
      { duration: '30s', target: 2 },
      { duration: '1m', target: peakPageVus },
      { duration: '30s', target: 0 },
    ];

export const options = {
  scenarios: {
    route_sweep: {
      executor: 'shared-iterations',
      vus: 1,
      iterations: PAGE_ROUTES.length,
      maxDuration: '5m',
      exec: 'routeSweep',
    },
    page_load: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages,
      gracefulRampDown: '10s',
      exec: 'pageLoad',
    },
    ...(__ENV.K6_CUSTOMER_ID && __ENV.K6_ORDER_ID
      ? {
          order_history_workflow: {
            executor: 'constant-vus',
            vus: 1,
            duration: '1m',
            exec: 'orderHistoryWorkflow',
          },
        }
      : {}),
    ...(!!__ENV.K6_API_TOKEN
      ? {
          api_load: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: stress
              ? [
                  { duration: '1m', target: 2 },
                  { duration: '2m', target: peakApiVus },
                  { duration: '1m', target: 0 },
                ]
              : [
                  { duration: '30s', target: peakApiVus },
                  { duration: '1m', target: peakApiVus },
                  { duration: '30s', target: 0 },
                ],
            gracefulRampDown: '10s',
            exec: 'apiLoad',
          },
        }
      : {}),
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: stress ? ['p(95)<2500', 'p(99)<5000'] : ['p(95)<1500', 'p(99)<3000'],
    checks: ['rate>0.99'],
  },
  summaryTrendStats: ['avg', 'med', 'p(90)', 'p(95)', 'p(99)', 'max'],
};

function checkPage(response, path) {
  check(response, {
    [`${path} returns HTTP 200`]: (r) => r.status === 200,
    [`${path} returns HTML`]: (r) => String(r.headers['Content-Type'] || '').includes('text/html'),
  });
}

function pageRequest(path, cookieHeader) {
  return http.get(`${BASE_URL}${path}`, {
    headers: { Cookie: cookieHeader },
    tags: { kind: 'page', path },
  });
}

function apiRequest(path, token) {
  return http.get(`${BASE_URL}${path}`, {
    headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
    tags: { kind: 'api', path: path.split('?')[0] },
  });
}

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
  const cookieHeader = Object.entries(cookies)
    .map(([name, values]) => `${name}=${values[0]}`)
    .join('; ');
  if (!cookieHeader) throw new Error('Login did not create an authenticated session cookie.');

  const dashboard = http.get(`${BASE_URL}/`, {
    headers: { Cookie: cookieHeader },
    tags: { kind: 'auth', path: '/' },
  });
  if (dashboard.status !== 200) {
    throw new Error(`Load-test account cannot open the dashboard (HTTP ${dashboard.status}).`);
  }

  return { cookieHeader, apiToken: __ENV.K6_API_TOKEN || '' };
}

export function routeSweep(data) {
  const route = PAGE_ROUTES[__ITER];
  group(`page route sweep ${route}`, () => checkPage(pageRequest(route, data.cookieHeader), route));
  sleep(THINK_TIME);
}

export function pageLoad(data) {
  const index = (__VU + __ITER) % PAGE_ROUTES.length;
  const route = PAGE_ROUTES[index];
  const response = http.get(`${BASE_URL}${route}`, {
    headers: { Cookie: data.cookieHeader },
    tags: { kind: 'page', path: route },
  });
  checkPage(response, route);
  sleep(THINK_TIME);
}

export function apiLoad(data) {
  const index = (__VU + __ITER) % API_ROUTES.length;
  const route = API_ROUTES[index];
  const response = apiRequest(route, data.apiToken);
  check(response, {
    [`${route} returns JSON HTTP 200`]: (r) => r.status === 200 && String(r.headers['Content-Type'] || '').includes('application/json'),
  });
  sleep(THINK_TIME);
}

export function orderHistoryWorkflow(data) {
  const customerId = encodeURIComponent(__ENV.K6_CUSTOMER_ID);
  const orderId = encodeURIComponent(__ENV.K6_ORDER_ID);

  group('customer order history to order details', () => {
    const customer = http.get(`${BASE_URL}/customers/${customerId}?_t=${Date.now()}`, {
      headers: {
        Cookie: data.cookieHeader,
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      tags: { kind: 'workflow', path: '/customers/{customer}' },
    });
    check(customer, {
      'customer details return JSON HTTP 200': (r) => r.status === 200 && String(r.headers['Content-Type'] || '').includes('application/json'),
    });

    const createPage = http.get(`${BASE_URL}/orders/create?customer_id=${customerId}`, {
      headers: { Cookie: data.cookieHeader },
      tags: { kind: 'workflow', path: '/orders/create' },
    });
    checkPage(createPage, '/orders/create?customer_id=…');

    const details = http.get(`${BASE_URL}/orders/${orderId}`, {
      headers: {
        Cookie: data.cookieHeader,
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      tags: { kind: 'workflow', path: '/orders/{order}' },
    });
    check(details, {
      'order details return JSON HTTP 200': (r) => r.status === 200 && String(r.headers['Content-Type'] || '').includes('application/json'),
      'order details contain an order record': (r) => {
        try { return !!JSON.parse(r.body).order; } catch (_) { return false; }
      },
    });
  });

  sleep(THINK_TIME);
}
