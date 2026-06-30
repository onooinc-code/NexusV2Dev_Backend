@extends('layouts.app')

@push('styles')
<style>
    .server-card {
        background: rgba(30, 41, 59, 0.5); /* slate-800/50 */
        border: 1px solid rgba(51, 65, 85, 0.5); /* slate-700/50 */
        border-radius: 12px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .server-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    .config-code {
        background: rgba(0, 0, 0, 0.3);
        padding: 8px;
        border-radius: 6px;
        font-family: monospace;
        font-size: 0.75rem;
        color: var(--nexus-text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .badge-status {
        font-size: 0.65rem;
        letter-spacing: 1px;
        padding: 4px 8px;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 text-light mb-1"><i class="fa-solid fa-network-wired text-primary me-2"></i> API & MCP Integration Hub</h2>
        <p class="text-muted small mb-0">Manage external APIs and Model Context Protocol (MCP) server connections.</p>
    </div>
    <div>
        <button class="btn btn-dark text-muted me-2" onclick="refreshServers(this)"><i class="fa-solid fa-arrows-rotate me-1"></i> Refresh</button>
        <button class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Add MCP Server</button>
    </div>
</div>

<div class="card bg-transparent border-secondary animate-fade-in">
    <div class="card-header border-secondary d-flex align-items-center">
        <i class="fa-solid fa-server text-muted me-2"></i> <h6 class="mb-0 text-light">MCP Servers</h6>
    </div>
    <div class="card-body p-4">
        <div class="row">
            <!-- Server 1: Connected -->
            <div class="col-md-4 mb-4">
                <div class="server-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="text-light mb-0" style="font-size: 1.1rem;">
                            postgres-mcp 
                            <i class="fa-solid fa-circle-check text-success ms-2" style="font-size: 0.9rem;"></i>
                        </h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm text-muted p-0 hover-text-white"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-sm text-danger p-0 hover-text-white"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>
                    
                    <div class="config-code mb-4">
                        STDIO: {"command":"npx","-y","@modelcontextprotocol/server-postgres","postgres://..."}
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-auto pt-3 border-top border-secondary border-opacity-50">
                        <span class="badge bg-success bg-opacity-10 text-success badge-status text-uppercase">CONNECTED</span>
                        <button class="btn btn-sm btn-outline-danger border-0" onclick="toggleServer(this, 'postgres-mcp')"><i class="fa-solid fa-power-off me-1"></i> Disconnect</button>
                    </div>
                </div>
            </div>

            <!-- Server 2: Disconnected -->
            <div class="col-md-4 mb-4">
                <div class="server-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="text-light mb-0" style="font-size: 1.1rem;">
                            github-mcp 
                            <i class="fa-solid fa-circle-xmark text-secondary ms-2" style="font-size: 0.9rem;"></i>
                        </h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm text-muted p-0 hover-text-white"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-sm text-danger p-0 hover-text-white"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>
                    
                    <div class="config-code mb-4">
                        STDIO: {"command":"npx","-y","@modelcontextprotocol/server-github"}
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-auto pt-3 border-top border-secondary border-opacity-50">
                        <span class="badge bg-secondary bg-opacity-10 text-secondary badge-status text-uppercase">DISCONNECTED</span>
                        <button class="btn btn-sm btn-outline-success border-0" onclick="toggleServer(this, 'github-mcp')"><i class="fa-solid fa-power-off me-1"></i> Connect</button>
                    </div>
                </div>
            </div>

            <!-- Server 3: Error -->
            <div class="col-md-4 mb-4">
                <div class="server-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="text-light mb-0" style="font-size: 1.1rem;">
                            google-drive-mcp 
                            <i class="fa-solid fa-triangle-exclamation text-danger ms-2" style="font-size: 0.9rem;"></i>
                        </h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm text-muted p-0 hover-text-white"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-sm text-danger p-0 hover-text-white"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>
                    
                    <div class="config-code mb-4">
                        SSE: {"url":"http://localhost:8080/sse"}
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-auto pt-3 border-top border-secondary border-opacity-50">
                        <span class="badge bg-danger bg-opacity-10 text-danger badge-status text-uppercase">CONNECTION REFUSED</span>
                        <button class="btn btn-sm btn-outline-success border-0" onclick="toggleServer(this, 'google-drive-mcp')"><i class="fa-solid fa-power-off me-1"></i> Retry Connect</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@stack('scripts')
<script>
    function refreshServers(btn) {
        const icon = btn.querySelector('i');
        icon.classList.add('fa-spin');
        Nexus.showTaskLoader('Probing MCP servers...');
        setTimeout(() => {
            icon.classList.remove('fa-spin');
            Nexus.hideTaskLoader();
        }, 1500);
    }

    function toggleServer(btn, name) {
        Nexus.showTaskLoader(`Toggling connection for ${name}...`);
        setTimeout(() => {
            Nexus.hideTaskLoader();
            // A page reload or DOM update would happen here
        }, 1000);
    }
</script>
