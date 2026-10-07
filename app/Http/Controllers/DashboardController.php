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
            'facilities'                         => $this->loadFacilities($data, $request),
            'equipment'                          => $this->loadEquipment($data, $request),
            'booked-facilities', 'my-bookings'   => $this->loadBookedFacilities($data, $request, $user),
            'borrowed-equipment', 'my-equipment' => $this->loadBorrowedEquipment($data, $request, $user),
            'history'                            => $this->loadHistory($data, $request, $user),
            'events'                             => $this->loadEvents($data, $request, $user),
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

        if ($user->hasPermission('facility.approve')) {
            $data['needsActionBookings'] = FacilityReservation::with(['facility', 'user'])
                ->pending()
                ->orderBy('reservation_date')
                ->limit(5)
                ->get();
            $data['needsActionEquipment'] = EquipmentRequest::with(['equipment', 'user'])
                ->pending()
                ->orderBy('date_needed')
                ->limit(5)
                ->get();
        }
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

        // Facilities occupied right now by an approved booking (others are open)
        $data['occupiedMap'] = FacilityReservation::ongoing()
            ->whereIn('facility_id', $data['facilities']->pluck('id'))
            ->get()
            ->keyBy('facility_id');

        $data['myActiveReservations'] = FacilityReservation::where('user_id', Auth::id())
            ->with('facility')
            ->whereIn('status', ['pending', 'approved'])
            ->notEnded()
            ->get();
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

        $data['myActiveEquipment'] = EquipmentRequest::where('user_id', Auth::id())
            ->with('equipment')
            ->active()
            ->latest()
            ->get();
    }

    // ─── BOOKED FACILITIES (User's & Campus Bookings) ───────────────────

    protected function loadBookedFacilities(array &$data, Request $request, $user): void
    {
        $status         = $request->get('status', '');
        $isAdminOrStaff = $user->hasPermission('facility.approve') || $user->hasPermission('equipment.approve');
        $defaultScope   = $isAdminOrStaff ? 'all' : 'my';
        $scope          = $request->get('scope', $defaultScope);

        $applySearch = function ($query) use ($data) {
            if ($data['search']) {
                $query->where(function ($q) use ($data) {
                    $q->where('title', 'like', "%{$data['search']}%")
                      ->orWhere('description', 'like', "%{$data['search']}%")
                      ->orWhereHas('facility', fn($fq) => $fq->where('name', 'like', "%{$data['search']}%"))
                      ->orWhereHas('user', function ($uq) use ($data) {
                          $uq->where('first_name', 'like', "%{$data['search']}%")
                             ->orWhere('last_name', 'like', "%{$data['search']}%")
                             ->orWhere('employee_id', 'like', "%{$data['search']}%");
                      });
                });
            }
            return $query;
        };

        // Current: pending or approved bookings whose time has not ended yet
        $current = FacilityReservation::with(['facility', 'user']);
        if ($scope === 'my') {
            $current->where('user_id', $user->id);
        }
        $current->whereIn('status', ['pending', 'approved'])->notEnded();
        $applySearch($current);
        if (in_array($status, ['pending', 'approved'], true)) {
            $current->where('status', $status);
        }

        // Completed: approved bookings whose time has ended OR status is completed (facility is open again)
        $completed = FacilityReservation::with(['facility', 'user']);
        if ($scope === 'my') {
            $completed->where('user_id', $user->id);
        }
        $completed->where(function ($q) {
            $q->where('status', 'completed')
              ->orWhere(function ($q2) {
                  $q2->where('status', 'approved')->ended();
              });
        });
        $applySearch($completed);

        $data['myReservationsList']    = $current->orderBy('reservation_date')->orderBy('time_start')
            ->paginate(10, ['*'], 'page');
        $data['completedReservations'] = $completed->orderByDesc('reservation_date')->orderByDesc('time_start')
            ->paginate(10, ['*'], 'completed_page');
        $data['statusFilter']          = $status;
        $data['scope']                 = $scope;
        $data['canViewAll']            = true;
    }

    // ─── BORROWED EQUIPMENT (User's & Campus Requests) ───────────────────

    protected function loadBorrowedEquipment(array &$data, Request $request, $user): void
    {
        $status         = $request->get('status', '');
        $isAdminOrStaff = $user->hasPermission('equipment.approve') || $user->hasPermission('facility.approve');
        $defaultScope   = $isAdminOrStaff ? 'all' : 'my';
        $scope          = $request->get('scope', $defaultScope);

        $applySearch = function ($query) use ($data) {
            if ($data['search']) {
                $query->where(function ($q) use ($data) {
                    $q->where('purpose', 'like', "%{$data['search']}%")
                      ->orWhereHas('equipment', fn($eq) => $eq->where('name', 'like', "%{$data['search']}%"))
                      ->orWhereHas('user', function ($uq) use ($data) {
                          $uq->where('first_name', 'like', "%{$data['search']}%")
                             ->orWhere('last_name', 'like', "%{$data['search']}%")
                             ->orWhere('employee_id', 'like', "%{$data['search']}%");
                      });
                });
            }
            return $query;
        };

        // Current: pending / approved / borrowed
        $current = EquipmentRequest::with(['equipment', 'user']);
        if ($scope === 'my') {
            $current->where('user_id', $user->id);
        }

        if ($status === 'borrowed') {
            $current->whereIn('status', ['borrowed', 'approved']);
        } elseif ($status === 'rejected') {
            $current->where('status', 'rejected');
        } elseif (in_array($status, EquipmentRequest::ACTIVE_STATUSES, true)) {
            $current->where('status', $status);
        } else {
            $current->whereIn('status', ['borrowed', 'approved', 'pending']);
        }
        $applySearch($current);

        // Returned equipment (confirmed by staff)
        $returned = EquipmentRequest::with(['equipment', 'user', 'confirmedBy'])->returned();
        if ($scope === 'my') {
            $returned->where('user_id', $user->id);
        }
        $applySearch($returned);

        $data['myEquipmentList']   = $current->orderByDesc('created_at')->paginate(10, ['*'], 'page');
        $data['returnedEquipment'] = $returned->orderByDesc('returned_at')->paginate(10, ['*'], 'returned_page');
        $data['statusFilter']      = $status;
        $data['scope']             = $scope;
        $data['canViewAll']        = true;
    }

    // ─── HISTORY (Borrowed Equipment & Booked Facilities) ──────────────────

    /**
     * History of borrowed equipment & booked facilities for all users.
     * Users can also filter by scope=my to see only their own history.
     */
    protected function loadHistory(array &$data, Request $request, $user): void
    {
        $tab    = $request->get('tab') === 'facilities' ? 'facilities' : 'equipment';
        $status = $request->get('status', '');
        $from   = $request->get('from', '');
        $to     = $request->get('to', '');
        $scope  = $request->get('scope', 'all'); // 'all' or 'my'
        $search = $data['search'];

        // Everyone can view campus-wide history as requested ("history for all user")
        $canViewAll = true;

        $userSearch = function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$search}%")
              ->orWhere('employee_id', 'like', "%{$search}%");
        };

        if ($tab === 'equipment') {
            $query = EquipmentRequest::with(['equipment', 'user', 'confirmedBy']);
            if ($scope === 'my') {
                $query->where('user_id', $user->id);
            }
            if ($search) {
                $query->where(function ($q) use ($search, $canViewAll, $userSearch) {
                    $q->where('purpose', 'like', "%{$search}%")
                      ->orWhereHas('equipment', fn($eq) => $eq->where('name', 'like', "%{$search}%"));
                    if ($canViewAll) {
                        $q->orWhereHas('user', $userSearch);
                    }
                });
            }
            if ($status) {
                $query->where('status', $status);
            }
            if ($from) {
                $query->whereDate('date_needed', '>=', $from);
            }
            if ($to) {
                $query->whereDate('date_needed', '<=', $to);
            }
            $data['historyItems'] = $query->orderByDesc('created_at')->paginate(20);
        } else {
            $query = FacilityReservation::with(['facility', 'user']);
            if ($scope === 'my') {
                $query->where('user_id', $user->id);
            }
            if ($search) {
                $query->where(function ($q) use ($search, $canViewAll, $userSearch) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhereHas('facility', fn($fq) => $fq->where('name', 'like', "%{$search}%"));
                    if ($canViewAll) {
                        $q->orWhereHas('user', $userSearch);
                    }
                });
            }
            if ($status === 'completed') {
                $query->where(function ($q) {
                    $q->where('status', 'completed')
                      ->orWhere(function ($q2) {
                          $q2->where('status', 'approved')->ended();
                      });
                });
            } elseif ($status === 'upcoming') {
                $query->where('status', 'approved')->notEnded();
            } elseif ($status) {
                $query->where('status', $status);
            }
            if ($from) {
                $query->whereDate('reservation_date', '>=', $from);
            }
            if ($to) {
                $query->whereDate('reservation_date', '<=', $to);
            }
            $data['historyItems'] = $query->orderByDesc('reservation_date')->orderByDesc('time_start')->paginate(20);
        }

        $data['historyTab']   = $tab;
        $data['canViewAll']   = $canViewAll;
        $data['historyScope'] = $scope;
        $data['statusFilter'] = $status;
        $data['dateFrom']     = $from;
        $data['dateTo']       = $to;
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
        $role   = $request->get('role', '');
        $status = $request->get('status', '');

        if ($data['search']) {
            $query->where(function ($q) use ($data) {
                $q->where('first_name', 'like', "%{$data['search']}%")
                  ->orWhere('last_name', 'like', "%{$data['search']}%")
                  ->orWhere('email', 'like', "%{$data['search']}%")
                  ->orWhere('employee_id', 'like', "%{$data['search']}%")
                  ->orWhereHas('department', function ($dq) use ($data) {
                      $dq->where('name', 'like', "%{$data['search']}%")
                         ->orWhere('code', 'like', "%{$data['search']}%");
                  });
            });
        }
        if ($role) {
            $query->where('role_id', $role);
        }
        if ($status === 'inactive_auto') {
            $query->where('status', 'inactive')->where('deactivation_reason', 'inactivity');
        } elseif ($status === 'inactive_admin') {
            $query->where('status', 'inactive')->where('deactivation_reason', 'admin');
        } elseif ($status) {
            $query->where('status', $status);
        }

        $data['users']        = $query->latest()->paginate(15);
        $data['roles']        = Role::all();
        $data['roleFilter']   = $role;
        $data['statusFilter'] = $status;
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
        if (!$user->hasPermission('facility.approve')) {
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

        // Sort: pending rows soonest-first; otherwise latest
        $isPendingFilter = ($status === 'pending' || $status === '');
        if ($isPendingFilter) {
            $query->orderBy('reservation_date')->orderBy('time_start');
        } else {
            $query->latest();
        }

        $reservations = $query->paginate(15);
        $data['reservations'] = $reservations;
        $data['statusFilter'] = $status;

        // Conflict map: for each pending reservation, check against approved ones (one batch query)
        $pendingIds    = $reservations->where('status', 'pending')->pluck('id')->toArray();
        $conflictMap   = [];
        if (!empty($pendingIds)) {
            // Load all approved reservations for the same facilities on the same dates
            $facilityIds = $reservations->where('status', 'pending')->pluck('facility_id')->unique()->toArray();
            $dates       = $reservations->where('status', 'pending')->pluck('reservation_date')->map(fn($d) => $d->toDateString())->unique()->toArray();
            $approved    = FacilityReservation::whereIn('facility_id', $facilityIds)
                ->whereIn(DB::raw('DATE(reservation_date)'), $dates)
                ->where('status', 'approved')
                ->with('user')
                ->get();

            foreach ($reservations->where('status', 'pending') as $res) {
                $conflict = $approved->first(function ($a) use ($res) {
                    return $a->facility_id === $res->facility_id
                        && $a->reservation_date->toDateString() === $res->reservation_date->toDateString()
                        && $a->time_start < $res->time_end
                        && $a->time_end   > $res->time_start;
                });
                $conflictMap[$res->id] = $conflict
                    ? 'Overlaps with ' . $conflict->user->full_name . ' (' . $conflict->title . ')'
                    : 'No conflict';
            }
        }
        $data['conflictMap'] = $conflictMap;
    }

    // ─── MANAGE EQUIPMENT (Admin/Staff) ──────────────────────────────────

    protected function loadManageEquipment(array &$data, Request $request): void
    {
        $user = Auth::user();
        if (!$user->hasPermission('equipment.approve')) {
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

        // Sort: pending rows soonest-first; otherwise latest
        $isPendingFilter = ($status === 'pending' || $status === '');
        if ($isPendingFilter) {
            $query->orderBy('date_needed');
        } else {
            $query->latest();
        }

        $eqRequests = $query->paginate(15);
        $data['equipmentRequests'] = $eqRequests;
        $data['statusFilter']      = $status;

        // Stock badge: for each pending row, check if available_quantity < requested quantity
        $stockMap = [];
        foreach ($eqRequests->where('status', 'pending') as $req) {
            $avail = $req->equipment->available_quantity ?? 0;
            $shortfall = $req->quantity - $avail;
            $stockMap[$req->id] = $shortfall > 0 ? "Short by {$shortfall}" : 'Stock OK';
        }
        $data['stockMap'] = $stockMap;
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
