@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Reports</h1>
        <p class="page-subtitle">Analytics and audit data for campus operations.</p>
    </div>
</div>

{{-- Report Type Tabs --}}
<div class="card animate-fade-in-up" style="animation-delay: 50ms;">
    <div class="card-body" style="padding: .75rem 1rem;">
        <div class="filter-group">
            <a href="{{ url('/dashboard?section=reports&report=facility-usage') }}" class="filter-btn {{ ($reportType ?? '') === 'facility-usage' ? 'active' : '' }}">Facility Usage</a>
            <a href="{{ url('/dashboard?section=reports&report=event-engagement') }}" class="filter-btn {{ ($reportType ?? '') === 'event-engagement' ? 'active' : '' }}">Event Engagement</a>
            <a href="{{ url('/dashboard?section=reports&report=audit-log') }}" class="filter-btn {{ ($reportType ?? '') === 'audit-log' ? 'active' : '' }}">Audit Log</a>
        </div>
    </div>
</div>

@if(($reportType ?? 'facility-usage') === 'facility-usage')
<div class="card animate-fade-in-up" style="animation-delay: 100ms; margin-top: 1rem;">
    <div class="card-header"><h3 class="card-title">Facility Usage Report</h3></div>
    <div class="card-body">
        <form method="GET" action="{{ url('/dashboard') }}" class="search-bar report-filter-bar">
            <input type="hidden" name="section" value="reports">
            <input type="hidden" name="report" value="facility-usage">
            <div class="form-group mb-0">
                <input type="date" name="from" class="form-control report-date-input" value="{{ $dateFrom ?? '' }}">
            </div>
            <span class="text-muted">to</span>
            <div class="form-group mb-0">
                <input type="date" name="to" class="form-control report-date-input" value="{{ $dateTo ?? '' }}">
            </div>
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        </form>

        <div class="report-chart-container">
            <canvas id="facilityUsageChart"></canvas>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th scope="col">Facility</th><th scope="col">Total Reservations</th><th scope="col">Approved</th><th scope="col">Usage Rate</th></tr></thead>
                <tbody>
                @forelse($facilityStats ?? [] as $stat)
                    @php $pct = $stat->total > 0 ? round(($stat->approved / $stat->total) * 100) : 0; @endphp
                    <tr>
                        <td class="font-semibold">{{ $stat->facility->name ?? '—' }}</td>
                        <td>{{ $stat->total }}</td>
                        <td>{{ $stat->approved }}</td>
                        <td>
                            <div class="progress-bar-wrapper">
                                <div class="progress-bar-track">
                                    <div class="progress-bar-fill progress-bar-fill-brand" style="width: {{ $pct }}%;"></div>
                                </div>
                                <span class="progress-bar-label">{{ $pct }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted empty-table-cell">No data for the selected period.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('facilityUsageChart');
    if (!ctx) return;
    
    const labels = {!! json_encode($facilityStats->map(fn($s) => $s->facility->name ?? '—')) !!};
    const totalData = {!! json_encode($facilityStats->map(fn($s) => $s->total)) !!};
    const approvedData = {!! json_encode($facilityStats->map(fn($s) => $s->approved)) !!};

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Total Requests',
                    data: totalData,
                    backgroundColor: 'rgba(56, 189, 248, 0.5)',
                    borderColor: 'rgb(56, 189, 248)',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Approved',
                    data: approvedData,
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 1,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { color: document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#475569' } }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: document.documentElement.classList.contains('dark') ? '#334155' : '#e2e8f0' }, ticks: { stepSize: 1, color: document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b' } },
                x: { grid: { display: false }, ticks: { color: document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b' } }
            }
        }
    });
});
</script>
@endpush

