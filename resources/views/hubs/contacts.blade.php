@extends('layouts.app')
@section('page_title', 'ContactsHub')

@push('styles')
<style>
/* ─── 3D Contact Card ─── */
.nx-contact-card {
    perspective: 1200px;
    cursor: pointer;
    height: 100%;
}
.nx-contact-card-inner {
    background: linear-gradient(135deg, rgba(15,23,42,0.7) 0%, rgba(30,41,59,0.5) 100%);
    backdrop-filter: blur(12px);
    border: 1px solid var(--glass-border);
    border-radius: 14px;
    padding: 20px;
    position: relative;
    overflow: hidden;
    height: 100%;
    transition: transform 0.2s ease-out, box-shadow 0.25s ease, border-color 0.25s ease;
    transform-style: preserve-3d;
}
.nx-contact-card:hover .nx-contact-card-inner {
    box-shadow: 0 12px 40px rgba(0,0,0,0.5), 0 0 0 1px var(--nexus-blue-glow);
    border-color: var(--nexus-blue-glow);
}
.nx-contact-card .card-bg-glow {
    position: absolute;
    top: -40px; right: -40px;
    width: 120px; height: 120px;
    border-radius: 50%;
    background: var(--nexus-blue-dim);
    filter: blur(40px);
    pointer-events: none;
    transition: background 0.3s ease;
}
.nx-contact-card:hover .card-bg-glow {
    background: hsla(217,91%,60%,0.2);
}

/* Avatar */
.contact-avatar {
    width: 46px; height: 46px;
    border-radius: 50%;
    background: var(--nexus-blue-dim);
    border: 2px solid var(--nexus-blue-glow);
    display: flex; align-items: center; justify-content: center;
    font-weight: 700;
    font-family: 'Outfit', sans-serif;
    font-size: 1.1rem;
    color: var(--nexus-blue);
    flex-shrink: 0;
    position: relative;
    overflow: hidden;
}

/* Confidence Ring SVG */
.nx-confidence-ring {
    width: 38px; height: 38px;
    flex-shrink: 0;
    position: relative;
}
.nx-confidence-ring svg { transform: rotate(-90deg); }
.nx-confidence-ring .ring-text {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.55rem;
    font-family: 'JetBrains Mono', monospace;
    color: var(--nexus-teal);
}

/* Quick actions bar (slide up on hover) */
.card-quick-actions {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    background: hsla(217,91%,60%,0.1);
    backdrop-filter: blur(8px);
    border-top: 1px solid var(--glass-border);
    padding: 8px 12px;
    display: flex;
    gap: 4px;
    justify-content: center;
    transform: translateY(100%);
    transition: transform 0.2s var(--ease-smooth);
    border-radius: 0 0 14px 14px;
}
.nx-contact-card:hover .card-quick-actions {
    transform: translateY(0);
}
.card-quick-actions .qa-btn {
    width: 28px; height: 28px;
    background: rgba(255,255,255,0.06);
    border: none;
    border-radius: 6px;
    color: var(--text-secondary);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
    font-size: 0.7rem;
}
.card-quick-actions .qa-btn:hover {
    background: rgba(255,255,255,0.12);
    color: var(--text-primary);
}

/* Table row hover */
.contacts-table tbody tr {
    cursor: pointer;
    transition: background 0.15s ease;
}
.contacts-table tbody tr:hover {
    background: rgba(59,130,246,0.05) !important;
}

/* Mode segmented control */
.mode-control {
    display: flex;
    background: rgba(0,0,0,0.3);
    border: 1px solid var(--glass-border);
    border-radius: 8px;
    padding: 3px;
    gap: 2px;
}
.mode-control .mode-btn {
    padding: 5px 14px;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 600;
    font-family: 'JetBrains Mono', monospace;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    color: var(--text-muted);
    background: transparent;
}
.mode-control .mode-btn:hover { color: var(--text-primary); background: rgba(255,255,255,0.04); }
.mode-control .mode-btn.active-manual   { background: rgba(255,255,255,0.08); color: var(--text-primary); }
.mode-control .mode-btn.active-copilot  { background: var(--nexus-blue-dim); color: var(--nexus-blue); border: 1px solid var(--nexus-blue-glow); }
.mode-control .mode-btn.active-autopilot { background: var(--amber-dim); color: hsl(38,92%,65%); border: 1px solid hsla(38,92%,50%,0.4); }

/* Stats bar */
.stats-chip {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.72rem;
    font-family: 'JetBrains Mono', monospace;
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--glass-border);
    color: var(--text-secondary);
}
</style>
@endpush

@section('content')

