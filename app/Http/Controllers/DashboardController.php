<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Equipment;
use App\Models\EquipmentRequest;
use App\Models\Event;
use App\Models\Facility;
use App\Models\FacilityReservation;
use App\Models\Role;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Unified dashboard — renders all modules dynamically based on
     * the ?section= query parameter and the user's role.
     */
    public function index(Request $request)
    {
        $user    = Auth::user();
        $section = $request->get('section', 'overview');
        $search  = $request->get('search', '');

        $data = [
            'pageTitle'      => 'Dashboard',
            'currentSection' => $section,
            'search'         => $search,
        ];

        // Load section-specific data
        match ($section) {
            'facilities'       => $this->loadFacilities($data, $request),
            'equipment'        => $this->loadEquipment($data, $request),
            'events'           => $this->loadEvents($data, $request, $user),
            'announcements'    => $this->loadAnnouncements($data, $request),
            'users'            => $this->loadUsers($data, $request, $user),
            'inventory'        => $this->loadInventory($data, $request, $user),
            'manage-bookings'  => $this->loadManageBookings($data, $request),
            'manage-equipment' => $this->loadManageEquipment($data, $request),
            'reports'          => $this->loadReports($data, $request),
            'calendar'         => null, // Calendar loads data via API
            default            => $this->loadOverview($data, $user),
        };

        return view('dashboard.index', $data);
    }

    // ─── OVERVIEW ────────────────────────────────────────────────────────

    protected function loadOverview(array &$data, $user): void
    {
        $data['upcomingEvents'] = Event::active()
            ->where('event_date', '>=', now())
            ->orderBy('event_date')
            ->limit(5)
            ->get();

        $data['latestAnnouncements'] = Announcement::active()
            ->whereNull('deleted_at')
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        if ($user->hasPermission('dashboard.admin_stats')) {
            $data['stats'] = [
                'total_users'          => User::whereNull('deleted_at')->count(),
                'active_users'         => User::active()->count(),
                'total_facilities'     => Facility::count(),
                'pending_reservations' => FacilityReservation::pending()->count(),
                'total_equipment'      => Equipment::count(),
                'pending_borrows'      => EquipmentRequest::pending()->count(),
                'upcoming_events'      => Event::active()->count(),
                'total_announcements'  => Announcement::active()->whereNull('deleted_at')->count(),
            ];
        }

        $data['myReservations'] = FacilityReservation::where('user_id', $user->id)
            ->with('facility')
            ->latest()
            ->limit(5)
            ->get();

        $data['myBorrows'] = EquipmentRequest::where('user_id', $user->id)
            ->with('equipment')
            ->latest()
            ->limit(5)
            ->get();
    }

    // ─── FACILITIES ──────────────────────────────────────────────────────

    protected function loadFacilities(array &$data, Request $request): void
    {
        $query  = Facility::query();
        $status = $request->get('status', '');

        if ($data['search']) {
            $query->where(function ($q) use ($data) {
                $q->where('name', 'like', "%{$data['search']}%")
                  ->orWhere('location', 'like', "%{$data['search']}%");
            });
        }
        if ($status) {
            $query->where('status', $status);
        }

        $data['facilities']   = $query->orderBy('name')->paginate(15);
        $data['statusFilter'] = $status;
    }

    // ─── EQUIPMENT ───────────────────────────────────────────────────────

    protected function loadEquipment(array &$data, Request $request): void
    {
        $query    = Equipment::query();
        $category = $request->get('category', '');

        if ($data['search']) {
            $query->where('name', 'like', "%{$data['search']}%");
        }
        if ($category) {
            $query->where('category', $category);
        }

        $data['equipment']      = $query->orderBy('name')->paginate(15);
        $data['categories']     = Equipment::select('category')->distinct()->pluck('category');
        $data['categoryFilter'] = $category;
    }

    // ─── EVENTS ──────────────────────────────────────────────────────────

    protected function loadEvents(array &$data, Request $request, $user): void
    {
        $query  = Event::withCount('registrations');
        $status = $request->get('status', '');

        if ($data['search']) {
            $query->where('title', 'like', "%{$data['search']}%");
        }
        if ($status) {
            $query->where('status', $status);
        }

        $data['events']       = $query->orderByDesc('event_date')->paginate(15);
        $data['statusFilter'] = $status;
    }

    // ─── ANNOUNCEMENTS ───────────────────────────────────────────────────

    protected function loadAnnouncements(array &$data, Request $request): void
    {
        $query    = Announcement::active()->whereNull('deleted_at')->with('author');
        $priority = $request->get('priority', '');

        if ($data['search']) {
            $query->where('title', 'like', "%{$data['search']}%");
        }
        if ($priority) {
            $query->where('priority', $priority);
        }

        $data['announcements']  = $query->orderByDesc('is_pinned')->orderByDesc('created_at')->paginate(15);
        $data['priorityFilter'] = $priority;
    }

    // ─── USERS (Admin) ───────────────────────────────────────────────────

    protected function loadUsers(array &$data, Request $request, $user): void
    {
        if (!$user->hasPermission('user.view')) {
            abort(403);
        }

        $query = User::with(['role', 'department'])->whereNull('deleted_at');
        $role  = $request->get('role', '');

        if ($data['search']) {
            $query->where(function ($q) use ($data) {
                $q->where('first_name', 'like', "%{$data['search']}%")
                  ->orWhere('last_name', 'like', "%{$data['search']}%")
                  ->orWhere('email', 'like', "%{$data['search']}%")
                  ->orWhere('employee_id', 'like', "%{$data['search']}%");
            });
        }
        if ($role) {
            $query->where('role_id', $role);
        }

        $data['users']      = $query->latest()->paginate(15);
        $data['roles']      = Role::all();
        $data['roleFilter'] = $role;
    }

    // ─── INVENTORY (Admin CRUD) ──────────────────────────────────────────

    protected function loadInventory(array &$data, Request $request, $user): void
    {
        if (!$user->hasPermission('equipment.manage')) {
            abort(403);
        }

        $query  = Equipment::query();
        $status = $request->get('status', '');

        if ($data['search']) {
            $query->where(function ($q) use ($data) {
                $q->where('name', 'like', "%{$data['search']}%")
                  ->orWhere('category', 'like', "%{$data['search']}%");
            });
        }
        if ($status) {
            $query->where('status', $status);
        }

        $data['inventoryItems'] = $query->orderBy('name')->paginate(15);
        $data['statusFilter']   = $status;
    }

    // ─── MANAGE BOOKINGS (Admin/Staff) ───────────────────────────────────

    protected function loadManageBookings(array &$data, Request $request): void
    {
        $user = Auth::user();
        if (!$user->hasPermission('facility.manage')) {
            abort(403);
        }

        $query  = FacilityReservation::with(['user', 'facility']);
        $status = $request->get('status', '');

        if ($data['search']) {
            $query->whereHas('user', function ($q) use ($data) {
                $q->where('first_name', 'like', "%{$data['search']}%")
                  ->orWhere('last_name', 'like', "%{$data['search']}%");
            });
        }
        if ($status) {
            $query->where('status', $status);
        }

        $data['reservations'] = $query->latest()->paginate(15);
        $data['statusFilter'] = $status;
    }

    // ─── MANAGE EQUIPMENT (Admin/Staff) ──────────────────────────────────

    protected function loadManageEquipment(array &$data, Request $request): void
    {
        $user = Auth::user();
        if (!$user->hasPermission('equipment.manage')) {
            abort(403);
        }

        $query  = EquipmentRequest::with(['user', 'equipment']);
        $status = $request->get('status', '');

        if ($data['search']) {
            $query->whereHas('user', function ($q) use ($data) {
                $q->where('first_name', 'like', "%{$data['search']}%")
                  ->orWhere('last_name', 'like', "%{$data['search']}%");
            });
        }
        if ($status) {
            $query->where('status', $status);
        }

        $data['equipmentRequests'] = $query->latest()->paginate(15);
        $data['statusFilter']      = $status;
    }

    // ─── REPORTS (Admin) ─────────────────────────────────────────────────

    protected function loadReports(array &$data, Request $request): void
    {
        $user = Auth::user();
        if (!$user->hasPermission('report.view')) {
            abort(403);
        }

        $reportType = $request->get('report', 'facility-usage');
        $data['reportType'] = $reportType;

        match ($reportType) {
            'event-engagement' => $this->loadEventEngagement($data, $request),
            'audit-log'        => $this->loadAuditLog($data, $request),
            default            => $this->loadFacilityUsage($data, $request),
        };
    }

    protected function loadFacilityUsage(array &$data, Request $request): void
    {
        $from = $request->get('from', now()->subMonth()->format('Y-m-d'));
        $to   = $request->get('to', now()->format('Y-m-d'));

        $data['facilityStats'] = FacilityReservation::select(
                'facility_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved')
            )
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('facility_id')
            ->with('facility')
            ->get();

        $data['dateFrom'] = $from;
        $data['dateTo']   = $to;
    }

    protected function loadEventEngagement(array &$data, Request $request): void
    {
        $from = $request->get('from', now()->subMonth()->format('Y-m-d'));
        $to   = $request->get('to', now()->format('Y-m-d'));

        $data['eventStats'] = Event::withCount('registrations')
            ->whereBetween('event_date', [$from, $to])
            ->orderByDesc('event_date')
            ->get();

        $data['dateFrom'] = $from;
        $data['dateTo']   = $to;
    }

    protected function loadAuditLog(array &$data, Request $request): void
    {
        $query = AuditLog::with('user')->latest();

        if ($data['search']) {
            $query->where('action', 'like', "%{$data['search']}%");
        }

        $data['auditLogs'] = $query->paginate(20);
    }
}
