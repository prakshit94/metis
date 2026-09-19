import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { parseHTML } from 'k6/html';

export const options = {
  stages: [
    { duration: '5s', target: 5 },  // Ramp up
    { duration: '15s', target: 5 }, // Load
    { duration: '5s', target: 0 },  // Ramp down
  ],
};

const BASE_URL = 'http://127.0.0.1:8000';

export function setup() {
  const res = http.get(`${BASE_URL}/login`);
  
  const doc = parseHTML(res.body);
  const csrfToken = doc.find('meta[name="csrf-token"]').attr('content') || 
                    doc.find('input[name="_token"]').val();
                    
  const loginRes = http.post(`${BASE_URL}/login`, {
    _token: csrfToken,
    email: 'admin@example.com',
    password: 'password',
  });
  
  let cookies = {};
  for (const name of Object.keys(loginRes.cookies)) {
    cookies[name] = loginRes.cookies[name][0].value;
  }
  
  return { cookies: cookies };
}

export default function (data) {
  const jar = http.cookieJar();
  for (const name of Object.keys(data.cookies)) {
    jar.set(BASE_URL, name, data.cookies[name]);
  }

  group('Dashboard', function () {
    let res = http.get(`${BASE_URL}/`);
    check(res, { 'status is 200': (r) => r.status === 200 });
  });

  group('Orders Index', function () {
    let res = http.get(`${BASE_URL}/orders`);
    check(res, { 'status is 200': (r) => r.status === 200 });
  });

  group('Orders Create POS', function () {
    let res = http.get(`${BASE_URL}/orders/create?customer_id=6`);
    check(res, { 'status is 200': (r) => r.status === 200 });
  });

  group('Promotions - Offers', function () {
    let res = http.get(`${BASE_URL}/promotions/offers`);
    check(res, { 'status is 200': (r) => r.status === 200 });
  });

  group('Promotions - Coupons', function () {
    let res = http.get(`${BASE_URL}/promotions/coupons`);
    check(res, { 'status is 200': (r) => r.status === 200 });
  });
  
  group('Promotions - Referrals', function () {
    let res = http.get(`${BASE_URL}/promotions/referral-programs`);
    check(res, { 'status is 200': (r) => r.status === 200 });
  });

  sleep(1);
}
