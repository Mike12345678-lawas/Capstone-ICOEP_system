@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Event Engagement Report</h1>
        <p class="page-subtitle">Monthly event registration and attendance analytics.</p>
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
                <thead><tr><th>Event Title</th><th>Date</th><th>Registrations</th><th>Attended</th><th>Attendance Rate</th></tr></thead>
                <tbody>
                @forelse($eventsData as $ev)
                    <tr>
                        <td style="font-weight: 500;">{{ $ev->title }}</td>
                        <td>{{ $ev->event_date->format('M d, Y') }}</td>
                        <td>{{ $ev->reg_count }}{{ $ev->max_participants > 0 ? ' / ' . $ev->max_participants : '' }}</td>
                        <td>{{ $ev->attended_count }}</td>
                        <td>
                            @php $rate = $ev->reg_count > 0 ? round(($ev->attended_count / $ev->reg_count) * 100) : 0; @endphp
                            <div style="display: flex; align-items: center; gap: .5rem;">
                                <div style="flex: 1; height: 8px; background: #fff; border-radius: 4px; overflow: hidden;">
                                    <div style="width: {{ $rate }}%; height: 100%; background: #10b981; border-radius: 4px;"></div>
                                </div>
                                <span class="text-muted" style="font-size: 0.85em;">{{ $rate }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted" style="padding: 2rem;">No events for the selected period.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
