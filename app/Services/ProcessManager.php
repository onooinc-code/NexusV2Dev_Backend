<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

class ProcessManager
{
    private $pidFile = '../logs/pids.json';
    private $isWindows;

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Start a service
     */
    public function startService(string $service): array
    {
        try {
            $command = $this->getStartCommand($service);
            if (!$command) {
                return ['error' => "Unknown service: $service"];
            }

            Log::info("Starting service: $service", ['command' => $command]);
            
            if ($this->isWindows) {
                // Windows: Start process in background
                shell_exec($command);
            } else {
                // Linux/Mac: Use nohup
                shell_exec($command . ' > /dev/null 2>&1 &');
            }

            // Wait a bit for process to start
            sleep(1);

            $pid = $this->detectProcessPid($service);
            $this->savePid($service, $pid);

            Log::info("Service started", ['service' => $service, 'pid' => $pid]);
            
            return [
                'status' => 'success',
                'message' => "$service started successfully",
                'service' => $service,
                'pid' => $pid,
            ];
        } catch (Exception $e) {
            Log::error("Failed to start service: $service", ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Stop a service
     */
    public function stopService(string $service): array
    {
        try {
            $pid = $this->getPid($service);
            
            if (!$pid) {
                return ['error' => "Service $service not running or PID not found"];
            }

            Log::info("Stopping service", ['service' => $service, 'pid' => $pid]);

            if ($this->isWindows) {
                shell_exec("taskkill /PID $pid /T /F 2>NUL");
            } else {
                shell_exec("kill $pid 2>/dev/null");
            }

            // Wait for process to terminate
            sleep(1);

            $this->removePid($service);
            
            Log::info("Service stopped", ['service' => $service]);
            
            return [
                'status' => 'success',
                'message' => "$service stopped successfully",
                'service' => $service,
            ];
        } catch (Exception $e) {
            Log::error("Failed to stop service: $service", ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Restart a service
     */
    public function restartService(string $service): array
    {
        $stopResult = $this->stopService($service);
        
        if (isset($stopResult['error'])) {
            Log::warning("Could not stop service, attempting start anyway", ['service' => $service]);
        }

        sleep(2);
        return $this->startService($service);
    }

    /**
     * Get start command for service
     */
    private function getStartCommand(string $service): ?string
    {
        $baseDir = base_path('..');
        $logDir = "$baseDir/logs";
        
        // Ensure logs directory exists
        @mkdir($logDir, 0755, true);

        $logFile = "$logDir/{$service}.log";
        
        $commands = [
            'api' => [
                'windows' => "START /B php \"$baseDir\\Nexus-backend\\artisan\" serve --host=127.0.0.1 --port=8000 >> \"$logFile\" 2>&1",
                'unix' => "cd \"$baseDir/Nexus-backend\" && php artisan serve --host=127.0.0.1 --port=8000 >> $logFile 2>&1 &",
            ],
            'reverb' => [
                'windows' => "START /B php \"$baseDir\\Nexus-backend\\artisan\" reverb:start --host=127.0.0.1 --port=6001 >> \"$logFile\" 2>&1",
                'unix' => "cd \"$baseDir/Nexus-backend\" && php artisan reverb:start --host=127.0.0.1 --port=6001 >> $logFile 2>&1 &",
            ],
            'nextjs' => [
                'windows' => "START /B powershell -Command \"cd '$baseDir\\Nexus-Frontend' ; npm run dev\" >> \"$logFile\" 2>&1",
                'unix' => "cd \"$baseDir/Nexus-Frontend\" && npm run dev >> $logFile 2>&1 &",
            ],
            'queue' => [
                'windows' => "START /B php \"$baseDir\\Nexus-backend\\artisan\" queue:work >> \"$logFile\" 2>&1",
                'unix' => "cd \"$baseDir/Nexus-backend\" && php artisan queue:work >> $logFile 2>&1 &",
            ],
            'vite' => [
                'windows' => "START /B powershell -Command \"cd '$baseDir\\Nexus-backend' ; npm run dev\" >> \"$logFile\" 2>&1",
                'unix' => "cd \"$baseDir/Nexus-backend\" && npm run dev >> $logFile 2>&1 &",
            ],
        ];

        if (!isset($commands[$service])) {
            return null;
        }

        return $this->isWindows ? $commands[$service]['windows'] : $commands[$service]['unix'];
    }

    /**
     * Detect process PID by service
     */
    private function detectProcessPid(string $service): ?int
    {
        try {
            if ($this->isWindows) {
                if ($service === 'queue') {
                    $output = shell_exec("powershell -Command \"Get-CimInstance Win32_Process -Filter 'name = ''php.exe'' and CommandLine like ''%queue:work%''' | Select-Object -ExpandProperty ProcessId\"");
                    if ($output) {
                        $pids = array_filter(array_map('trim', explode("\n", trim($output))));
                        if (!empty($pids)) {
                            return (int) reset($pids);
                        }
                    }
                    return null;
                }

                $ports = [
                    'api' => 8000,
                    'reverb' => 6001,
                    'nextjs' => 3000,
                    'vite' => 5173,
                ];

                if (!isset($ports[$service])) {
                    return null;
                }

                $port = $ports[$service];
                $output = shell_exec("netstat -ano | findstr \":$port\"");
                
                if ($output) {
                    preg_match('/\d+\s*$/', trim(explode("\n", $output)[0]), $matches);
                    if (!empty($matches)) {
                        return (int) trim($matches[0]);
                    }
                }
            } else {
                // Unix-like systems
                if ($service === 'queue') {
                    $output = shell_exec("ps aux | grep 'queue:work' | grep -v grep | awk '{print $2}'");
                    if ($output) {
                        $pids = array_filter(array_map('trim', explode("\n", trim($output))));
                        if (!empty($pids)) {
                            return (int) reset($pids);
                        }
                    }
                    return null;
                }

                $ports = [
                    'api' => 8000,
                    'reverb' => 6001,
                    'nextjs' => 3000,
                    'vite' => 5173,
                ];

                if (!isset($ports[$service])) {
                    return null;
                }

                $port = $ports[$service];
                $output = shell_exec("lsof -i :$port 2>/dev/null | grep LISTEN");
                
                if ($output) {
                    $parts = preg_split('/\s+/', trim($output));
                    if (isset($parts[1])) {
                        return (int) $parts[1];
                    }
                }
            }
        } catch (Exception $e) {
            Log::warning("Could not detect PID for service: $service", ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Check if process is running
     */
    public function isProcessRunning(int $pid): bool
    {
        try {
            if ($this->isWindows) {
                $output = shell_exec("tasklist /FI \"PID eq $pid\" 2>NUL");
                return $output !== null && strpos($output, (string)$pid) !== false;
            } else {
                return posix_kill($pid, 0);
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get service PID from storage
     */
    private function getPid(string $service): ?int
    {
        try {
            $pids = $this->loadPids();
            return $pids[$service] ?? null;
        } catch (Exception $e) {
            Log::warning("Could not load PIDs", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Save service PID
     */
    private function savePid(string $service, ?int $pid): void
    {
        try {
            $pids = $this->loadPids();
            $pids[$service] = $pid;
            
            $pidPath = base_path('..' . DIRECTORY_SEPARATOR . 'logs');
            @mkdir($pidPath, 0755, true);
            
            file_put_contents(
                $pidPath . DIRECTORY_SEPARATOR . 'pids.json',
                json_encode($pids, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        } catch (Exception $e) {
            Log::warning("Could not save PIDs", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove service PID
     */
    private function removePid(string $service): void
    {
        try {
            $pids = $this->loadPids();
            unset($pids[$service]);
            
            $pidPath = base_path('..' . DIRECTORY_SEPARATOR . 'logs');
            @mkdir($pidPath, 0755, true);
            
            file_put_contents(
                $pidPath . DIRECTORY_SEPARATOR . 'pids.json',
                json_encode($pids, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        } catch (Exception $e) {
            Log::warning("Could not remove PID", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Load all PIDs
     */
    private function loadPids(): array
    {
        try {
            $pidFile = base_path('..' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'pids.json');
            
            if (file_exists($pidFile)) {
                $content = file_get_contents($pidFile);
                $data = json_decode($content, true);
                return is_array($data) ? $data : [];
            }
        } catch (Exception $e) {
            Log::warning("Could not load PIDs file", ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Get all service statuses
     */
    public function getServiceStatuses(): array
    {
        $services = ['api', 'reverb', 'nextjs', 'queue', 'vite'];
        $statuses = [];

        foreach ($services as $service) {
            $pid = $this->getPid($service);
            $isRunning = $pid && $this->isProcessRunning($pid);
            
            if (!$isRunning) {
                $detectedPid = $this->detectProcessPid($service);
                if ($detectedPid && $this->isProcessRunning($detectedPid)) {
                    $pid = $detectedPid;
                    $isRunning = true;
                    $this->savePid($service, $pid);
                }
            }
            
            $statuses[$service] = [
                'status' => $isRunning ? 'running' : 'stopped',
                'pid' => $isRunning ? $pid : null,
                'port' => $this->getServicePort($service),
            ];
        }

        return $statuses;
    }

    /**
     * Get service port
     */
    private function getServicePort(string $service): ?int
    {
        $ports = [
            'api' => 8000,
            'reverb' => 6001,
            'nextjs' => 3000,
            'vite' => 5173,
            'queue' => null,
        ];

        return $ports[$service] ?? null;
    }
}
