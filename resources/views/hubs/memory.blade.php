@extends('layouts.app')

@section('content')
<!-- Header and Actions -->
<div class="row mb-4 align-items-center animate-fade-in stagger-1">
    <div class="col-md-6">
        <h2 class="fw-bold mb-0">MemoryHub</h2>
        <p class="text-muted text-sm">Cognitive Memory Management & Visualization.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <button class="btn btn-outline-secondary me-2" onclick="window.location.reload();"><i class="fa-solid fa-arrows-rotate me-1"></i> Sync Memory</button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#injectMemoryModal">
            <i class="fa-solid fa-plus me-1"></i> Add Memory
        </button>
    </div>
</div>

<div class="row g-4 animate-fade-in stagger-2">
    <!-- Main Content Area -->
    <div class="col-md-8">
        
        <!-- Search and Filters -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="input-group w-50">
                <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-solid fa-search"></i></span>
                <input type="text" id="memory-search-input" class="form-control bg-dark border-secondary text-light" placeholder="Search facts, events, or structured data...">
            </div>
            
            <ul class="nav nav-pills" id="memoryTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active bg-transparent text-light border border-secondary rounded-pill me-2 px-3 py-1" data-filter="all" type="button" role="tab" style="background-color: rgba(255,255,255,0.1) !important;">All</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link bg-transparent text-muted border border-secondary rounded-pill me-2 px-3 py-1" data-filter="semantic" type="button" role="tab">Semantic</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link bg-transparent text-muted border border-secondary rounded-pill px-3 py-1" data-filter="episodic" type="button" role="tab">Episodic</button>
                </li>
            </ul>
        </div>

        <!-- Memory Cards List -->
        <div class="d-flex flex-column gap-3" id="memory-cards-container">
            @forelse($memories as $memory)
                @php
                    $type = strtolower($memory->type ?? 'semantic');
                    // Normalize standard type badging
                    $isEpisodic = ($type === 'episodic' || $type === 'episode' || $type === 'memory_episodes');
                    $badgeClass = $isEpisodic ? 'bg-warning text-warning' : 'bg-primary text-primary';
                    $badgeIcon = $isEpisodic ? 'fa-clock-rotate-left' : 'fa-book-open';
                    $displayType = $isEpisodic ? 'episodic' : 'semantic';
                    
                    $meta = is_string($memory->metadata) ? json_decode($memory->metadata, true) : ($memory->metadata ?? []);
                    $confidence = $meta['confidence'] ?? 1.0;
                    $injectedBy = $meta['injected_by'] ?? ($memory->source ?? 'Core');
                    
                    $tags = is_string($memory->tags) ? json_decode($memory->tags, true) : ($memory->tags ?? []);
                @endphp
                <div class="card hover-3d bg-dark border-secondary border-0 shadow-sm memory-card-item type-{{ $displayType }}" style="background: rgba(22, 27, 34, 0.5); backdrop-filter: blur(10px);">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge {{ $badgeClass }} bg-opacity-25 border border-opacity-20 rounded-pill"><i class="fa-solid {{ $badgeIcon }} me-1"></i> {{ ucfirst($displayType) }}</span>
                            <div class="text-muted small"><i class="fa-solid fa-robot me-1"></i> Extracted by {{ ucfirst($injectedBy) }} <span class="ms-2">|</span> <span class="ms-2">Conf: {{ number_format($confidence, 2) }}</span></div>
                        </div>
                        <h6 class="text-light fw-bold mb-1 memory-title">{{ $memory->title ?: 'Untitled Memory' }}</h6>
                        <p class="text-muted small mb-3 memory-content">{{ $memory->content }}</p>
                        @if(!empty($tags))
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($tags as $tag)
                                <span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary rounded-pill">#{{ $tag }}</span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-4 py-5 text-center text-muted" id="memory-empty-state">
                    <i class="fa-solid fa-brain mb-2 d-block" style="font-size: 2rem; color: var(--glass-border);"></i>
                    No memories found.
                </div>
            @endforelse
        </div>

    </div>

    <!-- Right Sidebar (Insights & Stats) -->
    <div class="col-md-4">
        <!-- Tag Cloud -->
        <div class="card hover-3d bg-dark border-secondary mb-4 shadow-sm" style="background: rgba(22, 27, 34, 0.5); backdrop-filter: blur(10px);">
            <div class="card-header border-secondary bg-transparent fw-bold text-light pt-3">
                <i class="fa-solid fa-cloud text-info me-2"></i> Memory Concepts
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2" id="memory-tag-cloud">
                    @php
                        $allTags = [];
                        foreach($memories as $memory) {
                            $tags = is_string($memory->tags) ? json_decode($memory->tags, true) : ($memory->tags ?? []);
                            if(is_array($tags)) {
                                foreach($tags as $tag) {
                                    $allTags[$tag] = ($allTags[$tag] ?? 0) + 1;
                                }
                            }
                        }
                        arsort($allTags);
                        $tagColors = ['rgba(0, 122, 255, 0.2)' => '#58a6ff', 'rgba(99, 102, 241, 0.2)' => '#8b949e', 'rgba(63, 185, 80, 0.2)' => '#3fb950', 'rgba(248, 81, 73, 0.2)' => '#f85149', 'rgba(210, 168, 255, 0.2)' => '#d2a8ff'];
                    @endphp
                    @forelse(array_slice($allTags, 0, 15) as $tag => $count)
                        @php
                            $colorKey = array_rand($tagColors);
                            $textColor = $tagColors[$colorKey];
                        @endphp
                        <span class="badge rounded-pill" style="background-color: {{ $colorKey }}; color: {{ $textColor }}; font-size: {{ min(1.4, 0.8 + ($count * 0.15)) }}rem; padding: 6px 10px;">{{ $tag }}</span>
                    @empty
                        <span class="text-muted small">No tags found.</span>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Working Memory Context -->
        <div class="card hover-3d bg-dark border-secondary shadow-sm" style="background: rgba(22, 27, 34, 0.5); backdrop-filter: blur(10px);">
            <div class="card-header border-secondary bg-transparent fw-bold text-light pt-3">
                <i class="fa-solid fa-microchip text-success me-2"></i> Working Context
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Live context currently loaded in the agent's immediate memory.</p>
                <div class="d-flex flex-column gap-2">
                    <div class="bg-black border border-secondary p-2 rounded small text-light d-flex align-items-center">
                        <i class="fa-solid fa-tag text-muted me-2"></i> Active Memories: <span class="ms-auto fw-bold">{{ $memories->count() }}</span>
                    </div>
                    <div class="bg-black border border-secondary p-2 rounded small text-light d-flex align-items-center">
                        <i class="fa-solid fa-tag text-muted me-2"></i> Active Agent: <span class="ms-auto fw-bold text-primary">Souly</span>
                    </div>
                    <div class="bg-black border border-success p-2 rounded small text-success d-flex align-items-center">
                        <i class="fa-solid fa-check me-2"></i> System consolidated
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="injectMemoryModal" tabindex="-1" data-bs-theme="dark">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-light"><i class="fa-solid fa-microchip text-primary me-2"></i> Knowledge Synthesizer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted">Fact Type</label>
                    <select class="form-select bg-dark text-light border-secondary" id="memory-inject-type">
                        <option value="semantic">Semantic (Fact / Preference)</option>
                        <option value="episodic">Episodic (Event / Log)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Knowledge Content</label>
                    <textarea class="form-control bg-dark text-light border-secondary" rows="3" id="memory-inject-content" placeholder="Enter new knowledge details..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted d-flex justify-content-between">
                        <span>Confidence Weight</span>
                        <span id="conf-val" class="text-light">1.0</span>
                    </label>
                    <input type="range" class="form-range" min="0" max="1" step="0.1" id="conf-slider" value="1.0">
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btn-inject-fact">Inject Fact</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
    .nav-tabs .nav-link.active {
        background-color: var(--nexus-panel) !important;
        border-bottom: 2px solid var(--nexus-primary) !important;
    }