@elseif($reportType === 'event-engagement')
<div class="card animate-fade-in-up" style="animation-delay: 100ms; margin-top: 1rem;">
    <div class="card-header"><h3 class="card-title">Event Engagement Report</h3></div>
    <div class="card-body">
        <form method="GET" action="{{ url('/dashboard') }}" class="search-bar report-filter-bar">
            <input type="hidden" name="section" value="reports">
            <input type="hidden" name="report" value="event-engagement">
            <div class="form-group mb-0">
                <input type="date" name="from" class="form-control report-date-input" value="{{ $dateFrom ?? '' }}">
            </div>
            <span class="text-muted">to</span>
            <div class="form-group mb-0">
                <input type="date" name="to" class="form-control report-date-input" value="{{ $dateTo ?? '' }}">
            </div>
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        </form>

        <div class="report-chart-container">
            <canvas id="eventEngagementChart"></canvas>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th scope="col">Event</th><th scope="col">Date</th><th scope="col">Type</th><th scope="col">Registered</th><th scope="col">Capacity</th><th scope="col">Fill Rate</th></tr></thead>
                <tbody>
                @forelse($eventStats ?? [] as $ev)
                    @php $rate = ($ev->max_participants > 0) ? round(($ev->registrations_count / $ev->max_participants) * 100) : 0; @endphp
                    <tr>
                        <td class="font-semibold">{{ $ev->title }}</td>
                        <td>{{ $ev->event_date->format('M d, Y') }}</td>
                        <td><span class="badge badge-general">{{ ucfirst($ev->event_type) }}</span></td>
                        <td>{{ $ev->registrations_count }}</td>
                        <td>{{ $ev->max_participants > 0 ? $ev->max_participants : '∞' }}</td>
                        <td>
                            <div class="progress-bar-wrapper">
                                <div class="progress-bar-track">
                                    <div class="progress-bar-fill progress-bar-fill-success" style="width: {{ min($rate, 100) }}%;"></div>
                                </div>
                                <span class="progress-bar-label">{{ $rate }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted empty-table-cell">No events for the selected period.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('eventEngagementChart');
    if (!ctx) return;
    
    const labels = {!! json_encode($eventStats->map(fn($e) => $e->title)) !!};
    const registeredData = {!! json_encode($eventStats->map(fn($e) => $e->registrations_count)) !!};
    
    // Sort events by date ascending for the chart
    const reversedLabels = [...labels].reverse();
    const reversedData = [...registeredData].reverse();

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: reversedLabels,
            datasets: [
                {
                    label: 'Registrations',
                    data: reversedData,
                    borderColor: 'rgb(14, 165, 233)',
                    backgroundColor: 'rgba(14, 165, 233, 0.2)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgb(14, 165, 233)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgb(14, 165, 233)'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { color: document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#475569' } }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: document.documentElement.classList.contains('dark') ? '#334155' : '#e2e8f0' }, ticks: { stepSize: 1, color: document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b' } },
                x: { grid: { display: false }, ticks: { color: document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b' } }
            }
        }
    });
});
</script>
@endpush

@elseif($reportType === 'audit-log')
<div class="card animate-fade-in-up" style="animation-delay: 100ms; margin-top: 1rem;">
    <div class="card-header"><h3 class="card-title">Audit Log</h3></div>
    <div class="card-body">
        <form method="GET" action="{{ url('/dashboard') }}" class="search-bar report-filter-bar">
            <input type="hidden" name="section" value="reports">
            <input type="hidden" name="report" value="audit-log">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search by action..." value="{{ $search }}">
            </div>
        </form>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th scope="col">User</th><th scope="col">Action</th><th scope="col">Table</th><th scope="col">Record ID</th><th scope="col">IP Address</th><th scope="col">Date</th></tr></thead>
                <tbody>
                @forelse($auditLogs ?? [] as $log)
                    <tr>
                        <td class="font-semibold">{{ $log->user->full_name ?? 'System' }}</td>
                        <td><code>{{ $log->action }}</code></td>
                        <td>{{ $log->table_name }}</td>
                        <td>{{ $log->record_id ?: '—' }}</td>
                        <td><code>{{ $log->ip_address }}</code></td>
                        <td>{{ $log->created_at->format('M d, Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted empty-table-cell">No audit logs found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($auditLogs) && $auditLogs instanceof \Illuminate\Pagination\LengthAwarePaginator)
            {{ $auditLogs->withQueryString()->links('partials.pagination') }}
        @endif
    </div>
</div>
@endif
