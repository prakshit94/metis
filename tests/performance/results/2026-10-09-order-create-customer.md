# Customer-prefilled order creation load test — 2026-10-09

## Setup

- URL: `/orders/create?customer_id=81349` (fixture exists and is an active customer).
- k6 ramped from 5 to 10 to 20 and then 68 VUs, held the peak stage, and ramped down.
- All test traffic was authenticated GET requests. No order was submitted.
- The VUs shared one temporary test account session. The account and its test records were removed afterward; active account and session counts returned to 68 and 1.

## Results

- 818 HTTP requests; 0% HTTP failures.
- 815 completed iterations; 10 iterations were still running during graceful ramp-down.
- Every response returned HTTP 200 and HTML.
- Average response time: 5.42 s; p95: 13.12 s; p99: 13.49 s; maximum: 13.63 s.
- The p95/p99 latency targets of 2.5 s/5 s were exceeded.

During the 200-second sample, `pidstat` measured the PHP server at 50.3% average and 62% peak, and MySQL at 27.7% average and 35% peak. `pidstat` reports each process as a share of one CPU; this is not total host utilization. Host-wide CPU and the k6 generator were not sampled during this run, so these measurements cannot attribute a 99% host CPU reading.

This is a local `php artisan serve` run on a four-CPU host. It shows that this page is slow under the tested concurrency, but it is not a production capacity estimate.

The customer already had a referral code before this run, and its `updated_at` remained unchanged. The GET fallback that generates and saves a missing referral code therefore did not change this fixture during the test.
