# OrderFlow API

OrderFlow API is a REST API for managing customer orders. It provides token-based authentication, owner-scoped order operations, controlled status transitions, status history, cached summaries, and asynchronous paid-order notifications.

The codebase demonstrates practical Laravel patterns used in production applications.

## Features

- User registration, login, and logout with Laravel Sanctum
- Authenticated order CRUD operations
- Owner-based authorization with Laravel policies
- Filtering by order status and paginated order lists
- Controlled status transitions from `new` to `paid` or `cancelled`
- Transactional status updates with pessimistic row locking
- Persistent order status history
- Per-user cached order summaries with automatic invalidation
- Event and listener flow for paid orders
- Database-backed queued notification jobs with retries and backoff
- Login rate limiting by IP address
- Structured application logs and consistent JSON `404` responses
- Composite PostgreSQL indexes for common order queries
- Automated feature tests with PHPUnit

## Architecture

The main synchronous request flow is:

```text
HTTP request -> Middleware -> Route -> Controller -> Service / Eloquent -> PostgreSQL
```

Paid-order notifications use a separate asynchronous flow:

```text
Order paid -> OrderPaid event -> QueueOrderPaidNotification listener
           -> SendOrderPaidNotification job -> Queue worker
```

Order status changes are handled by `OrderStatusService` inside a database transaction. The selected order row is locked before validation and update, preventing concurrent requests from applying conflicting transitions.

## Tech Stack

- PHP 8.3+
- Laravel 13
- PostgreSQL
- Laravel Sanctum
- Eloquent ORM
- Database cache and queue drivers
- PHPUnit 12
- Laravel Pint

## Requirements

- PHP 8.3 or newer
- Composer
- PostgreSQL
- Required PHP extensions for Laravel and PostgreSQL, including `pdo_pgsql`

## Installation

After cloning the repository, install the PHP dependencies:

```bash
cd orderflow-api
composer install
cp .env.example .env
php artisan key:generate
```

Create a PostgreSQL database and update the database section in `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=orderflow_api
DB_USERNAME=postgres
DB_PASSWORD=your_password

CACHE_STORE=database
QUEUE_CONNECTION=database
```

Run the migrations and start the API:

```bash
php artisan migrate
php artisan serve
```

Start the queue worker in a second terminal:

```bash
php artisan queue:work
```

The API is available at `http://127.0.0.1:8000/api` by default.

## Authentication

Register a user:

```bash
curl -X POST http://127.0.0.1:8000/api/register \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Sultan",
    "email": "sultan@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

Login returns a Sanctum token:

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "sultan@example.com",
    "password": "password123"
  }'
```

Send the returned token with protected requests:

```text
Authorization: Bearer <token>
```

Login is limited to five attempts per minute for each IP address.

## API Endpoints

### Public

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/health` | Check API health |
| `POST` | `/api/register` | Register a user and issue a token |
| `POST` | `/api/login` | Authenticate and issue a token |

### Authenticated

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/user` | Get the authenticated user |
| `POST` | `/api/logout` | Revoke the current token |
| `GET` | `/api/orders` | List the user's orders with pagination |
| `GET` | `/api/orders?status=paid` | Filter the user's orders by status |
| `POST` | `/api/orders` | Create an order |
| `GET` | `/api/orders/{order}` | Get an owned order |
| `PATCH` | `/api/orders/{order}` | Update an owned order amount |
| `DELETE` | `/api/orders/{order}` | Delete an owned order |
| `PATCH` | `/api/orders/{order}/pay` | Transition a new order to paid |
| `PATCH` | `/api/orders/{order}/cancel` | Transition a new order to cancelled |
| `GET` | `/api/orders/{order}/history` | Get status transition history |
| `GET` | `/api/orders/summary` | Get cached totals for the current user |

Create an order:

```bash
curl -X POST http://127.0.0.1:8000/api/orders \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{"amount": 15000}'
```

New orders always start with the `new` status. The API does not accept a status during creation. A `new` order may transition only to `paid` or `cancelled`; both are final states.

## Caching and Queues

The `/api/orders/summary` response is cached separately for each user for 60 seconds. The cache is invalidated when that user's orders are created, updated, deleted, paid, or cancelled.

When an order becomes paid, the application dispatches an `OrderPaid` event. Its listener places `SendOrderPaidNotification` on the queue. The job has retry, timeout, backoff, and failure logging behavior.

## Error Responses

API validation, authentication, authorization, and missing-resource errors are returned as JSON. Unknown API resources use a consistent response:

```json
{
  "message": "Resource not found"
}
```

## Testing

The test environment uses an in-memory SQLite database, an array cache, and the synchronous queue driver.

Run the full test suite:

```bash
php artisan test
```

Check code style:

```bash
./vendor/bin/pint --test
```
