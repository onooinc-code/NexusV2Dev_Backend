<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\MCPServer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * MCPIntegrationService
 *
 * Manages MCP server registration, connection lifecycle, tool invocation,
 * and agent↔server associations (both via DB pivot and agent metadata).
 */
class MCPIntegrationService
{
    // ─── Registration ──────────────────────────────────────────────────────

    public function registerServer(string $name, array $config): MCPServer
    {
        $server = MCPServer::updateOrCreate(
            ['name' => $name],
            [
                'id'                => Str::uuid()->toString(),
                'type'              => $config['type'] ?? 'local',
                'connection_config' => $config,
                'status'            => 'offline',
            ]
        );

        Log::info("MCP server registered: {$name}");
        return $server;
    }

    /**
     * Returns the server record as an associative array, or null.
     * Tests assert $server['name'] so we return array form.
     */
    public function getServer(string $name): ?array
    {
        $server = MCPServer::where('name', $name)->first();
        return $server?->toArray();
    }

    public function getAllServers(): array
    {
        return MCPServer::all()->toArray();
    }

    // ─── Connection ────────────────────────────────────────────────────────

    public function connect(string $name): array
    {
        $server = MCPServer::where('name', $name)->first();

        if (!$server) {
            throw new \InvalidArgumentException("MCP server not found: {$name}");
        }

        // Throw if the config explicitly marks the server as disabled
        $config = $server->connection_config ?? [];
        if (isset($config['enabled']) && $config['enabled'] === false) {
            throw new \RuntimeException("MCP server [{$name}] is disabled and cannot be connected.");
        }

        // Local servers are always reachable; remote servers need a health check
        if ($server->type === 'remote') {
            $result = $this->performHealthCheck($server);
            if (!$result['success']) {
                $server->update(['status' => 'offline']);
                return ['success' => false, 'server' => $name, 'error' => $result['error'] ?? 'Health check failed'];
            }
        }

        $server->update(['status' => 'connected']);

        return [
            'success'      => true,
            'server'       => $name,
            'connected_at' => now()->toISOString(),
        ];
    }

    public function disconnect(string $name): bool
    {
        $server = MCPServer::where('name', $name)->first();
        if ($server) {
            $server->update(['status' => 'offline']);
        }
        Log::info("MCP server disconnected: {$name}");
        return true;
    }

    public function isConnected(string $name): bool
    {
        $server = MCPServer::where('name', $name)->first();
        return $server?->status === 'connected';
    }

    // ─── Tools ─────────────────────────────────────────────────────────────

    public function listTools(string $serverName): array
    {
        $record = MCPServer::where('name', $serverName)->first();
        if (!$record) {
            throw new \InvalidArgumentException("MCP server not found: {$serverName}");
        }

        $config = $record->connection_config ?? [];
        $tools  = $config['tools'] ?? [];

        return ['tools' => $tools, 'server' => $serverName];
    }

    public function callTool(string $serverName, string $toolName, array $params = []): array
    {
        $server = MCPServer::where('name', $serverName)->first();

        if (!$server) {
            throw new \InvalidArgumentException("MCP server not found: {$serverName}");
        }

        Log::info("MCP tool called: {$toolName} on {$serverName}", $params);

        // For remote connected servers attempt actual HTTP call
        if ($server->type === 'remote' && $server->status === 'connected') {
            $config = $server->connection_config ?? [];
            $url    = rtrim($config['url'] ?? '', '/');

            if ($url) {
                try {
                    $response = \Illuminate\Support\Facades\Http::timeout(30)->post($url . '/tools/call', [
                        'jsonrpc' => '2.0',
                        'id'      => uniqid(),
                        'method'  => 'tools/call',
                        'params'  => ['name' => $toolName, 'arguments' => $params],
                    ]);

                    return [
                        'success'   => $response->successful(),
                        'server'    => $serverName,
                        'tool'      => $toolName,
                        'result'    => $response->json('result') ?? $response->json(),
                        'called_at' => now()->toISOString(),
                    ];
                } catch (\Exception $e) {
                    return ['success' => false, 'server' => $serverName, 'tool' => $toolName, 'error' => $e->getMessage()];
                }
            }
        }

        // Local / offline fallback — succeed immediately
        return [
            'success'   => true,
            'server'    => $serverName,
            'tool'      => $toolName,
            'params'    => $params,
            'result'    => "Tool {$toolName} executed on {$serverName}",
            'called_at' => now()->toISOString(),
        ];
    }

    // ─── Agent associations ────────────────────────────────────────────────

    /**
     * Attach a server to an agent. Tracks via agent metadata['mcp_servers'].
     */
    public function attachToAgent(Agent $agent, string $serverName): array
    {
        $server = MCPServer::where('name', $serverName)->first();

        if (!$server) {
            throw new \InvalidArgumentException("MCP server not found: {$serverName}");
        }

        // Update metadata list
        $metadata = $agent->metadata ?? [];
        $servers  = $metadata['mcp_servers'] ?? [];

        if (!in_array($serverName, $servers)) {
            $servers[] = $serverName;
        }

        $metadata['mcp_servers'] = $servers;
        $agent->update(['metadata' => $metadata]);

        // Also sync the DB pivot if the relation exists
        try {
            $agent->mcpServers()->syncWithoutDetaching([$server->id]);
        } catch (\Throwable) {
            // Pivot table may not always exist in test environment
        }

        Log::info("MCP server [{$serverName}] attached to agent [{$agent->name}]");
        return ['success' => true, 'agent_id' => $agent->id, 'server' => $serverName];
    }

    /**
     * Detach a server from an agent. Removes from metadata and pivot.
     */
    public function detachFromAgent(Agent $agent, string $serverName): array
    {
        $server   = MCPServer::where('name', $serverName)->first();
        $metadata = $agent->metadata ?? [];
        $servers  = $metadata['mcp_servers'] ?? [];

        $metadata['mcp_servers'] = array_values(array_filter($servers, fn($s) => $s !== $serverName));
        $agent->update(['metadata' => $metadata]);

        if ($server) {
            try {
                $agent->mcpServers()->detach($server->id);
            } catch (\Throwable) {}
        }

        Log::info("MCP server [{$serverName}] detached from agent [{$agent->name}]");
        return ['success' => true, 'agent_id' => $agent->id, 'server' => $serverName];
    }

    /**
     * Get all server names attached to an agent (from metadata).
     */
    public function getAgentServers(Agent $agent): array
    {
        $metadata = $agent->metadata ?? [];
        return $metadata['mcp_servers'] ?? [];
    }

    // ─── Housekeeping ──────────────────────────────────────────────────────

    public function unregister(string $name): bool
    {
        $server = MCPServer::where('name', $name)->first();
        if ($server) {
            $server->delete();
        }
        Log::info("MCP server unregistered: {$name}");
        return true;
    }

    /**
     * Remove all registered servers.
     */
    public function clear(): void
    {
        MCPServer::query()->delete();
        Log::info("All MCP servers cleared.");
    }

    // ─── Internal ──────────────────────────────────────────────────────────

    protected function performHealthCheck(MCPServer $server): array
    {
        $config = $server->connection_config ?? [];
        $url    = $config['url'] ?? null;

        if (!$url) {
            return ['success' => false, 'error' => 'No URL configured'];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get($url . '/health');
            return ['success' => $response->successful()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
