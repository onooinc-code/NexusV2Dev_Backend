# NexusV3

> **A Laravel 13 AI-first platform for contact management, workflow automation, and cognitive AI orchestration.**

---

## 🚀 Quick Start

```bash
# Install dependencies
composer install && npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate --seed

# Start all services (recommended)
composer run dev
```

Then open: [http://localhost:8000/hub/dashboard](http://localhost:8000/hub/dashboard)

---

## 📦 Tech Stack

| | Technology |
|---|---|
| **Backend** | Laravel 13, PHP 8.3+ |
| **Frontend** | Blade Templates, Vite, Vanilla CSS |
| **Database** | MySQL / PostgreSQL |
| **Queue** | Redis + Laravel Horizon |
| **WebSockets** | Laravel Reverb |
| **Auth** | Laravel Sanctum |
| **Dev Tools** | Telescope, Clockwork, Debugbar, Pail |
| **Tests** | PHPUnit 11 |

---

## 🧩 Hub Architecture

Nexus is organized into **Hubs** — self-contained feature domains:

| Hub | Description | UI Route |
|---|---|---|
| **Dashboard** | System overview, health, activity feed | `/hub/dashboard` |
| **Contacts** | CRM engine with AI intelligence & memory | `/hub/contacts` |
| **AI Models** | Dynamic multi-provider AI router | `/hub/models` |
| **Agents** | Multi-agent orchestration framework | `/hub/agents` |
| **Workflows** | Automation workflow engine | `/hub/workflows` |
| **Tasks** | Heterogeneous task manager (Manual/Agent/System) | `/hub/tasks` |
| **Hedra Soul** | AI cognitive core "Souly" — 50+ endpoints | `/hub/hedra-soul` |
| **People Connect** | Real-time messaging (WhatsApp via WAHA) | `/hub/people-connect` |
| **Memory** | Versioned confidence-scored memory system | `/hub/memory` |
| **Proactive AI** | NLP-based ECA automation rules | `/hub/proactive-ai` |
| **Scheduler** | Cron job management | `/hub/scheduler` |
| **Logs** | Structured audit logging console | `/hub/logs` |
| **Settings** | Full system configuration | `/hub/settings` |
| **Admin** | DLQ management, admin tools | `/hub/admin` |
| **WAHA** | WhatsApp sync & management | `/hub/waha` |

---

## 🔑 Key Services

```bash
# Web Server
php artisan serve                    # Port 8000

# Queue Worker (required for AI tasks)
php artisan horizon                  # Queue dashboard at /horizon

# WebSocket Server (required for real-time features)
php artisan reverb:start             # Port 8080

# Frontend Assets
npm run dev                          # Vite HMR
```

---

## 🧪 Testing

```bash
# Run all tests
php artisan test --compact

# Run specific test file
php artisan test tests/Feature/ContactControllerTest.php --compact

# Run with filter
php artisan test --filter=ContactHub --compact
```

---

## 📚 Documentation

All documentation is in the [`Documentation/`](Documentation/) folder:

- [`Main-Files/PROJECT_OVERVIEW.md`](Documentation/Main-Files/PROJECT_OVERVIEW.md) — Full project overview
- [`Main-Files/SYSTEM_ARCHITECTURE.md`](Documentation/Main-Files/SYSTEM_ARCHITECTURE.md) — System architecture with diagrams
- [`Main-Files/API_DESIGN.md`](Documentation/Main-Files/API_DESIGN.md) — Full API reference
- [`Main-Files/DEVELOPER_QUICK_START.md`](Documentation/Main-Files/DEVELOPER_QUICK_START.md) — Setup guide
- [`Main-Files/COMPREHENSIVE_FEATURES_LIST.md`](Documentation/Main-Files/COMPREHENSIVE_FEATURES_LIST.md) — All features
- [`Main-Files/Data_Models.md`](Documentation/Main-Files/Data_Models.md) — All Eloquent models and ERD
- [`Main-Files/THIRD_PARTY_INTEGRATIONS.md`](Documentation/Main-Files/THIRD_PARTY_INTEGRATIONS.md) — Integrations guide
- [`Main-Files/Bugs_Missing_Features.md`](Documentation/Main-Files/Bugs_Missing_Features.md) — Known bugs and gaps
- [`Main-Files/DESIGN_PLAN_AND_ROADMAP.md`](Documentation/Main-Files/DESIGN_PLAN_AND_ROADMAP.md) — Roadmap
- [`The-Hubs/`](Documentation/The-Hubs/) — Per-hub deep documentation

---

## 🔐 Environment Variables

See [`.env.example`](.env.example) for all required variables. Key ones:

```env
APP_KEY=                    # Auto-generated via php artisan key:generate
DB_CONNECTION=mysql
REDIS_HOST=127.0.0.1
QUEUE_CONNECTION=redis
BROADCAST_CONNECTION=reverb
REVERB_APP_KEY=...
OPENAI_API_KEY=sk-...       # Optional — configure via UI after setup
```

---

## 🏗️ Architecture Principles

1. **Hub-based Domain Architecture** — Each feature domain is self-contained
2. **Thin Controllers** — Business logic lives in Services, not Controllers
3. **Async by Default** — All AI and heavy tasks run via Laravel Queue
4. **Observable Everything** — Dual-write logging (file + MySQL)
5. **No Lock-in** — Any AI provider works via the dynamic provider registry

---

## 📄 License

MIT License