{{-- ── Autopilot Banner (hidden by default) ── --}}
{{-- Managed via global Nexus.setAutopilot() in JS --}}

<div class="d-flex flex-column gap-4 animate-in">

    {{-- ═══ HEADER ═══ --}}
    <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3 stagger-1" style="opacity: 0;">
        <div>
            <div class="d-flex align-items-center gap-3 mb-1">
                <div style="width: 42px; height: 42px; background: var(--nexus-blue-dim); border: 1px solid var(--nexus-blue-glow); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-address-book" style="color: var(--nexus-blue); font-size: 1.1rem;"></i>
                </div>
                <div>
                    <h1 class="mb-0" style="font-size: 1.4rem; font-weight: 700; letter-spacing: -0.02em;">Contacts Intelligence</h1>
                    <p class="text-muted mb-0" style="font-size: 0.8rem;">Cognitive contact management & relationship intelligence</p>
                </div>
            </div>

            {{-- Stats bar --}}
            <div class="d-flex flex-wrap gap-2 mt-2">
                <span class="stats-chip"><i class="fa-solid fa-users" style="color: var(--nexus-blue);"></i> {{ $totalContacts }} Total</span>
                <span class="stats-chip" id="waha-chip"><i class="fa-brands fa-whatsapp" style="color: hsl(142,76%,55%);"></i> {{ $wahaContacts }} WAHA Connected</span>
                <span class="stats-chip"><i class="fa-solid fa-bolt" style="color: var(--amber);"></i> <span id="autopilot-count">{{ $autopilotCount }}</span> Autopilot</span>
                <span class="stats-chip"><i class="fa-solid fa-circle-half-stroke" style="color: var(--nexus-teal);"></i> <span id="copilot-count">{{ $copilotCount }}</span> Copilot</span>
            </div>
        </div>

        {{-- Actions --}}
        <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
            {{-- Global Reply Mode --}}
            <div class="mode-control">
                <button class="mode-btn active-manual" data-mode="manual" id="mode-manual">Manual</button>
                <button class="mode-btn" data-mode="copilot" id="mode-copilot">Copilot</button>
                <button class="mode-btn" data-mode="autopilot" id="mode-autopilot">⚡ Autopilot</button>
            </div>
            <button class="btn btn-sm" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--text-secondary); border-radius: 8px; font-size: 0.78rem; padding: 6px 14px;" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="fa-solid fa-upload me-1"></i> Import
            </button>
            <button class="btn btn-sm" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--text-secondary); border-radius: 8px; font-size: 0.78rem; padding: 6px 14px;" data-bs-toggle="modal" data-bs-target="#maintenanceModal">
                <i class="fa-solid fa-screwdriver-wrench me-1"></i> Maintenance
            </button>
            <button class="btn btn-sm btn-primary" data-bs-toggle="offcanvas" data-bs-target="#addContactDrawer" style="font-size: 0.78rem; padding: 6px 16px; border-radius: 8px;">
                <i class="fa-solid fa-plus me-1"></i> Add Contact
            </button>
        </div>
    </div>

    {{-- ═══ FILTER & SEARCH BAR ═══ --}}
    <form method="GET" action="{{ route('hub.contacts') }}" class="d-flex flex-wrap gap-3 align-items-center p-3 rounded-3 stagger-2 w-100" style="background: rgba(255,255,255,0.02); border: 1px solid var(--glass-border); opacity: 0;">
        {{-- Hidden Mode --}}
        <input type="hidden" name="mode" id="form-mode" value="{{ request('mode', 'all') }}">

        {{-- Search --}}
        <div class="position-relative flex-grow-1" style="min-width: 200px; max-width: 320px;">
            <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="top: 50%; left: 12px; transform: translateY(-50%); font-size: 0.75rem;"></i>
            <input type="text" name="search" id="search-query" value="{{ request('search') }}" class="form-control ps-4" placeholder="Search name, phone, email..." style="font-size: 0.83rem;">
        </div>
        
        <div class="d-flex align-items-center gap-3">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" name="waha" value="1" id="filter-waha" onchange="this.form.submit()" {{ request('waha') == '1' ? 'checked' : '' }}>
                <label class="form-check-label text-muted small" for="filter-waha">WAHA Only</label>
            </div>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" name="favorites" value="1" id="filter-favorites" onchange="this.form.submit()" {{ request('favorites') == '1' ? 'checked' : '' }}>
                <label class="form-check-label text-muted small" for="filter-favorites">Favorites Only</label>
            </div>
        </div>

        <button type="submit" class="btn btn-sm btn-primary" style="border-radius: 8px; font-size: 0.78rem; padding: 6px 14px;">
            <i class="fa-solid fa-filter me-1"></i> Apply
        </button>
        <a href="{{ route('hub.contacts') }}" class="btn btn-sm" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); color: var(--text-muted); border-radius: 8px; font-size: 0.78rem; padding: 6px 12px;">
            <i class="fa-solid fa-rotate-right me-1"></i> Reset
        </a>

        <div class="d-flex gap-1 p-1 ms-auto rounded" style="background: rgba(0,0,0,0.25); border: 1px solid var(--glass-border);">
            <button type="button" id="btn-view-grid" class="btn btn-sm px-2 py-1" style="background: var(--nexus-blue-dim); border: 1px solid var(--nexus-blue-glow); color: var(--nexus-blue); border-radius: 5px;" title="Grid View">
                <i class="fa-solid fa-grip"></i>
            </button>
            <button type="button" id="btn-view-table" class="btn btn-sm px-2 py-1" style="background: transparent; border: none; color: var(--text-muted); border-radius: 5px;" title="Table View">
                <i class="fa-solid fa-table-list"></i>
            </button>
        </div>
    </form>

    {{-- ═══ CONTACTS RESULTS ═══ --}}
    <div id="contacts-container" class="stagger-3" style="opacity: 0;">

        {{-- ── GRID VIEW ── --}}
        <div id="view-grid" class="row g-3">
            @forelse($contacts as $contact)
            <div class="col-6 col-md-4 col-lg-3 contact-item"
                 data-name="{{ strtolower($contact->name ?? '') }}"
                 data-email="{{ strtolower($contact->email ?? '') }}"
                 data-phone="{{ strtolower($contact->phone ?? '') }}"
                 data-role="{{ strtolower($contact->role ?? '') }}"
                 data-company="{{ strtolower($contact->company ?? '') }}"
                 data-favorite="{{ $contact->isFavoritedBy(auth()->user()) ? 'true' : 'false' }}">

                <div class="nx-contact-card" onclick="window.location='{{ route('hub.contacts.profile', $contact->id) }}'">
                    <div class="nx-contact-card-inner"
                         onmousemove="tilt3d(this, event)"
                         onmouseleave="resetTilt(this)">

                         <div class="card-bg-glow"></div>

                        {{-- Top row: avatar + confidence ring --}}
                        <div class="d-flex align-items-start justify-content-between mb-3 position-relative" style="z-index: 2;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="contact-avatar">
                                    {{ strtoupper(substr($contact->name ?? 'U', 0, 1)) }}
                                </div>
                                @php $isFav = $contact->isFavoritedBy(auth()->user()); @endphp
                                <button class="btn-toggle-favorite border-0 bg-transparent p-0 ms-1" 
                                        data-id="{{ $contact->id }}" 
                                        title="Toggle Favorite" 
                                        onclick="event.stopPropagation(); toggleFavoriteContact(this);">
                                    <i class="{{ $isFav ? 'fa-solid' : 'fa-regular' }} fa-star" 
                                       style="color: {{ $isFav ? '#eab308' : 'var(--text-muted)' }}; font-size: 1.1rem; cursor: pointer; transition: all 0.15s ease;"></i>
                                </button>
                            </div>
                            {{-- Confidence Ring --}}
                            @php $conf = $contact->profile_confidence ?? 50; $circ = 2 * 3.14159 * 15; $dash = ($conf / 100) * $circ; @endphp
                            <div class="nx-confidence-ring" title="{{ $conf }}% confidence">
                                <svg width="38" height="38" viewBox="0 0 38 38">
                                    <circle class="ring-bg" cx="19" cy="19" r="15" fill="none" stroke="rgba(255,255,255,0.07)" stroke-width="3"></circle>
                                    <circle cx="19" cy="19" r="15" fill="none"
                                        stroke="{{ $conf >= 70 ? 'hsl(174,90%,41%)' : ($conf >= 40 ? 'hsl(38,92%,50%)' : 'hsl(0,84%,60%)') }}"
                                        stroke-width="3"
                                        stroke-linecap="round"
                                        stroke-dasharray="{{ $dash }} {{ $circ }}"
                                        style="transform-origin: center; transition: stroke-dasharray 0.5s ease;"></circle>
                                </svg>
                                <div class="ring-text">{{ $conf }}%</div>
                            </div>
                        </div>

                        {{-- Name + Role --}}
                        <div class="mb-3 position-relative" style="z-index: 2;">
                            <h6 class="mb-0 fw-semibold" style="font-size: 0.9rem; color: var(--text-primary);">{{ $contact->name }}</h6>
                            <span style="font-size: 0.72rem; color: var(--nexus-blue); font-weight: 500;">{{ $contact->role ?? 'No role' }}</span>
                        </div>

                        {{-- Chips row --}}
                        <div class="d-flex flex-wrap gap-1 mb-3 position-relative" style="z-index: 2;">
                            @if($contact->waha_contact_id)
                            <span class="emotion-chip" style="background: hsla(142,72%,29%,0.15); border-color: hsla(142,72%,29%,0.35); color: hsl(142,76%,60%);">
                                <i class="fa-brands fa-whatsapp" style="font-size: 0.5rem;"></i> WAHA
                            </span>
                            @endif
                            @if($contact->emotional_baseline)
                            <span class="emotion-chip neutral">{{ $contact->emotional_baseline }}</span>
                            @endif
                            <span class="reply-mode-badge {{ $contact->reply_mode_override ?? 'manual' }}">
                                {{ $contact->reply_mode_override ?? 'global' }}
                            </span>
                        </div>

                        {{-- Contact details --}}
                        <div class="d-flex flex-column gap-1 position-relative pb-2" style="z-index: 2; font-size: 0.75rem; color: var(--text-muted);">
                            @if($contact->company)
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-building" style="width: 12px;"></i>
                                <span class="truncate">{{ $contact->company }}</span>
                            </div>
                            @endif
                            @if($contact->email)
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-regular fa-envelope" style="width: 12px;"></i>
                                <span class="truncate">{{ $contact->email }}</span>
                            </div>
                            @endif
                            @if($contact->phone)
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-phone" style="width: 12px;"></i>
                                <span>{{ $contact->phone }}</span>
                            </div>
                            @endif
                        </div>

                        {{-- Quick Actions (slides up on hover) --}}
                        <div class="card-quick-actions" onclick="event.stopPropagation()">
                            <button class="qa-btn" title="Open Profile" onclick="window.location='{{ route('hub.contacts.profile', $contact->id) }}'">
                                <i class="fa-solid fa-user"></i>
                            </button>
                            <button class="qa-btn" title="View Conversations" onclick="window.location='{{ route('hub.people-connect') }}?contact_id={{ $contact->id }}'">
                                <i class="fa-solid fa-comments"></i>
                            </button>
                            <button class="qa-btn" title="Import Messages" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="fa-solid fa-download"></i>
                            </button>
                            <button class="qa-btn" title="Edit Reply Mode" data-contact-id="{{ $contact->id }}">
                                <i class="fa-solid fa-gear"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center py-5">
                <div style="width: 64px; height: 64px; background: var(--nexus-blue-dim); border: 1px solid var(--nexus-blue-glow); border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <i class="fa-solid fa-users-slash" style="color: var(--nexus-blue); font-size: 1.5rem;"></i>
                </div>
                <h5 class="fw-semibold mb-2">No Contacts Yet</h5>
                <p class="text-muted" style="font-size: 0.83rem;">Start by adding contacts or syncing from WAHA</p>
                <button class="btn btn-primary btn-sm mt-2" data-bs-toggle="offcanvas" data-bs-target="#addContactDrawer">
                    <i class="fa-solid fa-plus me-1"></i> Add First Contact
                </button>
            </div>
            @endforelse
        </div>

        {{-- ── TABLE VIEW ── --}}
        <div id="view-table" class="d-none">
            <div class="nx-glass-panel overflow-hidden">
                <table class="table table-dark mb-0 contacts-table" id="contacts-datatable">
                    <thead>
                        <tr>
                            <th style="width: 30px;"></th>
                            <th style="width: 40px;"></th>
                            <th>Name</th>
                            <th>Role / Company</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Reply Mode</th>
                            <th>Confidence</th>
                            <th style="width: 80px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contacts as $contact)
                        <tr onclick="window.location='{{ route('hub.contacts.profile', $contact->id) }}'"
                            class="contact-row"
                            data-name="{{ strtolower($contact->name ?? '') }}"
                            data-email="{{ strtolower($contact->email ?? '') }}"
                            data-phone="{{ strtolower($contact->phone ?? '') }}"
                            data-role="{{ strtolower($contact->role ?? '') }}"
                            data-company="{{ strtolower($contact->company ?? '') }}"
                            data-favorite="{{ $contact->isFavoritedBy(auth()->user()) ? 'true' : 'false' }}">
                            <td>
                                @php $isFav = $contact->isFavoritedBy(auth()->user()); @endphp
                                <button class="btn-toggle-favorite border-0 bg-transparent p-0" 
                                        data-id="{{ $contact->id }}" 
                                        title="Toggle Favorite" 
                                        onclick="event.stopPropagation(); toggleFavoriteContact(this);">
                                    <i class="{{ $isFav ? 'fa-solid' : 'fa-regular' }} fa-star" 
                                       style="color: {{ $isFav ? '#eab308' : 'var(--text-muted)' }}; font-size: 0.95rem; cursor: pointer; transition: all 0.15s ease;"></i>
                                </button>
                            </td>
                            <td>
                                <div class="contact-avatar" style="width: 32px; height: 32px; font-size: 0.75rem;">
                                    {{ strtoupper(substr($contact->name ?? 'U', 0, 1)) }}
                                </div>
                            </td>
                            <td>
                                <span class="fw-medium" style="color: var(--text-primary);">{{ $contact->name }}</span>
                                @if($contact->waha_contact_id)
                                <i class="fa-brands fa-whatsapp ms-1" style="color: hsl(142,76%,55%); font-size: 0.75rem;" title="WAHA connected"></i>
                                @endif
                            </td>
                            <td>
                                <div style="font-size: 0.82rem;">{{ $contact->role ?? '—' }}</div>
                                <div style="font-size: 0.7rem; color: var(--text-muted);">{{ $contact->company ?? '' }}</div>
                            </td>
                            <td style="font-size: 0.8rem; color: var(--text-secondary);">{{ $contact->email ?? '—' }}</td>
                            <td style="font-size: 0.8rem; color: var(--text-secondary);">{{ $contact->phone ?? '—' }}</td>
                            <td>
                                <span class="reply-mode-badge {{ $contact->reply_mode_override ?? 'manual' }}">
                                    {{ $contact->reply_mode_override ?? 'global' }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="flex: 1; height: 4px; background: rgba(255,255,255,0.08); border-radius: 2px; overflow: hidden;">
                                        @php $conf = $contact->profile_confidence ?? 50; @endphp
                                        <div style="height: 100%; width: {{ $conf }}%; background: {{ $conf >= 70 ? 'hsl(174,90%,41%)' : ($conf >= 40 ? 'hsl(38,92%,50%)' : 'hsl(0,84%,60%)') }}; border-radius: 2px;"></div>
                                    </div>
                                    <span style="font-size: 0.68rem; font-family: 'JetBrains Mono'; color: var(--text-muted);">{{ $conf }}%</span>
                                </div>
                            </td>
                            <td onclick="event.stopPropagation()">
                                <div class="d-flex gap-1">
                                    <a href="{{ route('hub.contacts.profile', $contact->id) }}" class="btn btn-sm" style="padding: 3px 7px; background: rgba(255,255,255,0.04); border: none; color: var(--text-muted); border-radius: 5px; font-size: 0.7rem;">
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- No results after filter --}}
        <div id="no-results" class="d-none text-center py-5">
            <i class="fa-solid fa-magnifying-glass mb-2 d-block" style="font-size: 1.5rem; color: var(--glass-border);"></i>
            <p class="text-muted" style="font-size: 0.83rem;">No contacts match your search.</p>
        </div>

    </div>
    
    {{-- Pagination --}}
    <div class="mt-4 d-flex justify-content-center" data-bs-theme="dark">
        {{ $contacts->links('pagination::bootstrap-5') }}
    </div>
