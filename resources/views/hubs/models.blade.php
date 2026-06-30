@extends('layouts.app')

@section('content')
<div class="row mb-4 animate-fade-in stagger-1">
    <div class="col-md-6">
        <h2 class="fw-bold mb-0">AIModelsHub</h2>
        <p class="text-muted">AI Provider & Model Management.</p>
    </div>
</div>

<div class="row g-4 animate-fade-in stagger-2">
    <!-- Provider Configurator -->
    <div class="col-md-6">
        <div class="card hover-3d h-100 border-0 shadow-sm" style="background: rgba(22, 27, 34, 0.5) !important; backdrop-filter: blur(10px);">
            <div class="card-header bg-transparent border-secondary d-flex justify-content-between align-items-center pt-3 pb-2">
                <h5 class="mb-0 fw-bold text-light"><i class="fa-solid fa-server text-primary me-2"></i> Integrated Providers</h5>
                <button class="btn btn-sm btn-primary"><i class="fa-solid fa-plus"></i></button>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush mb-4" id="provider-list">
                    @forelse($providers as $provider)
                    <div class="list-group-item bg-dark border border-secondary rounded mb-3 p-3 shadow-sm" style="background-color: #0d1117 !important;" data-id="{{ $provider->id }}">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="fw-bold text-light fs-5"><i class="fa-brands fa-hubspot me-2 text-primary"></i> {{ $provider->name }} API</div>
                            <div class="form-check form-switch fs-5">
                                <input class="form-check-input" type="checkbox" role="switch" {{ $provider->is_active ? 'checked' : '' }}>
                            </div>
                        </div>
                        <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text bg-dark text-muted border-secondary"><i class="fa-solid fa-link"></i></span>
                            <input type="text" class="form-control bg-dark text-light border-secondary" value="{{ $provider->api_base_url ?? 'N/A' }}">
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1 small text-muted">
                                <span>Health Status</span>
                                <span class="text-success fw-bold">98% Uptime</span>
                            </div>
                            <div class="progress" style="height: 6px; background-color: var(--nexus-border);">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 98%;"></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary">Latency: 120ms</span>
                            <button class="btn btn-sm btn-outline-success btn-ping" data-provider="{{ $provider->name }}"><i class="fa-solid fa-satellite-dish me-1"></i> Ping</button>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-5 border border-secondary rounded bg-dark">
                        <i class="fa-solid fa-server fa-3x mb-3 opacity-50"></i>
                        <p class="mb-0">No providers configured in the database.</p>
                        <button class="btn btn-sm btn-primary mt-3">Configure Provider</button>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Charting -->
    <div class="col-md-6">
        <div class="card hover-3d h-100 border-0 shadow-sm" style="background: rgba(22, 27, 34, 0.5) !important; backdrop-filter: blur(10px);">
            <div class="card-header bg-transparent border-secondary pt-3 pb-2">
                <h5 class="mb-0 fw-bold text-light"><i class="fa-solid fa-chart-pie text-info me-2"></i> Tokens Allocation & Latency</h5>
            </div>
            <div class="card-body">
                <canvas id="latencyChart" style="max-height: 250px;"></canvas>
                
                <div class="mt-5 border-top border-secondary pt-4">
                    <h6 class="text-light fw-bold mb-3">Model Distribution</h6>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small"><i class="fa-solid fa-circle text-primary me-1" style="font-size: 8px;"></i> GPT-4 Turbo</span>
                        <span class="text-light fw-bold small">65%</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small"><i class="fa-solid fa-circle text-success me-1" style="font-size: 8px;"></i> Claude 3 Opus</span>
                        <span class="text-light fw-bold small">25%</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small"><i class="fa-solid fa-circle text-warning me-1" style="font-size: 8px;"></i> Local LLaMA 3</span>
                        <span class="text-light fw-bold small">10%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        // Toggle API Switch
        $('.form-check-input[role="switch"]').change(function() {
            const providerId = $(this).closest('.list-group-item').data('id');
            const isActive = $(this).is(':checked') ? 1 : 0;
            
            $.ajax({
                url: `/hub/models/${providerId}/toggle`,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                data: { is_active: isActive },
                success: function() {
                    console.log('Provider toggled successfully');
                }
            });
        });

        // Mock Ping functionality
        $('.btn-ping').click(function() {
            const btn = $(this);
            const provider = btn.data('provider');
            const originalHtml = btn.html();
            
            btn.html('<i class="fa-solid fa-spinner fa-spin"></i> Pinging...');
            btn.removeClass('btn-outline-success').addClass('btn-secondary');
            
            setTimeout(() => {
                btn.html('<i class="fa-solid fa-check"></i> ' + Math.floor(Math.random() * 100 + 50) + 'ms');
                btn.removeClass('btn-secondary').addClass('btn-success text-white');
                
                setTimeout(() => {
                    btn.html(originalHtml);
                    btn.removeClass('btn-success text-white').addClass('btn-outline-success');
                }, 3000);
            }, 1000);
        });

        // Chart.js Latency Setup
        const ctx = document.getElementById('latencyChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Gemini 1.5 Pro', 'Gemini Flash', 'Claude 3 Opus'],
                datasets: [{
                    label: 'Avg Latency (ms)',
                    data: [850, 320, 1200],
                    backgroundColor: [
                        'rgba(66, 133, 244, 0.6)',
                        'rgba(66, 133, 244, 0.9)',
                        'rgba(210, 153, 34, 0.6)'
                    ],
                    borderColor: [
                        '#4285F4',
                        '#4285F4',
                        '#d29922'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: '#c9d1d9' } }
                },
                scales: {
                    y: { grid: { color: '#30363d' }, ticks: { color: '#8b949e' } },
                    x: { grid: { display: false }, ticks: { color: '#8b949e' } }
                }
            }
        });
    });
</script>
@endpush
