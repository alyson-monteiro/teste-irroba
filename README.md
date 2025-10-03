# Product Update Hub

A Laravel-based hub responsible for consuming product update jobs from an Amazon SQS queue. Incoming jobs update downstream systems (stock, price, description, images, tags) asynchronously while enforcing idempotency, monitoring, and robust error handling.

## Features
- Amazon SQS queue integration with Laravel queue workers.
- Idempotent processing via unique job enforcement and persistent tracking.
- Validation for every job payload and update type before any side-effect.
- Centralised monitoring endpoint exposing processed / failed job metrics.
- Scheduled pruning of historical job records to keep the hub lean.
- Automated tests covering job success, failure, idempotency, and service behaviour.

## Getting Started
1. Copy `.env.example` to `.env` and set the following values:
   - `APP_KEY` via `php artisan key:generate`.
   - `QUEUE_CONNECTION=sqs` plus AWS credentials (`AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`).
   - `SQS_PREFIX`, `SQS_QUEUE`, and optionally `SQS_WAIT_TIME`.
   - Downstream API credentials (`PRODUCTS_API_BASE_URL`, `PRODUCTS_API_KEY`).
   - Monitoring key (`HUB_MONITORING_API_KEY`).
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```
3. Run database migrations:
   ```bash
   php artisan migrate
   ```
4. Start the queue worker:
   ```bash
   php artisan queue:work sqs --tries=3 --backoff=60 --max-time=3600
   ```
   For long-lived processing consider `supervisor`, `systemd`, or an AWS ECS task.

## Monitoring & Operations
- Metrics endpoint: `GET /api/monitoring/metrics` (requires `X-Api-Key: <HUB_MONITORING_API_KEY>` header).
- Logs: structured queue logs (success, failure) emitted via Laravel's logging system.
- Pruning: `php artisan hub:prune-processed-jobs --days=30` (scheduled daily at 01:00).

## Testing
Run the automated test suite:
```bash
php artisan test
```
Tests cover:
- Successful processing and downstream API calls.
- Idempotency guarantees and duplicate suppression.
- Failure handling with stored diagnostics.
- Monitoring endpoint authentication and payload.
- Service-level configuration validation.

## Deployment Notes
- Ensure the queue worker runs continuously (Supervisor, Horizon, ECS, etc.).
- Configure HTTPS endpoints for the downstream product API.
- Rotate the monitoring API key regularly and distribute securely.
- Enable database backups for the `processed_jobs` table for auditability, retaining only what compliance requires.
