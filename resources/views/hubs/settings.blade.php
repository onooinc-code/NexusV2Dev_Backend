@extends('layouts.app')

@section('content')
<div class="row mb-4 animate-fade-in stagger-1">
    <div class="col-md-6">
        <h2 class="fw-bold mb-0">SettingsHub</h2>
        <p class="text-muted">System Configuration & Core Preferences.</p>
    </div>
</div>

<div class="row g-4 animate-fade-in stagger-2">
    <!-- Sidebar Navigation -->
    <div class="col-md-3">
        <div class="card bg-dark border-secondary hover-3d h-100 shadow-sm" style="background: rgba(22, 27, 34, 0.5) !important; backdrop-filter: blur(10px);">
            <div class="card-body p-2">
                <div class="nav flex-column nav-pills me-3 w-100" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <button class="nav-link active text-start py-3 px-4 mb-2 text-light rounded shadow-sm border-0" id="v-pills-general-tab" data-bs-toggle="pill" data-bs-target="#v-pills-general" type="button" role="tab" style="background-color: rgba(255,255,255,0.05);">
                        <i class="fa-solid fa-sliders text-primary me-2 w-20px text-center"></i> General
                    </button>
                    <button class="nav-link text-start py-3 px-4 mb-2 text-muted rounded border-0" id="v-pills-data-tab" data-bs-toggle="pill" data-bs-target="#v-pills-data" type="button" role="tab">
                        <i class="fa-solid fa-database text-danger me-2 w-20px text-center"></i> Data & Cache
                    </button>
                    <button class="nav-link text-start py-3 px-4 mb-2 text-muted rounded border-0" id="v-pills-security-tab" data-bs-toggle="pill" data-bs-target="#v-pills-security" type="button" role="tab">
                        <i class="fa-solid fa-shield-halved text-success me-2 w-20px text-center"></i> Security
                    </button>
                    <button class="nav-link text-start py-3 px-4 text-muted rounded border-0" id="v-pills-billing-tab" data-bs-toggle="pill" data-bs-target="#v-pills-billing" type="button" role="tab">
                        <i class="fa-solid fa-credit-card text-warning me-2 w-20px text-center"></i> Billing
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="col-md-9">
        <div class="tab-content" id="v-pills-tabContent">
            
            <!-- General Settings Tab -->
            <div class="tab-pane fade show active" id="v-pills-general" role="tabpanel">
                <div class="card bg-dark border-secondary shadow-lg border-0" style="background: rgba(22, 27, 34, 0.5) !important; backdrop-filter: blur(10px);">
                    <div class="card-header bg-transparent border-secondary pt-3 pb-2">
                        <h5 class="mb-0 text-light fw-bold"><i class="fa-solid fa-sliders text-primary me-2"></i> Application Settings</h5>
                    </div>
                    <div class="card-body">
                        <form id="form-general-settings">
                            <div class="mb-5 bg-dark p-4 rounded border border-secondary" style="background-color: #0d1117 !important;">
                                <label class="form-label text-light fw-bold d-flex justify-content-between mb-3">
                                    <span><i class="fa-solid fa-gauge-high text-info me-2"></i> Dynamic Rate Limiter (Req/min)</span>
                                    <span id="rate-val" class="text-info fs-5">{{ $settings['rate_limit'] ?? 60 }}</span>
                                </label>
                                <input type="range" class="form-range" min="10" max="200" step="10" id="rate-slider" value="{{ $settings['rate_limit'] ?? 60 }}">
                                <p class="text-muted small mt-2 mb-0">Controls the global API request limits per user. Increase for high-traffic agents.</p>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <div class="form-check form-switch bg-dark p-3 rounded border border-secondary h-100 d-flex align-items-center" style="background-color: #0d1117 !important;">
                                        <div class="w-100 d-flex justify-content-between align-items-center ms-3">
                                            <div>
                                                <label class="form-check-label text-light fw-bold mb-1" for="switchMaintenance">Maintenance Mode</label>
                                                <div class="text-muted small" style="line-height: 1.2;">Disable agent interactions.</div>
                                            </div>
                                            <input class="form-check-input fs-4 m-0" type="checkbox" role="switch" id="switchMaintenance" {{ isset($settings['maintenance_mode']) && $settings['maintenance_mode'] ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch bg-dark p-3 rounded border border-secondary h-100 d-flex align-items-center" style="background-color: #0d1117 !important;">
                                        <div class="w-100 d-flex justify-content-between align-items-center ms-3">
                                            <div>
                                                <label class="form-check-label text-light fw-bold mb-1" for="switchDebug">Telemetry Logs</label>
                                                <div class="text-muted small" style="line-height: 1.2;">Verbose output in LogsHub.</div>
                                            </div>
                                            <input class="form-check-input fs-4 m-0" type="checkbox" role="switch" id="switchDebug" {{ isset($settings['debug_telemetry']) && $settings['debug_telemetry'] ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end border-top border-secondary pt-3 mt-4">
                                <button type="button" class="btn btn-primary px-4" id="btn-save-settings"><i class="fa-solid fa-floppy-disk me-2"></i> Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Data Management Tab -->
            <div class="tab-pane fade" id="v-pills-data" role="tabpanel">
                <div class="card bg-dark border-danger shadow-lg border-opacity-25" style="background: rgba(22, 27, 34, 0.5) !important; backdrop-filter: blur(10px);">
                    <div class="card-header bg-transparent border-danger border-opacity-25 pt-3 pb-2">
                        <h5 class="mb-0 text-danger fw-bold"><i class="fa-solid fa-database me-2"></i> Data & Cache Management</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4 bg-dark p-4 rounded border border-secondary" style="background-color: #0d1117 !important;">
                            <h6 class="text-light fw-bold"><i class="fa-solid fa-memory text-warning me-2"></i> Cognitive Cache Manager</h6>
                            <p class="text-muted small mb-4">The system caches repeated API calls to LLMs to save tokens. You can manually clear this cache if agents exhibit stale responses.</p>
                            
                            <div class="d-flex justify-content-between align-items-center bg-black p-3 rounded border border-secondary mb-3">
                                <div>
                                    <div class="fw-bold text-light mb-1">Local Cache Size</div>
                                    <div class="text-muted small">Approx. 4.2 MB across 1,204 keys</div>
                                </div>
                                <button class="btn btn-outline-warning" id="btn-clear-cache"><i class="fa-solid fa-broom me-1"></i> Flush Cache</button>
                            </div>
                        </div>

                        <div class="alert alert-danger bg-danger bg-opacity-10 border border-danger p-4 mb-0">
                            <h6 class="text-danger fw-bold mb-2"><i class="fa-solid fa-triangle-exclamation me-2"></i> Danger Zone</h6>
                            <p class="text-muted small mb-3">These actions are irreversible and will cause immediate data loss.</p>
                            <button class="btn btn-danger" id="btn-factory-purge"><i class="fa-solid fa-skull me-2"></i> Factory Reset System</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Other tabs placeholders -->
            <div class="tab-pane fade" id="v-pills-security" role="tabpanel">
                <p class="text-muted mt-3 ms-2">Security settings coming soon...</p>
            </div>
            <div class="tab-pane fade" id="v-pills-billing" role="tabpanel">
                <p class="text-muted mt-3 ms-2">Billing integration coming soon...</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Slider value update
        $('#rate-slider').on('input', function() {
            $('#rate-val').text($(this).val());
        });

        // AJAX Settings save
        $('#btn-save-settings').click(function() {
            const btn = $(this);
            const originalText = btn.text();
            btn.html('<i class="fa-solid fa-spinner fa-spin"></i> Saving...');
            btn.prop('disabled', true);
            
            $.ajax({
                url: '{{ route('hub.settings.update') }}',
                method: 'POST',
                headers: { 
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: {
                    rate_limit: $('#rate-slider').val(),
                    maintenance_mode: $('#switchMaintenance').is(':checked') ? 1 : 0,
                    debug_telemetry: $('#switchDebug').is(':checked') ? 1 : 0
                },
                success: function() {
                    btn.html('<i class="fa-solid fa-floppy-disk me-2"></i> Save Changes').prop('disabled', false);
                    // Add a small success toast or alert
                    alert('Settings saved successfully!');
                },
                error: function(err) {
                    btn.html(originalText).prop('disabled', false);
                    alert('Error saving settings.');
                    console.error(err);
                }
            });
        });

        // Mock Cache Clear
        $('#btn-clear-cache').click(function() {
            if(confirm("Are you sure you want to clear the cognitive cache?")) {
                const btn = $(this);
                btn.html('<i class="fa-solid fa-spinner fa-spin"></i> Clearing...');
                setTimeout(() => {
                    btn.html('<i class="fa-solid fa-check"></i> Cleared');
                    btn.removeClass('btn-outline-warning').addClass('btn-success');
                    setTimeout(() => {
                        btn.html('<i class="fa-solid fa-broom me-1"></i> Clear Cache');
                        btn.removeClass('btn-success').addClass('btn-outline-warning');
                    }, 2000);
                }, 1000);
            }
        });

        // Factory Purge Action
        $('#btn-factory-purge').click(function() {
            if(prompt("Type 'PURGE' to confirm factory wipe:") === 'PURGE') {
                $.ajax({
                    url: '/api/v1/settings/factory-reset',
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    data: { _token: '{{ csrf_token() }}' },
                    success: function() {
                        alert("Factory purge successful!");
                        window.location.reload();
                    },
                    error: function(err) {
                        alert("Failed to factory purge. Check permissions.");
                        console.error(err);
                    }
                });
            }
        });
    });
</script>
@endpush
