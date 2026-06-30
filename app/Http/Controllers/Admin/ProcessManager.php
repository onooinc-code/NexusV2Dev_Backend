<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ProcessManager
{
    protected $logsPath;
    protected $backendPath;
    protected $frontendPath;

    public function __construct(string $logsPath)
    {
        $this->logsPath = $logsPath;
        $this->backendPath = base_path();
        $this->frontendPath = base_path('../Nexus-Frontend');
    }

    /**
     * Start a service
     */
    public function startService(string $service): array
    {
        $service = strtolower($service);

        try {
            match ($service) {
                'api' => $this->startApi(),
                'reverb' => $this->startReverb(),
                'vite' => $this->startVite(),
                'queue' => $this->startQueue(),
                'nextjs' => $this->startNextJs(),
                default => throw new \Exception("Unknown service: $service"),
            };

            Log::info("Service started: $service");
            return [
                'status' => 'started',
                'service' => $service,
                'timestamp' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            Log::error("Failed to start $service: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Stop a service
     */
    public function stopService(string $service): array
    {
        $service = strtolower($service);

        try {
            // Get PID from file
            $pidFile = $this->logsPath . '/pids.txt';
            $pid = $this->getPidForService($pidFile, $service);

            if ($pid) {
                $this->killProcess($pid);
                Log::info("Service stopped: $service (PID: $pid)");
            }

            return [
                'status' => 'stopped',
                'service' => $service,
                'pid' => $pid,
                'timestamp' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            Log::error("Failed to stop $service: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Start API server
     */
    private function startApi(): void
    {
        $cmd = $this->buildCommand([
            'cd ' . $this->backendPath,
            'php artisan serve --port=8000',
        ]);

        $logFile = $this->logsPath . '/api.log';
        $this->executeCommand($cmd, $logFile, 'API');
    }

    /**
     * Start Reverb WebSocket server
     */
    private function startReverb(): void
    {
        $cmd = $this->buildCommand([
            'cd ' . $this->backendPath,
            'php artisan reverb:start --host=0.0.0.0 --port=6001',
        ]);

        $logFile = $this->logsPath . '/reverb.log';
        $this->executeCommand($cmd, $logFile, 'Reverb');
    }

    /**
     * Start Vite dev server
     */
    private function startVite(): void
    {
        $cmd = $this->buildCommand([
            'cd ' . $this->backendPath,
            'npm run dev -- --port 5173',
        ]);

        $logFile = $this->logsPath . '/vite.log';
        $this->executeCommand($cmd, $logFile, 'Vite');
    }

    /**
     * Start Queue worker
     */
    private function startQueue(): void
    {
        $cmd = $this->buildCommand([
            'cd ' . $this->backendPath,
            'php artisan queue:work --tries=3 --timeout=90',
        ]);

        $logFile = $this->logsPath . '/queue.log';
        $this->executeCommand($cmd, $logFile, 'Queue');
    }

    /**
     * Start Next.js frontend
     */
    private function startNextJs(): void
    {
        $cmd = $this->buildCommand([
            'cd ' . $this->frontendPath,
            'npm run dev -- --port 3000',
        ]);

        $logFile = $this->logsPath . '/nextjs.log';
        $this->executeCommand($cmd, $logFile, 'Next.js');
    }

    /**
     * Build command for the OS
     */
    private function buildCommand(array $commands): string
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return implode(' && ', $commands);
        } else {
            return implode(' && ', $commands);
        }
    }

    /**
     * Execute a command asynchronously
     */
    private function executeCommand(string $cmd, string $logFile, string $service): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = "start /B cmd /C \"$cmd > \"$logFile\" 2>&1\"";
            shell_exec($cmd);
        } else {
            $cmd = "nohup sh -c '$cmd' > $logFile 2>&1 &";
            shell_exec($cmd);
        }
    }

    /**
     * Kill a process
     */
    private function killProcess(int $pid): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            shell_exec("taskkill /PID $pid /F 2>NUL");
        } else {
            posix_kill($pid, 9);
        }
    }

    /**
     * Get PID for a service from the PID file
     */
    private function getPidForService(string $pidFile, string $service): ?int
    {
        if (!File::exists($pidFile)) {
            return null;
        }

        $content = File::get($pidFile);
        foreach (explode("\n", $content) as $line) {
            if (empty(trim($line))) continue;
            [$svc, $pid] = array_map('trim', explode(':', $line));
            if (strtolower($svc) === $service) {
                return (int) $pid;
            }
        }

        return null;
    }
}