</style>
<script>
    $(document).ready(function() {
        $('#conf-slider').on('input', function() {
            $('#conf-val').text($(this).val());
        });

        // Tab filtering
        $('#memoryTabs button').on('click', function() {
            const filter = $(this).data('filter');
            
            // Toggle active classes
            $('#memoryTabs button').removeClass('active text-light').addClass('text-muted').css('background-color', 'transparent');
            $(this).addClass('active text-light').removeClass('text-muted').css('background-color', 'rgba(255,255,255,0.1)');
            
            if (filter === 'all') {
                $('.memory-card-item').show();
            } else {
                $('.memory-card-item').hide();
                $('.memory-card-item.type-' + filter).show();
            }
        });

        // Search filtering
        $('#memory-search-input').on('keyup', function() {
            const value = $(this).val().toLowerCase();
            $('.memory-card-item').filter(function() {
                const title = $(this).find('.memory-title').text().toLowerCase();
                const content = $(this).find('.memory-content').text().toLowerCase();
                $(this).toggle(title.indexOf(value) > -1 || content.indexOf(value) > -1);
            });
        });

        // Inject fact submission
        $('#btn-inject-fact').click(function() {
            const btn = $(this);
            const content = $('#memory-inject-content').val().trim();
            const type = $('#memory-inject-type').val();
            const confidence = $('#conf-slider').val();
            
            if (!content) {
                alert('Please enter knowledge content');
                return;
            }
            
            btn.html('<i class="fa-solid fa-spinner fa-spin"></i> Injecting...').prop('disabled', true);
            
            $.ajax({
                url: '{{ route("hub.memory.store") }}',
                method: 'POST',
                data: JSON.stringify({
                    content: content,
                    type: type,
                    confidence: parseFloat(confidence)
                }),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(res) {
                    $('#injectMemoryModal').modal('hide');
                    btn.html('Inject Fact').prop('disabled', false);
                    
                    if (window.Nexus && window.Nexus.notify) {
                        window.Nexus.notify('Memory injected successfully!', 'success');
                    } else {
                        alert('Memory injected successfully!');
                    }
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                },
                error: function(err) {
                    btn.html('Inject Fact').prop('disabled', false);
                    alert('Failed to inject memory: ' + (err.responseJSON?.message || 'Unknown error'));
                }
            });
        });
    });
</script>
@endpush
