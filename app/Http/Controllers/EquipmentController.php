<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Equipment;
use App\Models\EquipmentRequest;
use App\Models\Notification;
use App\Services\ResourceSolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EquipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Equipment::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        $equipment = $query->orderBy('name')->paginate(config('icoep.items_per_page'));
        $categories = Equipment::select('category')->distinct()->orderBy('category')->pluck('category');

        return view('equipment.index', [
            'pageTitle'      => 'Equipment Inventory',
            'breadcrumb'     => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Equipment'],
            ],
            'equipment'      => $equipment,
            'categories'     => $categories,
            'search'         => $search,
            'categoryFilter' => $category,
        ]);
    }

    public function show(Equipment $equipment)
    {
        $activeRequests = $equipment->requests()
            ->with('user')
            ->whereIn('status', ['pending', 'approved', 'borrowed'])
            ->orderByDesc('created_at')
            ->get();

        return view('equipment.show', [
            'pageTitle' => $equipment->name,
            'breadcrumb' => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Equipment', 'url' => '/dashboard?section=equipment'],
                ['label' => $equipment->name],
            ],
            'equipment'      => $equipment,
            'activeRequests' => $activeRequests,
        ]);
    }

    // ─── Admin CRUD for Equipment Items ──────────────────────────────────

    public function create()
    {
        $categories = Equipment::select('category')->distinct()->orderBy('category')->pluck('category');

        return view('equipment.create', [
            'pageTitle'  => 'Add Equipment',
            'breadcrumb' => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Equipment Inventory', 'url' => '/dashboard?section=inventory'],
                ['label' => 'Add Equipment'],
            ],
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'category'    => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'quantity'    => 'required|integer|min:1',
            'status'      => 'required|in:available,maintenance,retired',
        ]);

        $validated['available_quantity'] = $validated['quantity'];

        $equipment = Equipment::create($validated);

        AuditLog::log('CREATE_EQUIPMENT', 'equipment', $equipment->id);

        return redirect('/dashboard?section=inventory')
            ->with('success', "Equipment \"{$equipment->name}\" created successfully.");
    }

    public function edit(Equipment $equipment)
    {
        $categories = Equipment::select('category')->distinct()->orderBy('category')->pluck('category');

        return view('equipment.edit', [
            'pageTitle'  => 'Edit: ' . $equipment->name,
            'breadcrumb' => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Equipment Inventory', 'url' => '/dashboard?section=inventory'],
                ['label' => 'Edit'],
            ],
            'equipment'  => $equipment,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Equipment $equipment)
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'category'           => 'required|string|max:100',
            'description'        => 'nullable|string|max:1000',
            'quantity'           => 'required|integer|min:1',
            'available_quantity' => 'required|integer|min:0',
            'status'             => 'required|in:available,maintenance,retired',
        ]);

        // Ensure available_quantity doesn't exceed total
        if ($validated['available_quantity'] > $validated['quantity']) {
            $validated['available_quantity'] = $validated['quantity'];
        }

        $equipment->update($validated);

        AuditLog::log('UPDATE_EQUIPMENT', 'equipment', $equipment->id);

        return redirect('/dashboard?section=inventory')
            ->with('success', "Equipment \"{$equipment->name}\" updated successfully.");
    }

    public function destroy(Equipment $equipment)
    {
        // Prevent deletion if there are active borrow requests
        $activeCount = $equipment->requests()
            ->whereIn('status', ['pending', 'approved', 'borrowed'])
            ->count();

        if ($activeCount > 0) {
            return back()->with('error', "Cannot delete \"{$equipment->name}\" — it has {$activeCount} active request(s).");
        }

        $name = $equipment->name;
        AuditLog::log('DELETE_EQUIPMENT', 'equipment', $equipment->id);
        $equipment->delete();

        return redirect('/dashboard?section=inventory')
            ->with('success', "Equipment \"{$name}\" deleted.");
    }

    // ─── Borrow Flow (Solver-Enabled) ──────────────────────────────────

    /**
     * Borrow equipment — processed through the deterministic ResourceSolver.
     * Flow is pending + manual approval, or config-driven auto-approval.
     */
    public function borrow(Request $request, Equipment $equipment)
    {
        $request->validate([
            'quantity'             => 'required|integer|min:1',
            'purpose'              => 'required|string|max:255',
            'date_needed'          => 'required|date|after_or_equal:today',
            'expected_return_date' => 'required|date|after_or_equal:date_needed',
        ]);

        $user = Auth::user();

        // ─── Run deterministic solver ───
        $result = DB::transaction(function () use ($request, $equipment, $user) {
            // Lock equipment row to prevent race conditions
            Equipment::lockForUpdate()->find($equipment->id);

            $solverResult = ResourceSolver::resolveEquipmentRequest(
                $equipment,
                (int) $request->quantity,
                $request->date_needed,
                $request->expected_return_date,
                $user
            );

            if ($solverResult['status'] === 'rejected') {
                return $solverResult;
            }

            // ─── Create the request with pending status (inside the lock) ───
            $eqRequest = EquipmentRequest::create([
                'equipment_id'         => $equipment->id,
                'user_id'              => $user->id,
                'quantity'             => $request->quantity,
                'purpose'              => $request->purpose,
                'date_needed'          => $request->date_needed,
                'expected_return_date' => $request->expected_return_date,
                'status'               => 'pending',
                'remarks'              => 'Pending admin approval',
            ]);

            AuditLog::log('CREATE_BORROW', 'equipment_requests', $eqRequest->id);

            return array_merge($solverResult, ['eqRequest' => $eqRequest]);
        });

        // Handle preemption of lower-priority requests
        if ($result['status'] === 'rejected') {
            $suggestions = [];
            if (!empty($result['suggestions'])) {
                foreach (array_slice($result['suggestions'], 0, 3) as $s) {
                    $suggestions[] = [
                        'type'         => 'alternate_equipment',
                        'label'        => "{$s['name']} ({$s['category']}, {$s['available']} avail.)",
                        'equipment_id' => $s['equipment_id'] ?? $s['id'] ?? null,
                        'name'         => $s['name'] ?? null,
                    ];
                }
            }

            return back()->withInput()
                ->with('error', $result['reason'])
                ->with('suggestions', $suggestions);
        }

        $eqRequest = $result['eqRequest'];

        // ─── Optional rule-based auto-approval ───────────────────────────
        $autoCfg = config('icoep.auto_approve.equipment', []);
        if (!empty($autoCfg['enabled'])) {
            $roleName     = $user->role->name ?? '';
            $allowedRoles = $autoCfg['allowed_roles'] ?? [];
            $buffer       = (int) ($autoCfg['min_stock_buffer'] ?? 0);
            $roleAllowed  = in_array($roleName, $allowedRoles, true);

            if ($roleAllowed && $result['status'] === 'approved') {
                $autoApproved = DB::transaction(function () use ($eqRequest, $buffer) {
                    $locked    = EquipmentRequest::lockForUpdate()->find($eqRequest->id);
                    $equipment = Equipment::lockForUpdate()->find($locked->equipment_id);

                    if ($equipment->available_quantity < ($locked->quantity + $buffer)) {
                        return false;
                    }

                    $equipment->decrement('available_quantity', $locked->quantity);
                    $locked->update([
                        'status'      => 'approved',
                        'remarks'     => 'Auto-approved by system rule.',
                        'approved_by' => null,
                    ]);
                    return true;
                });

                if ($autoApproved) {
                    $eqRequest->refresh();
                    AuditLog::log('AUTO_APPROVED_BORROW', 'equipment_requests', $eqRequest->id);

                    Notification::send(
                        $user->id,
                        '✅ Equipment Request Approved',
                        "Your borrow request for \"{$equipment->name}\" (qty: {$request->quantity}) was automatically approved.",
                        'success',
                        '/equipment/' . $equipment->id
                    );

                    return redirect('/equipment/' . $equipment->id)
                        ->with('success', 'Equipment request automatically approved — sufficient stock confirmed.');
                }
            }
        }
        // ─────────────────────────────────────────────────────────────────

        Notification::send(
            $user->id,
            '⏳ Equipment Request Pending',
            "Your borrow request for \"{$equipment->name}\" (qty: {$request->quantity}) has been submitted and is pending admin approval.",
            'info',
            '/equipment/' . $equipment->id
        );

        // Notify Admins
        $admins = \App\Models\User::whereHas('role', function($q) {
            $q->where('name', 'Administrator');
        })->get();

        foreach ($admins as $admin) {
            Notification::send(
                $admin->id,
                '🔔 New Equipment Request',
                "{$user->full_name} has requested to borrow \"{$equipment->name}\" (qty: {$request->quantity}).",
                'info',
                '/dashboard?section=manage-equipment'
            );
        }

        return redirect('/equipment/' . $equipment->id)
            ->with('success', 'Equipment request submitted successfully. Waiting for admin approval.');
    }

    // ─── Admin Request Management ───────────────────────────────────────

    public function manage(Request $request)
    {
        $query = EquipmentRequest::with(['equipment', 'user']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        } else {
            $query->pending();
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('purpose', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($q2) => $q2->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))
                  ->orWhereHas('equipment', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        $requests = $query->orderByDesc('created_at')
            ->paginate(config('icoep.items_per_page'));

        return view('equipment.manage', [
            'pageTitle' => 'Manage Equipment Requests',
            'breadcrumb' => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Equipment Requests'],
            ],
            'requests'     => $requests,
            'statusFilter' => $status ?? 'pending',
            'search'       => $search,
        ]);
    }

    public function updateStatus(Request $request, EquipmentRequest $equipmentRequest)
    {
        $request->validate([
            'status'  => 'required|in:approved,rejected,borrowed,returned,cancelled',
            'remarks' => 'nullable|string|max:500',
        ]);

        // ─── Allowed state transitions ───
        $allowedTransitions = [
            'pending'  => ['approved', 'rejected', 'cancelled'],
            'approved' => ['borrowed', 'cancelled'],
            'borrowed' => ['returned'],
        ];

        $currentStatus = $equipmentRequest->status;
        $allowed = $allowedTransitions[$currentStatus] ?? [];

        if (!in_array($request->status, $allowed)) {
            return back()->with('error', "Cannot change status from '{$currentStatus}' to '{$request->status}'.");
        }

        // Block approving a request whose date is already past
        if ($request->status === 'approved' && $equipmentRequest->date_needed->lt(now()->startOfDay())) {
            return back()->with('error', 'Cannot approve a request whose needed date has already passed.');
        }

        $txError = DB::transaction(function () use ($equipmentRequest, $request) {
            // Re-fetch with lock to get the authoritative status & stock
            $locked = EquipmentRequest::lockForUpdate()->find($equipmentRequest->id);
            $oldStatus = $locked->status;
            $equipment = Equipment::lockForUpdate()->find($locked->equipment_id);

            $updateData = [
                'status'      => $request->status,
                'remarks'     => $request->remarks,
                'approved_by' => Auth::id(),
            ];

            // If transitioned to 'approved' or 'borrowed' from a non-reserved state, decrement stock
            if (in_array($request->status, ['approved', 'borrowed']) && in_array($oldStatus, ['pending', 'rejected', 'cancelled'])) {
                if ($equipment->available_quantity < $locked->quantity) {
                    return 'Cannot approve: insufficient stock. Only ' . $equipment->available_quantity . ' unit(s) available.';
                }
                $equipment->decrement('available_quantity', $locked->quantity);
            }

            // If returned, increment stock back
            if ($request->status === 'returned' && in_array($oldStatus, ['approved', 'borrowed'])) {
                $equipment->increment('available_quantity', $locked->quantity);
                $updateData['actual_return_date'] = now();
            }

            // If cancelled or rejected AFTER being reserved, increment stock back
            if (in_array($request->status, ['rejected', 'cancelled']) && in_array($oldStatus, ['approved', 'borrowed'])) {
                $equipment->increment('available_quantity', $locked->quantity);
            }

            $locked->update($updateData);

            return null;
        });

        if ($txError) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $txError], 422);
            }
            return back()->with('error', $txError);
        }

        AuditLog::log(strtoupper($request->status) . '_BORROW', 'equipment_requests', $equipmentRequest->id);

        $statusLabel = ucfirst($request->status);
        $remarkText  = '';
        if ($request->status === 'rejected' && $request->filled('remarks')) {
            $remarkText = ' Reason: ' . $request->remarks;
        }
        Notification::send(
            $equipmentRequest->user_id,
            "Equipment Request {$statusLabel}",
            "Your borrow request for \"{$equipmentRequest->equipment->name}\" has been {$request->status}.{$remarkText}",
            in_array($request->status, ['approved', 'returned']) ? 'success' : ($request->status === 'rejected' ? 'error' : 'warning'),
            '/equipment/' . $equipmentRequest->equipment_id
        );

        if ($request->expectsJson()) {
            $equipmentRequest->refresh();
            return response()->json(['success' => true, 'status' => $equipmentRequest->status]);
        }
        return back()->with('success', "Request {$request->status} successfully.");
    }

    // ─── Bulk Approve ───

    public function bulkApprove(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        $succeeded = 0;
        $failures  = [];

        foreach ($request->ids as $id) {
            $eqReq = EquipmentRequest::find($id);
            if (!$eqReq || $eqReq->status !== 'pending') {
                $failures[] = "#{$id}: not found or not pending";
                continue;
            }
            if ($eqReq->date_needed->lt(now()->startOfDay())) {
                $failures[] = "#{$id}: date already passed";
                continue;
            }

            $txError = DB::transaction(function () use ($eqReq) {
                $locked    = EquipmentRequest::lockForUpdate()->find($eqReq->id);
                $equipment = Equipment::lockForUpdate()->find($locked->equipment_id);
                if ($equipment->available_quantity < $locked->quantity) {
                    return 'insufficient stock (' . $equipment->available_quantity . ' available)';
                }
                $equipment->decrement('available_quantity', $locked->quantity);
                $locked->update(['status' => 'approved', 'approved_by' => Auth::id()]);
                return null;
            });

            if ($txError) {
                $failures[] = "#{$id}: {$txError}";
                continue;
            }

            AuditLog::log('APPROVED_BORROW', 'equipment_requests', $eqReq->id);
            Notification::send(
                $eqReq->user_id,
                'Equipment Request Approved',
                "Your borrow request for \"{$eqReq->equipment->name}\" has been approved.",
                'success',
                '/equipment/' . $eqReq->equipment_id
            );
            $succeeded++;
        }

        $msg = "{$succeeded} approved";
        if ($failures) {
            $msg .= '; ' . count($failures) . ' failed: ' . implode(', ', $failures);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => $succeeded, 'failures' => $failures, 'message' => $msg]);
        }
        return back()->with($failures ? 'error' : 'success', $msg);
    }

    // ─── User Cancel Own Pending Request ───

    public function cancelOwn(Request $request, EquipmentRequest $equipmentRequest)
    {
        if ($equipmentRequest->user_id !== Auth::id()) {
            abort(403);
        }
        if ($equipmentRequest->status !== 'pending') {
            $msg = 'Only pending requests can be cancelled.';
            if ($request->expectsJson()) {
                return response()->json(['error' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $equipmentRequest->update(['status' => 'cancelled', 'remarks' => 'Cancelled by requester']);
        AuditLog::log('CANCELLED_BORROW', 'equipment_requests', $equipmentRequest->id);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'status' => 'cancelled']);
        }
        return back()->with('success', 'Request cancelled.');
    }
}
