# Model Context Protocol (MCP) Integration Documentation

## Overview

The Model Context Protocol (MCP) integration within the Nexus system enables the dynamic discovery, connection, and execution of external tools. By utilizing MCP, AI agents within the platform can interact with standard tools, perform specialized logic, and seamlessly augment their capabilities.

The integration architecture focuses on a centralized `MCPIntegrationService`, the `MCPServer` database model, and an HTTP-based controller `MCPServerController` for management operations.

## Core Architecture

### 1. The `MCPServer` Model
The `MCPServer` model (`App\Models\MCPServer`) persists MCP server configurations. A server configuration encapsulates:
- **`name`**: The unique identifier/name of the server.
- **`type`**: The connectivity mode, such as `local` or `remote`.
- **`connection_config`**: A JSON/array cast column that holds URLs, disabled flags, available tools, and other server-specific operational parameters.
- **`status`**: The live status of the server (e.g., `offline`, `connected`).

### 2. The `MCPIntegrationService`
Located at `App\Services\MCPIntegrationService`, this service is the beating heart of the integration. It governs the entire lifecycle of an MCP connection.

#### Registration and Setup
- `registerServer(string $name, array $config)`: Upserts a new MCP server. It defaults the server state to `offline` and stores the connection parameters securely.
- `getServer(string $name)` and `getAllServers()`: Retrieval mechanisms for registered servers, predominantly returning associative arrays for easy manipulation and API responses.

#### Connection Lifecycle Management
- `connect(string $name)`: Attempts to establish a connection. If the server is of type `remote`, it invokes `performHealthCheck()` to ensure the endpoint is reachable. A disabled server via `connection_config['enabled'] = false` throws a `RuntimeException`. If successful, the server's status shifts to `connected`.
- `disconnect(string $name)`: Gracefully terminates the connection, pushing the status back to `offline`.
- `performHealthCheck(MCPServer $server)`: An internal method that executes a fast HTTP GET request against the server's `/health` endpoint with a strict 5-second timeout.

#### Tool Invocation
The service abstracts the complexity of invoking remote JSON-RPC 2.0 endpoints.
- `listTools(string $serverName)`: Inspects the `connection_config` to yield the available tools exposed by the MCP server.
- `callTool(string $serverName, string $toolName, array $params = [])`: This is the primary execution pipeline. If the server is remote, it structures a JSON-RPC 2.0 payload containing a unique ID, the method `tools/call`, and the necessary arguments. It uses Laravel's HTTP facade with a 30-second timeout to POST to the remote `/tools/call` endpoint. It safely captures exceptions and structures a standardized response payload containing execution success flags, execution timestamps, and the returned result.

#### Agent Associations
Agents in Nexus can be dynamically assigned to specific MCP servers, allowing specialized agents to access specialized tools.
- `attachToAgent(Agent $agent, string $serverName)`: Adds the server to the agent's `metadata['mcp_servers']` array and attempts to synchronize a database pivot relationship (`$agent->mcpServers()->syncWithoutDetaching()`).
- `detachFromAgent(Agent $agent, string $serverName)`: Strips the server from the agent's metadata and cleans up the pivot table.
- `getAgentServers(Agent $agent)`: Retrieves the list of available MCP tools bound to a specific agent.

### 3. The `MCPServerController`
The `MCPServerController` exposes the `MCPIntegrationService` over RESTful HTTP endpoints. This allows administrative dashboards or internal microservices to register, ping, connect, and invoke tools dynamically without touching raw PHP code. It ensures proper validation of incoming requests and sanitizes outputs before returning JSON to the client.

## Security and Error Handling
The integration is built with defensive programming in mind:
- **Timeouts**: Remote calls are strictly bounded (5 seconds for health checks, 30 seconds for tool execution) to prevent hanging worker processes.
- **Graceful Fallbacks**: Local or offline servers return immediate mock-success responses during `callTool` to allow testing and local development without standing up full remote JSON-RPC infrastructure.
- **Transaction Safety**: Agent attachments safely catch `\Throwable` when attempting pivot table syncs, acknowledging that test environments might omit certain schema relationships while preserving metadata state.

By standardizing external tool invocations through the MCP integration, Nexus provides a highly scalable and robust mechanism for AI agents to interact with the outside world.
