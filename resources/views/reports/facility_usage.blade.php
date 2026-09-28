@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Facility Usage Report</h1>
        <p class="page-subtitle">Monthly analysis of facility booking activity.</p>
    </div>
    <button onclick="window.print()" class="btn btn-secondary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        Print Report
    </button>
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="" class="search-bar" style="margin-bottom: 1.5rem;">
            <div class="filter-group">
                <select name="month" class="form-control" style="width: auto;">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                    @endfor
                </select>
                <select name="year" class="form-control" style="width: auto;">
                    @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Generate</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Facility</th><th>Total Bookings</th><th>Approved</th><th>Utilization</th></tr></thead>
                <tbody>
                @forelse($usageData as $row)
                    <tr>
                        <td style="font-weight: 500;">{{ $row->name }}</td>
                        <td>{{ $row->total_bookings }}</td>
                        <td>{{ $row->approved_bookings ?? 0 }}</td>
                        <td>
                            @php $pct = $row->total_bookings > 0 ? round(($row->approved_bookings / $row->total_bookings) * 100) : 0; @endphp
                            <div style="display: flex; align-items: center; gap: .5rem;">
                                <div style="flex: 1; height: 8px; background: #fff; border-radius: 4px; overflow: hidden;">
                                    <div style="width: {{ $pct }}%; height: 100%; background: var(--color-brand-600); border-radius: 4px;"></div>
                                </div>
                                <span class="text-muted" style="font-size: 0.85em;">{{ $pct }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted" style="padding: 2rem;">No data for the selected period.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
