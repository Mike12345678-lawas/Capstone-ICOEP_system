import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

export const errorRate = new Rate('errors');

export const options = {
    stages: [
        { duration: '1m', target: 50 },
        { duration: '3m', target: 200 },
        { duration: '1m', target: 0 },
    ],
    thresholds: {
        http_req_duration: ['p(95)<500'],
        errors: ['rate<0.01'],
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000/api';
const FACILITY_ID = 1; 

export default function () {
    const loginPayload = JSON.stringify({
        email: `testuser_${__VU}@example.com`,
        password: 'password123',
    });
    const loginParams = { headers: { 'Content-Type': 'application/json' } };
    const loginRes = http.post(`${BASE_URL}/login`, loginPayload, loginParams);
    
    check(loginRes, { 'logged in successfully': (r) => r.status === 200 });
    if (loginRes.status !== 200) {
        errorRate.add(1);
        return;
    }
    const token = loginRes.json('token');
    const authHeaders = { 
        headers: { 
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}` 
        } 
    };

    const bookingPayload = JSON.stringify({
        facility_id: FACILITY_ID,
        title: `Load Test Event ${__VU}-${__ITER}`,
        start_time: new Date(Date.now() + 86400000).toISOString(),
        end_time: new Date(Date.now() + 90000000).toISOString(), 
    });
    const createRes = http.post(`${BASE_URL}/bookings`, bookingPayload, authHeaders);
    
    check(createRes, { 'booking created': (r) => r.status === 201 });
    if (createRes.status !== 201) {
        errorRate.add(1);
        return;
    }
    const bookingId = createRes.json('id');

    sleep(2);

    const statusRes = http.get(`${BASE_URL}/bookings/${bookingId}`, authHeaders);
    check(statusRes, { 
        'booking fetched successfully': (r) => r.status === 200,
        'solver evaluated correctly': (r) => ['approved', 'pending'].includes(r.json('status'))
    });

    sleep(1);
}