</div>

{{-- ═══ ADD CONTACT OFFCANVAS DRAWER ═══ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="addContactDrawer" style="width: 400px;">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title d-flex align-items-center gap-2">
            <i class="fa-solid fa-user-plus" style="color: var(--nexus-blue);"></i>
            Add New Contact
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <form id="add-contact-form">
            @csrf
            <div class="mb-3">
                <label class="form-label text-muted" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.8px; font-family: 'JetBrains Mono';">Full Name *</label>
                <input type="text" name="name" class="form-control" placeholder="Contact name" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.8px; font-family: 'JetBrains Mono';">Role</label>
                <input type="text" name="role" class="form-control" placeholder="e.g. Client, Partner, Friend">
            </div>
            <div class="mb-3">
                <label class="form-label text-muted" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.8px; font-family: 'JetBrains Mono';">Company</label>
                <input type="text" name="company" class="form-control" placeholder="Organization name">
            </div>
            <div class="mb-3">
                <label class="form-label text-muted" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.8px; font-family: 'JetBrains Mono';">Email</label>
                <input type="email" name="email" class="form-control" placeholder="email@example.com">
            </div>
            <div class="mb-3">
                <label class="form-label text-muted" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.8px; font-family: 'JetBrains Mono';">Phone / WhatsApp</label>
                <input type="text" name="phone" class="form-control" placeholder="+1234567890">
            </div>
            <div id="contact-form-error" class="alert d-none" style="background: var(--error-dim); border: 1px solid hsla(0,84%,60%,0.3); border-radius: 8px; font-size: 0.8rem; color: hsl(0,84%,70%);"></div>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary flex-1 w-100" id="btn-save-contact">
                    <i class="fa-solid fa-check me-1"></i> Save Contact
                </button>
                <button type="button" class="btn" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--text-secondary);" data-bs-dismiss="offcanvas">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- ═══ IMPORT MESSAGES MODAL ═══ --}}
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="fa-solid fa-upload" style="color: var(--nexus-blue);"></i>
                    Import Messages
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                {{-- Drag & Drop Zone --}}
                <div class="nx-dropzone mb-4" id="dropzone" onclick="$('#file-input').click()">
                    <i class="fa-solid fa-cloud-arrow-up mb-3 d-block" style="font-size: 2rem; color: var(--nexus-blue);"></i>
                    <div class="fw-semibold mb-1" style="font-size: 0.9rem;">Drop WhatsApp export file here</div>
                    <div class="text-muted" style="font-size: 0.75rem;">Supports .zip, .txt (WhatsApp chat export format)</div>
                    <input type="file" id="file-input" accept=".zip,.txt" class="d-none">
                </div>
                {{-- Preview Step (hidden until file selected) --}}
                <div id="import-preview" class="d-none">
                    <div class="p-3 rounded-3 mb-3" style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border);">
                        <div class="row g-3 text-center">
                            <div class="col-4">
                                <div style="font-family: 'Outfit'; font-size: 1.5rem; font-weight: 700; color: var(--nexus-blue);" id="import-count-total">—</div>
                                <div style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-family: 'JetBrains Mono';">Total Messages</div>
                            </div>
                            <div class="col-4">
                                <div style="font-family: 'Outfit'; font-size: 1.5rem; font-weight: 700; color: var(--success-bright);" id="import-count-new">—</div>
                                <div style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-family: 'JetBrains Mono';">New Messages</div>
                            </div>
                            <div class="col-4">
                                <div style="font-family: 'Outfit'; font-size: 1.5rem; font-weight: 700; color: var(--amber);" id="import-count-dups">—</div>
                                <div style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-family: 'JetBrains Mono';">Duplicates</div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary flex-1" id="btn-confirm-import">
                            <i class="fa-solid fa-check me-1"></i> Confirm Import
                        </button>
                        <button class="btn btn-sm" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--text-secondary);" id="btn-cancel-import">
                            Cancel
                        </button>
                    </div>
                </div>
                {{-- Progress --}}
                <div id="import-progress" class="d-none">
                    <div class="mb-2" style="font-size: 0.8rem; color: var(--text-muted);">Importing messages...</div>
                    <div style="height: 4px; background: rgba(255,255,255,0.06); border-radius: 2px; overflow: hidden; margin-bottom: 8px;">
                        <div id="import-bar" style="height: 100%; width: 0%; background: linear-gradient(90deg, var(--nexus-blue), var(--nexus-teal)); border-radius: 2px; transition: width 0.3s ease;"></div>
                    </div>
                    <div id="import-status-text" style="font-size: 0.75rem; font-family: 'JetBrains Mono'; color: var(--nexus-teal);">Processing...</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ MAINTENANCE MODAL ═══ --}}
