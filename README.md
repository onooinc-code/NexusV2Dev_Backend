# NexusV2 Backend

This is the Laravel/PHP backend repository for the NexusV2 project. It serves as the core API engine, handling AI integrations, background jobs, webhooks, and complex workflow orchestrations.

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- MySQL/PostgreSQL
- Redis (for Queues and Caching)

### Installation
```bash
composer install
```

### Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```
Configure your database credentials and Redis connection in `.env`.

### Database & Migrations
```bash
php artisan migrate --seed
```

### Running the Services
To fully run the backend locally, you need three services running simultaneously:
1. **API Server**: `php artisan serve`
2. **Queue Worker**: `php artisan horizon` (or `php artisan queue:work`)
3. **WebSockets (Reverb)**: `php artisan reverb:start`

## 🏗️ Architecture
- **Framework**: Laravel 11
- **Design Pattern**: Domain-Driven Design (Hubs) - AgentsHub, ContactHub, AiModelsHub, etc.
- **Async Engine**: Heavy reliance on Queues and Background Jobs for AI tasks and API rate-limiting.

## 🤖 AI Development Guidelines (Antigravity/Cursor)
When asking AI to modify this repository:
1. Always reference the backend architecture documents and the specific Hub requirements.
2. Write PHPUnit tests before implementing complex logic (TDD approach).
3. Ensure no direct cross-hub database writes occur; always use Service contracts.
