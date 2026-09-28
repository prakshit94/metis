import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    vus: 50, // 50 concurrent users
    duration: '30s',
};

const BASE_URL = 'http://localhost:8000';
const TOKEN = '1|M4kj7z5vXcQzDeU5GiV9ZftQtHvCtCMAMkSowSR86a3d5b92'; // Using the previously generated token

export default function () {
    const params = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${TOKEN}`,
        },
    };

    // 1. Test Customer Search (by phone)
    let searchRes = http.get(`${BASE_URL}/customers/search-by-phone?phone=9999999999`, params);
    check(searchRes, {
        'Customer Search: status is 200 or 4xx': (r) => r.status < 500,
    });
    sleep(0.5);

    // 2. Test Customer Create (Core Logic)
    const customerPayload = JSON.stringify({
        firstname: 'Load',
        lastname: 'Test',
        phone: '999999999' + Math.floor(Math.random() * 10), // Random phone to avoid unique constraint if any
        email: `loadtest${__VU}${__ITER}@example.com`,
    });
    let customerRes = http.post(`${BASE_URL}/customers`, customerPayload, params);
    check(customerRes, {
        'Customer Create: status is 201 or 4xx (validation)': (r) => r.status < 500,
    });
    sleep(0.5);

    // 3. Test Order Create (Core Logic)
    // We send a plausible payload. It might fail validation if products/warehouse don't exist,
    // but it will successfully test the controller's request parsing and validation performance.
    const orderPayload = JSON.stringify({
        party_id: 1, // Mock customer ID
        warehouse_id: 1,
        shipping_address_id: 1,
        billing_address_id: 1,
        payment_method: 'cod',
        items: [
            { product_id: 1, quantity: 1, unit_price: 100 }
        ]
    });
    let orderRes = http.post(`${BASE_URL}/orders`, orderPayload, params);
    check(orderRes, {
        'Order Create: status is 201 or 4xx (validation)': (r) => r.status < 500,
    });
    sleep(0.5);
}
