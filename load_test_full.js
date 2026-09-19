import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { parseHTML } from 'k6/html';

export const options = {
  stages: [
    { duration: '30s', target: 300 }, // Ramp up to 300 users
    { duration: '1m', target: 300 },  // Sustain heavy load
    { duration: '15s', target: 0 },   // Ramp down
  ],
};

const BASE_URL = 'http://127.0.0.1:8000';

export function setup() {
  // 1. WEB SESSION SETUP
  const res = http.get(`${BASE_URL}/login`);
  const doc = parseHTML(res.body);
  const csrfToken = doc.find('meta[name="csrf-token"]').attr('content') || 
                    doc.find('input[name="_token"]').val();
                    
  const webLoginRes = http.post(`${BASE_URL}/login`, {
    _token: csrfToken,
    email: 'admin@example.com',
    password: 'password',
  });
  
  let cookies = {};
  for (const name of Object.keys(webLoginRes.cookies)) {
    cookies[name] = webLoginRes.cookies[name][0].value;
  }

  // 2. API BEARER TOKEN SETUP
  const apiLoginRes = http.post(`${BASE_URL}/api/auth/login`, JSON.stringify({
    email: 'admin@example.com',
    password: 'password',
  }), {
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
  });

  let apiToken = '';
  if (apiLoginRes.status === 200) {
    apiToken = apiLoginRes.json('token');
  }

  return { cookies: cookies, apiToken: apiToken };
}

export default function (data) {
  // Set Web Cookies
  const jar = http.cookieJar();
  for (const name of Object.keys(data.cookies)) {
    jar.set(BASE_URL, name, data.cookies[name]);
  }

  const webEndpoints = [
    // Core & HR
    { name: 'Web Dashboard', path: '/' },
    { name: 'Web Analytics', path: '/analytics' },
    { name: 'Web Users', path: '/users' },
    { name: 'Web Departments', path: '/departments' },
    { name: 'Web Attendances', path: '/attendances' },
    
    // Catalog
    { name: 'Web Products', path: '/catalog/products' },
    { name: 'Web Categories', path: '/catalog/categories' },
    { name: 'Web Warehouses', path: '/catalog/warehouses' },
    { name: 'Web Attributes', path: '/catalog/attributes' },
    
    // Inventory & Procurement
    { name: 'Web Inventory Dash', path: '/inventory/dashboard' },
    { name: 'Web Stock Mgmt', path: '/inventory/stock-management' },
    { name: 'Web Purchase Orders', path: '/procurement/purchase-orders' },
    { name: 'Web Suppliers', path: '/procurement/suppliers' },
    
    // Orders, Returns & Financials
    { name: 'Web Orders Index', path: '/orders' },
    { name: 'Web Orders POS Create', path: '/orders/create?customer_id=6' },
    { name: 'Web Returns', path: '/returns' },
    { name: 'Web Complaints', path: '/complaints' },
    { name: 'Web Credit Notes', path: '/credit-notes' },
    { name: 'Web Refunds', path: '/refunds' },
    { name: 'Web Payments', path: '/payments' },
    { name: 'Web Invoices', path: '/invoices' },

    // Customers
    { name: 'Web Customers Index', path: '/customers' },
    { name: 'Web Customer Settings', path: '/customer-settings' },

    // Shipping
    { name: 'Web Shipments', path: '/shipping/shipments' },
    { name: 'Web Shipping Settings', path: '/shipping/settings' },

    // Promotions
    { name: 'Web Promos Offers', path: '/promotions/offers' },
    { name: 'Web Promos Coupons', path: '/promotions/coupons' },
    { name: 'Web Promos Referrals', path: '/promotions/referral-programs' },
  ];

  const apiEndpoints = [
    { name: 'API Dashboard', path: '/api/dashboard' },
    { name: 'API Reports', path: '/api/reports' },
    { name: 'API Recent Activities', path: '/api/activities/recent' },
    { name: 'API Roles', path: '/api/roles' },
    { name: 'API Permissions', path: '/api/permissions' },
    { name: 'API Teams', path: '/api/teams' },
    { name: 'API Users', path: '/api/users' },
    { name: 'API Departments', path: '/api/departments' },
    { name: 'API Security Sessions', path: '/api/security/sessions' },
    { name: 'API Org Chart', path: '/api/org-chart' },
    { name: 'API Attendances', path: '/api/attendances' },
    { name: 'API Leaves', path: '/api/leaves' },
    { name: 'API Leave Balances', path: '/api/leave-balances' },
    { name: 'API Holidays', path: '/api/holidays' },
    { name: 'API Orders', path: '/api/orders' },
    { name: 'API Complaints', path: '/api/complaints' },
    { name: 'API Credit Notes', path: '/api/credit-notes' },
    { name: 'API Invoices', path: '/api/invoices' },
    { name: 'API Payments', path: '/api/payments' },
    { name: 'API Refunds', path: '/api/refunds' },
    { name: 'API Returns', path: '/api/returns' },
  ];

  // Test Web Endpoints
  webEndpoints.forEach((ep) => {
    group(ep.name, function () {
      let res = http.get(`${BASE_URL}${ep.path}`);
      check(res, { 'status is 200': (r) => r.status === 200 });
    });
    sleep(0.1); 
  });

  // Test API Endpoints
  apiEndpoints.forEach((ep) => {
    group(ep.name, function () {
      let params = {
        headers: {
          'Authorization': `Bearer ${data.apiToken}`,
          'Accept': 'application/json'
        }
      };
      let res = http.get(`${BASE_URL}${ep.path}`, params);
      check(res, { 'status is 200 or 201': (r) => r.status === 200 || r.status === 201 });
    });
    sleep(0.1); 
  });

  sleep(1);
}
