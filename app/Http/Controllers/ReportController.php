<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\FacilityReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function facilityUsage(Request $request)
    {
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));

        $usageData = DB::table('facilities as f')
            ->leftJoin('facility_reservations as r', function ($join) use ($month, $year) {
                $join->on('f.id', '=', 'r.facility_id')
                    ->whereMonth('r.reservation_date', $month)
                    ->whereYear('r.reservation_date', $year);
            })
            ->select(
                'f.name',
                DB::raw('COUNT(r.id) as total_bookings'),
                DB::raw("SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_bookings")
            )
            ->groupBy('f.id', 'f.name')
            ->orderByDesc('total_bookings')
            ->get();

        return view('reports.facility_usage', [
            'pageTitle' => 'Facility Usage Report',
            'breadcrumb' => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Reports'],
                ['label' => 'Facility Usage'],
            ],
            'usageData' => $usageData,
            'month'     => $month,
            'year'      => $year,
        ]);
    }

    public function eventEngagement(Request $request)
    {
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));

        $eventsData = Event::whereMonth('event_date', $month)
            ->whereYear('event_date', $year)
            ->select('title', 'event_date', 'max_participants')
            ->withCount([
                'registrations as reg_count',
                'registrations as attended_count' => fn($q) => $q->where('has_attended', true),
            ])
            ->orderBy('event_date')
            ->get();

        return view('reports.event_engagement', [
            'pageTitle' => 'Event Engagement Report',
            'breadcrumb' => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Reports'],
                ['label' => 'Event Engagement'],
            ],
            'eventsData' => $eventsData,
            'month'      => $month,
            'year'       => $year,
        ]);
    }

    public function auditLog(Request $request)
    {
        $query = AuditLog::with('user');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('table_name', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($q2) => $q2->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"));
            });
        }

        $logs = $query->orderByDesc('created_at')
            ->paginate(config('icoep.items_per_page'));

        return view('reports.audit_log', [
            'pageTitle' => 'System Audit Logs',
            'breadcrumb' => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Reports'],
                ['label' => 'Audit Logs'],
            ],
            'logs'   => $logs,
            'search' => $search,
        ]);
    }
}
