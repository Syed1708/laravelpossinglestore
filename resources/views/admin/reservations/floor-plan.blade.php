@extends(view()->exists('tyro-dashboard::layouts.admin') ? 'tyro-dashboard::layouts.admin' : 'tyro-dashboard::layouts.app')

@section('title', 'Table Floor Plan & Hostess')

@section('breadcrumb')
    <a href="{{ route('tyro-dashboard.index') }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>Floor Plan &amp; Hostess</span>
@endsection

@section('content')
    <script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>

    <style>
        .floor-plan-container {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 20px;
        }

        @media (max-width: 1024px) {
            .floor-plan-container {
                grid-template-columns: 1fr;
            }
        }

        /* Table Grid Cards */
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
        }



        .table-occupied {
            border-color: #ef4444;
            background-color: rgba(239, 68, 68, 0.1);
        }

        .table-reserved {
            border-color: #f59e0b;
            background-color: rgba(245, 158, 11, 0.1);
        }

        .table-available {
            border-color: #10b981;
            background-color: rgba(16, 185, 129, 0.05);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-num {
            font-size: 18px;
            font-weight: 900;
        }

        .table-zone {
            font-size: 10px;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 4px;
            background: var(--border, #334155);
            text-transform: uppercase;
        }

        /* Queue Sidebar */
        /* 🚀 THEME-AWARE QUEUE SIDEBAR (Light & Dark Mode Compatible) */
        .queue-sidebar {
            background-color: var(--card, var(--card-bg, #1e293b));
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 16px;
            padding: 20px;
            height: fit-content;
            color: var(--foreground, var(--text-main, #0f172a));
        }

        /* Individual Booking Card inside Queue */
        .booking-item {
            background-color: var(--muted, var(--bg-color, #f8fafc));
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 12px;
            color: var(--foreground, var(--text-main, #0f172a));
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .booking-item-name {
            font-size: 14px;
            font-weight: 800;
            color: var(--foreground, #0f172a);
        }

        .booking-item-details {
            font-size: 12px;
            color: var(--muted-foreground, #64748b);
            margin-top: 2px;
            font-weight: 600;
        }

        /* Table Cards Theme Adaptation */
        .table-card {
            background-color: var(--card, var(--card-bg, #1e293b));
            border: 2px solid var(--border, #e2e8f0);
            border-radius: 16px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 140px;
            transition: all 0.2s ease;
            color: var(--foreground, #0f172a);
        }

        .btn-seat {
            background: #10b981;
            color: #0f172a;
            font-weight: 900;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            width: 100%;
            margin-top: 8px;
        }

        .btn-seat:hover {
            background: #34d399;
        }
    </style>

    <div class="page-header">
        <div class="page-header-row">
            <div>
                <h1 class="page-title">🗺️ Table Floor Plan &amp; Hostess</h1>
                <p class="page-description">Manage real-time seating, phone reservations, and hostess queue for
                    {{ Carbon\Carbon::parse($date)->format('M d, Y') }}.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="openPhoneBookingModal()" class="btn btn-primary">
                    📞 New Phone Booking
                </button>
            </div>
        </div>
    </div>

    <!-- Date Picker Filter -->
    <div class="card" style="margin-bottom: 1rem;">
        <div class="card-body">
            <form action="{{ route('admin.reservations.floor_plan') }}" method="GET" class="filters-bar">
                <div class="filter-group">
                    <label class="filter-label">Select Date:</label>
                    <input type="date" name="date" class="form-input" value="{{ $date }}"
                        onchange="this.form.submit()">
                </div>
                <a href="{{ route('admin.reservations.floor_plan') }}" class="btn btn-ghost">Today</a>
            </form>
        </div>
    </div>

    <!-- MAIN FLOOR PLAN LAYOUT -->
    <div class="floor-plan-container">

        <!-- LEFT: VISUAL TABLES GRID -->
        <div>
            <h3 style="margin-top: 0; margin-bottom: 15px; font-weight: 800;">🍽️ Physical Tables Overview</h3>
            <div class="tables-grid">
                @foreach ($tables as $table)
                    @php
                        // Find active reservation seated at this table today
                        $activeRes = $reservations->first(
                            fn($r) => $r->table_id === $table->id && $r->status === 'seated',
                        );
                        $upcomingRes = $reservations->first(
                            fn($r) => $r->table_id === $table->id && $r->status === 'confirmed',
                        );

                        $statusClass = 'table-available';
                        $statusText = 'AVAILABLE';
                        if ($activeRes) {
                            $statusClass = 'table-occupied';
                            $statusText = 'OCCUPIED';
                        } elseif ($upcomingRes) {
                            $statusClass = 'table-reserved';
                            $statusText = 'RESERVED';
                        }
                    @endphp

                    <div class="table-card {{ $statusClass }}">
                        <div>
                            <div class="table-header">
                                <span class="table-num">{{ $table->table_number }}</span>
                                <span class="table-zone">{{ $table->zone }}</span>
                            </div>
                            <p style="font-size: 11px; margin: 4px 0; color: var(--muted-foreground);">
                                👥 Capacity: {{ $table->capacity }} seats
                            </p>
                        </div>

                        <div>
                            @if ($activeRes)
                                <div style="font-size: 12px; font-weight: 800; color: #ef4444; margin-bottom: 4px;">
                                    🛋️ {{ $activeRes->customer_name }} ({{ $activeRes->guest_count }}p)
                                </div>
                                <button onclick="updateReservationStatus({{ $activeRes->id }}, 'completed')"
                                    class="btn btn-ghost" style="font-size: 10px; width: 100%; padding: 4px;">
                                    Mark Table Free
                                </button>
                            @elseif($upcomingRes)
                                <div style="font-size: 11px; font-weight: 800; color: #f59e0b;">
                                    ⏰ {{ Carbon\Carbon::parse($upcomingRes->reservation_time)->format('H:i') }} -
                                    {{ $upcomingRes->customer_name }}
                                </div>
                                <button onclick="updateReservationStatus({{ $upcomingRes->id }}, 'seated')"
                                    class="btn-seat">
                                    Seat Guests Now
                                </button>
                            @else
                                <span style="font-size: 11px; font-weight: 800; color: #10b981;">
                                    ● {{ $statusText }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- RIGHT: TODAY'S RESERVATIONS QUEUE -->
        <!-- RIGHT: TODAY'S RESERVATIONS QUEUE (Light & Dark Mode Compatible) -->
        <div class="queue-sidebar">
            <h3 style="margin-top: 0; margin-bottom: 15px; font-weight: 800; font-size: 16px;">
                📅 Bookings Queue ({{ $reservations->count() }})
            </h3>

            @forelse($reservations as $res)
                <div class="booking-item">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <!-- 🚀 Customer Name adapts to theme -->
                            <div class="booking-item-name">{{ $res->customer_name }}</div>
                            <div class="booking-item-details">
                                📞 {{ $res->customer_phone }} | 👥 {{ $res->guest_count }} Guests
                            </div>
                        </div>

                        <span
                            class="badge {{ $res->status === 'seated' ? 'badge-danger' : ($res->status === 'confirmed' ? 'badge-warning' : 'badge-success') }}">
                            {{ Carbon\Carbon::parse($res->reservation_time)->format('H:i') }}
                        </span>
                    </div>

                    @if ($res->special_notes)
                        <p style="font-size: 11px; color: #d97706; margin: 8px 0 0 0; font-weight: bold;">
                            ⚠️ {{ $res->special_notes }}
                        </p>
                    @endif

                    <div style="display: flex; gap: 6px; margin-top: 10px;">
                        @if ($res->status === 'confirmed')
                            <button onclick="updateReservationStatus({{ $res->id }}, 'seated')" class="btn-seat">
                                🛋️ Seat Guests
                            </button>
                            <button onclick="updateReservationStatus({{ $res->id }}, 'cancelled')"
                                class="btn btn-ghost" style="font-size: 11px; color: #ef4444;">
                                Cancel
                            </button>
                        @elseif($res->status === 'seated')
                            <button onclick="updateReservationStatus({{ $res->id }}, 'completed')"
                                class="btn btn-secondary" style="font-size: 11px; width: 100%;">
                                🎉 Complete Booking
                            </button>
                        @else
                            <span
                                style="font-size: 11px; font-weight: bold; color: #10b981; margin-top: 4px; display: inline-block;">
                                ✓ {{ strtoupper($res->status) }}
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <p style="text-align: center; color: var(--muted-foreground, #64748b); font-size: 13px; padding: 20px 0;">
                    No bookings found for {{ $date }}.
                </p>
            @endforelse
        </div>

    </div>

    <!-- NEW PHONE BOOKING MODAL -->
    <div class="modal-overlay" id="phoneBookingModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">📞 Record Phone Reservation</h3>
                <button type="button" class="modal-close" onclick="closeModal('phoneBookingModal')">✕</button>
            </div>
            <form id="phoneBookingForm" onsubmit="handlePhoneBookingSubmit(event)">
                @csrf
                <div class="modal-body" style="display: grid; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Customer Name</label>
                        <input type="text" name="customer_name" class="form-input" required placeholder="e.g. John Doe">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="customer_phone" class="form-input" required
                            placeholder="e.g. +33612345678">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div class="form-group">
                            <label class="form-label">Date</label>
                            <input type="date" name="reservation_date" class="form-input" value="{{ $date }}"
                                required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Time</label>
                            <input type="time" name="reservation_time" class="form-input" value="19:30" required>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div class="form-group">
                            <label class="form-label">Guest Count</label>
                            <input type="number" name="guest_count" class="form-input" value="2" min="1"
                                max="20" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Assign Table (Optional)</label>
                            <select name="table_id" class="form-select">
                                <option value="">Auto-Assign Later</option>
                                @foreach ($tables as $t)
                                    <option value="{{ $t->id }}">{{ $t->table_number }} ({{ $t->capacity }}
                                        seats - {{ $t->zone }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Special Notes / Requests</label>
                        <input type="text" name="special_notes" class="form-input"
                            placeholder="e.g. Birthday, terrace preferred...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        onclick="closeModal('phoneBookingModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm Phone Booking</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            // 🚀 FIX BUG 3: Clear form fields when opening modal
            function openPhoneBookingModal() {
                const form = document.getElementById('phoneBookingForm');
                if (form) form.reset();
                openModal('phoneBookingModal');
            }

            // 🚀 FIX BUG 1: Convert FormData to JSON payload
            async function handlePhoneBookingSubmit(e) {
                e.preventDefault();
                const payload = Object.fromEntries(new FormData(e.target));

                try {
                    const response = await fetch('/admin/api/reservations/phone-booking', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json', // Matches JSON payload
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        closeModal('phoneBookingModal');
                        window.location.reload();
                    } else {
                        alert(data.message || 'Validation failed: Check input fields.');
                    }
                } catch (err) {
                    alert('Network error submitting phone booking.');
                }
            }

            // 🚀 FIX BUG 2: Status Update JS
            async function updateReservationStatus(reservationId, status) {
                try {
                    const response = await fetch(`/admin/api/reservations/${reservationId}/status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            status
                        })
                    });

                    // 🚀 Read raw text first to prevent JSON parse crashes on HTML errors
                    const responseText = await response.text();
                    let data;
                    try {
                        data = JSON.parse(responseText);
                    } catch (e) {
                        console.error("Non-JSON Server Error Response:", responseText);
                        alert(`Server Error (${response.status}): Check browser console for details.`);
                        return;
                    }

                    if (response.ok && data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Error updating reservation status.');
                    }
                } catch (err) {
                    console.error("Fetch Exception:", err);
                    alert('Network Exception: ' + err.message);
                }
            }


            // Reverb WebSockets Real-Time Listener

            const pusherKey = '{{ env('REVERB_APP_KEY') }}';
            if (pusherKey) {
                const pusher = new Pusher(pusherKey, {
                    cluster: '{{ env('REVERB_APP_CLUSTER', 'mt1') }}',
                    wsHost: '{{ env('REVERB_HOST', '127.0.0.1') }}',
                    wsPort: {{ env('REVERB_PORT', 8080) }},
                    forceTLS: false,
                    enabledTransports: ['ws', 'wss']
                });

                const channel = pusher.subscribe('kds-channel');
                channel.bind('reservation-event', function(data) {
                    console.log('⚡ Live Reservation Event:', data.action, data.reservation);
                    // Reloads table grid or hostess queue instantly!
                    window.location.reload();
                });

            }
        </script>
    @endpush
@endsection
