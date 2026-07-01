# NexusV3 — Data Seeds

> A guide to all seed data and the Seed Runner system.

---

## 1. Seed Runner System

Nexus uses a **modular Seed Runner** controlled by `SeedRunnerService`. Rather than running `php artisan db:seed` and getting everything, operators can selectively run specific seeders from:
- The Settings Hub admin panel (UI)
- `POST /api/v1/settings/seeds/{id}/run` (API)
- `POST /api/v1/settings/seeds/run-multiple` (bulk API)

All seed runs are logged and tracked.

---

## 2. Core Seeders

### `DatabaseSeeder`
The master seeder that calls all sub-seeders in the correct dependency order.
```
php artisan db:seed
```

### `UserSeeder`
Creates the default admin user:
```json
{
  "name": "Hedra Admin",
  "email": "admin@nexus.local",
  "password": "password",
  "is_admin": true
}
```
> ⚠️ Change this password immediately in production!

### `SettingsSeeder`
Populates all default settings records with factory values. This is the **most important seeder** — without it, the Settings Hub will have no baseline configuration.

Categories seeded:
- `ai_providers` — Default provider slugs, routing defaults
- `system` — App name, timezone, default language
- `features` — Feature flags (all off by default)
- `notifications` — Email driver defaults
- `ui` — Theme, sidebar state

### `AIProviderSeeder`
Creates placeholder AI provider records:
- OpenAI (inactive by default — requires API key)
- Anthropic (inactive by default)
- Local Ollama (active if local endpoint is configured)

### `AgentPersonaSeeder`
Creates the default AI personas:
- **Souly** — The Hedra Soul persona (system prompt for the Hedra AI)
- **Analyst** — Data-focused, terse, analytical tone
- **Assistant** — Helpful, friendly, general-purpose

### `AgentSeeder`
Creates the core system agents (`is_system = true`):
- **Contact Analysis Agent** (`contact-analyzer`)
- **Memory Updater Agent** (`memory-updater`)
- **Workflow Orchestrator Agent** (`workflow-orchestrator`)

### `SoulyInstructionVersionSeeder`
Seeds the first instruction version for the Souly AI. This becomes the baseline `instruction_version_id` for all new Hedra Soul sessions.

---

## 3. Development-Only Seeders

These should NOT be run in production:

### `ContactFactorySeeder`
Creates 50 fake contacts with realistic names, phone numbers, and email addresses using Faker. Includes a few messages per contact.

### `WorkflowTemplateSeeder`
Seeds demo workflow templates:
- "Welcome New Contact" workflow
- "Daily Summary Report" workflow
- "Escalation Alert" workflow

### `ProactiveRuleSeeder`
Creates sample ECA rules for demonstration:
- "Remind me every Monday to review pending contacts"
- "Alert if CPU usage exceeds 90%"

---

## 4. Running Specific Seeders

### Via Artisan
```bash
php artisan db:seed --class=SettingsSeeder
php artisan db:seed --class=AgentSeeder
```

### Via API (requires admin auth)
```http
POST /api/v1/settings/seeds/settings-seeder/run
Authorization: Bearer {admin_token}
```

### Via UI
Settings Hub → Admin → Seed Manager → Run

---

## 5. Seeder Order Dependencies

```
DatabaseSeeder
├── UserSeeder           (no dependencies)
├── SettingsSeeder       (no dependencies)
├── AIProviderSeeder     (requires SettingsSeeder)
├── AgentPersonaSeeder   (no dependencies)
├── AgentSeeder          (requires AgentPersonaSeeder)
│   └── SoulyInstructionVersionSeeder
└── (dev only)
    ├── ContactFactorySeeder
    ├── WorkflowTemplateSeeder
    └── ProactiveRuleSeeder
```
