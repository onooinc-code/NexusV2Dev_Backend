# NexusV3 — Developer Quick Start Guide

> Get the project running locally in under 10 minutes.

---

## Prerequisites

| Tool | Minimum Version | Purpose |
|---|---|---|
| PHP | 8.3+ | Laravel runtime |
| Composer | 2.x | PHP dependency manager |
| Node.js + npm | 18+ | Frontend asset bundling (Vite) |
| MySQL or PostgreSQL | 8.0+ / 14+ | Primary database |
| Redis | 6.x+ | Queue broker + cache |

---

## Step 1: Clone & Install Dependencies

```bash
# Clone the repository
git clone <repo-url>
cd Nexus

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

---

## Step 2: Environment Configuration

```bash
# Copy the example environment file
cp .env.example .env

# Generate the application key
php artisan key:generate
```

Then edit `.env` with your specific credentials:

```env
# App
APP_NAME=Nexus
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nexus
DB_USERNAME=root
DB_PASSWORD=

# Redis (Queue + Cache + Reverb)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Queue Driver (must be redis for Horizon)
QUEUE_CONNECTION=redis

# WebSockets (Reverb)
REVERB_APP_ID=nexus
REVERB_APP_KEY=your-reverb-key
REVERB_APP_SECRET=your-reverb-secret
REVERB_HOST=localhost
REVERB_PORT=8080

# Broadcasting
BROADCAST_CONNECTION=reverb

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000

# AI Providers (add as needed)
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...
```

---

## Step 3: Database Setup

```bash
# Run all 74 migrations
php artisan migrate

# Seed the database with initial data
php artisan db:seed

# OR — run both in one command
php artisan migrate --seed
```

---

## Step 4: Build Frontend Assets

```bash
# For development (hot-reload)
npm run dev

# For production
npm run build
```

---

## Step 5: Start All Services

The application requires **4 processes** running simultaneously in development:

### Option A — All-in-one (Recommended)
```bash
composer run dev
```
This runs: `php artisan serve` + `php artisan queue:listen` + `php artisan pail` + `npm run dev`

### Option B — Manual (Separate terminals)
```bash
# Terminal 1: Web server
php artisan serve

# Terminal 2: Queue worker (Horizon)
php artisan horizon

# Terminal 3: WebSocket server (Reverb)
php artisan reverb:start

# Terminal 4: Frontend assets (Vite)
npm run dev
```

---

## Step 6: Access the Application

| URL | Purpose |
|---|---|
| `http://localhost:8000/hub/dashboard` | Main application dashboard |
| `http://localhost:8000/hub/contacts` | Contacts Hub |
| `http://localhost:8000/hub/agents` | Agents Hub |
| `http://localhost:8000/hub/hedra-soul` | Hedra Soul AI |
| `http://localhost:8000/api/v1/health` | API health check |
| `http://localhost:8000/horizon` | Horizon queue dashboard |
| `http://localhost:8000/telescope` | Telescope debug (dev only) |

---

## Running Tests

```bash
# Run all tests (compact output)
php artisan test --compact

# Run a specific test file
php artisan test tests/Feature/ContactHubTest.php --compact

# Run tests matching a filter
php artisan test --filter=ContactController --compact

# Run with code coverage (requires Xdebug)
php artisan test --coverage
```

---

## Useful Artisan Commands

```bash
# List all available commands
php artisan list

# Clear all caches
php artisan optimize:clear

# Inspect all routes
php artisan route:list --except-vendor

# Run proactive AI scheduler manually
php artisan proactive:run-scheduler

# Reset settings to factory defaults
# (via API: POST /api/v1/settings/factory-reset)
```

---

## Common Issues & Fixes

| Issue | Fix |
|---|---|
| `Vite manifest not found` | Run `npm run build` or `npm run dev` |
| Queue jobs not processing | Ensure Horizon is running: `php artisan horizon` |
| WebSocket not connecting | Ensure Reverb is running: `php artisan reverb:start` |
| Missing `APP_KEY` | Run `php artisan key:generate` |
| "Class not found" after adding model | Run `composer dump-autoload` |
| Migrations fail | Check DB credentials in `.env` |
| API returns 401 | Pass `Bearer {token}` in `Authorization` header |