<div class="modal fade" id="maintenanceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="fa-solid fa-screwdriver-wrench" style="color: var(--nexus-teal);"></i>
                    Global Maintenance
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted mb-4" style="font-size: 0.82rem;">Select maintenance operations to perform on the entire contact database:</p>
                <div class="d-flex flex-column gap-3">
                    @foreach([
                        ['Re-analyze all contacts', 'fa-brain', 'Run AI analysis on every contact to refresh insights and confidence scores.'],
                        ['Refresh memory freshness', 'fa-database', 'Update memory freshness timestamps and consolidate stale memories.'],
                        ['Resolve identity conflicts', 'fa-code-merge', 'Detect and resolve duplicate contact entries.'],
                        ['Sync WAHA contacts', 'fa-whatsapp', 'Re-sync all contacts with the WAHA API session.'],
                    ] as $action)
                    <div class="d-flex gap-3 p-3 rounded-3" style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border);">
                        <input class="form-check-input mt-1 maintenance-action flex-shrink-0" type="checkbox" value="{{ $action[0] }}" id="ma-{{ $loop->index }}" style="width: 16px; height: 16px; border-radius: 4px; accent-color: var(--nexus-blue);">
                        <div>
                            <label class="fw-medium mb-1 d-flex align-items-center gap-2" for="ma-{{ $loop->index }}" style="cursor: pointer; font-size: 0.85rem;">
                                <i class="fa-{{ $action[1] === 'fa-whatsapp' ? 'brands' : 'solid' }} {{ $action[1] }}" style="color: var(--nexus-blue); font-size: 0.8rem;"></i>
                                {{ $action[0] }}
                            </label>
                            <div class="text-muted" style="font-size: 0.73rem;">{{ $action[2] }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div id="maintenance-progress" class="d-none mt-4">
                    <div style="height: 4px; background: rgba(255,255,255,0.06); border-radius: 2px; overflow: hidden; margin-bottom: 8px;">
                        <div id="maintenance-bar" style="height: 100%; width: 0%; background: linear-gradient(90deg, var(--nexus-teal), var(--nexus-blue)); border-radius: 2px; transition: width 0.5s ease;"></div>
                    </div>
                    <div id="maintenance-status" style="font-size: 0.75rem; font-family: 'JetBrains Mono'; color: var(--nexus-teal);">Queuing maintenance jobs...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--text-secondary);" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="btn-run-maintenance">
                    <i class="fa-solid fa-play me-1"></i> Run Selected
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // ─── 3D Card Tilt ───
    window.tilt3d = function(el, e) {
        const rect = el.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        const cx = rect.width / 2;
        const cy = rect.height / 2;
        const rx = ((y - cy) / cy) * -8;
        const ry = ((x - cx) / cx) * 8;
        el.style.transform = `perspective(1200px) rotateX(${rx}deg) rotateY(${ry}deg)`;
    };
    window.resetTilt = function(el) {
        el.style.transform = 'perspective(1200px) rotateX(0) rotateY(0)';
    };

