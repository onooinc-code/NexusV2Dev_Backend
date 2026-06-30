# Nexus-servers

Windows batch scripts for managing and monitoring the Laravel development environment.

## Scripts

### start-services.bat

Orchestrates all Laravel dependencies with port cleanup and monitoring.

**Features:**
- Terminates processes on ports 8000, 8080, 6379, 3306 before starting
- Starts Redis, Laravel server, Reverb, and Horizon
- Color-coded output for status feedback
- 10-second refresh monitoring loop
- Automatic service restart on failure
- Graceful shutdown with Ctrl+C

**Usage:**
```cmd
Nexus-servers\start-services.bat
```

**Services Started:**
| Service | Port | URL |
|---------|------|-----|
| Laravel HTTP | 8000 | http://127.0.0.1:8000 |
| Horizon | - | http://127.0.0.1:8000/horizon |
| Reverb | 8080 | ws://127.0.0.1:8080 |
| Redis | 6379 | redis://127.0.0.1:6379 |

---

### stream-logs.bat

Streams live application logs with real-time HTTP request monitoring.

**Features:**
- Streams logs via `php artisan pail`
- Color-coded log levels (ERROR=red, WARNING=yellow, INFO=green, CRITICAL=highlight)
- Active HTTP connection display
- Filtering options available

**Usage:**
```cmd
Nexus-servers\stream-logs.bat
Nexus-servers\stream-logs.bat --level=error
Nexus-servers\stream-logs.bat --filter=Job
```

**Options:**
- `--level=[LEVEL]` - Filter by log level (error, warning, info, etc.)
- `--filter=[TEXT]` - Filter logs containing specific text

---

## Requirements

- PHP and Composer (in PATH)
- Redis server (optional - script will skip if not installed)
- Laravel project with Pail package (`laravel/pail`)
- Windows 10+ for ANSI color support