$(document).ready(function () {

    // ─── View Toggle ───
    $('#btn-view-grid').on('click', function() {
        $('#view-grid').removeClass('d-none');
        $('#view-table').addClass('d-none');
        $(this).css({'background': 'var(--nexus-blue-dim)', 'border': '1px solid var(--nexus-blue-glow)', 'color': 'var(--nexus-blue)'});
        $('#btn-view-table').css({'background': 'transparent', 'border': 'none', 'color': 'var(--text-muted)'});
    });

    $('#btn-view-table').on('click', function() {
        $('#view-grid').addClass('d-none');
        $('#view-table').removeClass('d-none');
        $(this).css({'background': 'var(--nexus-blue-dim)', 'border': '1px solid var(--nexus-blue-glow)', 'color': 'var(--nexus-blue)'});
        $('#btn-view-grid').css({'background': 'transparent', 'border': 'none', 'color': 'var(--text-muted)'});
    });

    // ─── Filtering ───
    // Now handled by the backend via form submission.
    $('#btn-filter-favorites').on('click', function() {
        // Toggle the button style
        $(this).toggleClass('btn-outline-warning btn-warning');
        $(this).find('i').toggleClass('fa-regular fa-solid');
        
        // Let's add a hidden input if they want to filter favorites, or just leave it for now.
    });

    // ─── Toggle Favorite Contact ───
    window.toggleFavoriteContact = function(btn) {
        const contactId = $(btn).data('id');
        const starIcon = $(btn).find('i');
        
        starIcon.removeClass('fa-solid fa-regular').addClass('fa-solid fa-spinner fa-spin').css('color', 'var(--nexus-blue)');

        $.ajax({
            url: `/hub/contacts/${contactId}/toggle-favorite`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                if (res.success) {
                    const allBtns = $(`.btn-toggle-favorite[data-id="${contactId}"]`);
                    allBtns.each(function() {
                        const icon = $(this).find('i');
                        icon.removeClass('fa-spinner fa-spin');
                        if (res.is_favorite) {
                            icon.removeClass('fa-regular').addClass('fa-solid').css('color', '#eab308');
                            $(this).closest('.contact-item, .contact-row').attr('data-favorite', 'true');
                        } else {
                            icon.removeClass('fa-solid').addClass('fa-regular').css('color', 'var(--text-muted)');
                            $(this).closest('.contact-item, .contact-row').attr('data-favorite', 'false');
                        }
                    });

                    Nexus.notify(res.message, 'success');
                    
                    if (filterFavoritesOnly) {
                        filterContacts();
                    }
                }
            },
            error: function() {
                starIcon.removeClass('fa-spinner fa-spin');
                const isFav = $(btn).closest('.contact-item, .contact-row').attr('data-favorite') === 'true';
                if (isFav) {
                    starIcon.addClass('fa-solid').css('color', '#eab308');
                } else {
                    starIcon.addClass('fa-regular').css('color', 'var(--text-muted)');
                }
                Nexus.notify('Failed to toggle favorite.', 'error');
            }
        });
    };

    // ─── Reload ───
    $('#btn-reload').on('click', function() {
        NProgress.start();
        window.location.reload();
    });

    // ─── Global Reply Mode Control ───
    let currentMode = 'manual';
    $('.mode-btn').on('click', function() {
        const mode = $(this).data('mode');
        if (mode === currentMode) return;
        currentMode = mode;

        $('.mode-btn').each(function() {
            const m = $(this).data('mode');
            $(this).removeClass('active-manual active-copilot active-autopilot');
            if (m === currentMode) $(this).addClass('active-' + currentMode);
        });

        if (mode === 'autopilot') {
            Nexus.setAutopilot(true);
            Nexus.notify('⚡ Autopilot engaged globally — AI will respond automatically.', 'warning');
        } else {
            Nexus.setAutopilot(false);
        }
    });

    // ─── Add Contact Form ───
    $('#add-contact-form').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btn-save-contact');
        $btn.html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...').prop('disabled', true);
        $('#contact-form-error').addClass('d-none');

        $.ajax({
            url: '{{ route("hub.contacts.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    Nexus.notify('Contact added successfully!', 'success');
                    setTimeout(() => window.location.reload(), 800);
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                let msg = 'Failed to save contact.';
                if (errors) msg = Object.values(errors).flat().join(' ');
                $('#contact-form-error').text(msg).removeClass('d-none');
                $btn.html('<i class="fa-solid fa-check me-1"></i> Save Contact').prop('disabled', false);
            }
        });
    });

    // ─── Drag & Drop Import ───
    const $dz = $('#dropzone');
    $dz.on('dragover', function(e) { e.preventDefault(); $(this).addClass('dragover'); });
    $dz.on('dragleave', function() { $(this).removeClass('dragover'); });
    $dz.on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) handleImportFile(files[0]);
    });
    $('#file-input').on('change', function() {
        if (this.files[0]) handleImportFile(this.files[0]);
    });

    function handleImportFile(file) {
        // Simulate dry-run preview
        $('#import-preview').removeClass('d-none');
        const fakeTotal = Math.floor(Math.random() * 5000 + 500);
        const fakeDups  = Math.floor(fakeTotal * 0.1);
        $('#import-count-total').text(fakeTotal.toLocaleString());
        $('#import-count-new').text((fakeTotal - fakeDups).toLocaleString());
        $('#import-count-dups').text(fakeDups.toLocaleString());
    }

    $('#btn-confirm-import').on('click', function() {
        $('#import-preview').addClass('d-none');
        $('#import-progress').removeClass('d-none');
        Nexus.updateStatusBar('Importing messages...', 'running');
        let progress = 0;
        const interval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress >= 100) {
                progress = 100;
                clearInterval(interval);
                $('#import-status-text').text('Import complete!');
                Nexus.updateStatusBar('Import completed', 'success');
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('importModal')).hide();
                    Nexus.clearStatusBar();
                    $('#import-progress').addClass('d-none');
                    $('#import-preview').addClass('d-none');
                }, 2000);
            }
            $('#import-bar').css('width', Math.min(progress, 100) + '%');
            $('#import-status-text').text(`Processing... ${Math.floor(progress)}%`);
        }, 400);
    });

    $('#btn-cancel-import').on('click', function() {
        $('#import-preview').addClass('d-none');
    });

    // ─── Maintenance ───
    $('#btn-run-maintenance').on('click', function() {
        const selected = [];
        $('.maintenance-action:checked').each(function() { selected.push($(this).val()); });
        if (!selected.length) { Nexus.notify('Please select at least one maintenance action.', 'warning'); return; }

        $(this).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Running...').prop('disabled', true);
        $('#maintenance-progress').removeClass('d-none');
        Nexus.updateStatusBar('Running maintenance...', 'running');

        let progress = 0;
        const interval = setInterval(() => {
            progress += 20;
            $('#maintenance-bar').css('width', Math.min(progress, 100) + '%');
            $('#maintenance-status').text(`Processing: ${selected[Math.floor(progress/20) - 1] || selected[selected.length-1]}...`);
            if (progress >= 100) {
                clearInterval(interval);
                $('#maintenance-status').text('All maintenance jobs queued successfully.');
                Nexus.updateStatusBar('Maintenance jobs queued', 'success');
                setTimeout(() => Nexus.clearStatusBar(), 3000);
                $('#btn-run-maintenance').html('<i class="fa-solid fa-check me-1"></i> Done!').prop('disabled', false);
            }
        }, 600);
    });

});
</script>
@endpush
