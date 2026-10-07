<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['role', 'department']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        if ($roleFilter = $request->get('role')) {
            $query->where('role_id', $roleFilter);
        }

        if ($statusFilter = $request->get('status')) {
            if ($statusFilter === 'inactive_auto') {
                $query->where('status', 'inactive')->where('deactivation_reason', 'inactivity');
            } elseif ($statusFilter === 'inactive_admin') {
                $query->where('status', 'inactive')->where('deactivation_reason', 'admin');
            } else {
                $query->where('status', $statusFilter);
            }
        }

        $users = $query->orderBy('last_name')->orderBy('first_name')
            ->paginate(config('icoep.items_per_page'));

        $roles = Role::orderBy('id')->get();

        return view('users.index', [
            'pageTitle'    => 'User Management',
            'breadcrumb'   => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'User Management'],
            ],
            'users'        => $users,
            'roles'        => $roles,
            'search'       => $search,
            'roleFilter'   => $roleFilter,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function create()
    {
        $roles       = Role::orderBy('id')->get();
        $departments = Department::orderBy('name')->get();

        return view('users.create', [
            'pageTitle' => 'Create User',
            'breadcrumb' => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'User Management', 'url' => '/users'],
                ['label' => 'Create User'],
            ],
            'roles'       => $roles,
            'departments' => $departments,
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $additionalData = [
            'privacy_consent'      => true,
            'privacy_consent_date' => now(),
        ];

        if (($validated['status'] ?? 'active') === 'inactive') {
            $additionalData['deactivated_at']      = now();
            $additionalData['deactivation_reason'] = 'admin';
        } else {
            $additionalData['last_activity_at'] = now();
        }

        $user = User::create(array_merge($validated, $additionalData));

        AuditLog::log('CREATE_USER', 'users', $user->id);

        return redirect('/users')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $isSelf = $user->id === Auth::id();

        if (!$isSelf && !Auth::user()->hasPermission('user.edit')) {
            abort(403);
        }

        $roles       = Role::orderBy('id')->get();
        $departments = Department::orderBy('name')->get();

        $breadcrumb = [['label' => 'Dashboard', 'url' => '/dashboard']];
        if (!$isSelf && Auth::user()->hasPermission('user.view')) {
            $breadcrumb[] = ['label' => 'User Management', 'url' => '/users'];
        }
        $breadcrumb[] = ['label' => $isSelf ? 'My Profile' : 'Edit User'];

        return view('users.edit', [
            'pageTitle'   => $isSelf ? 'My Profile' : 'Edit User',
            'breadcrumb'  => $breadcrumb,
            'user'        => $user,
            'roles'       => $roles,
            'departments' => $departments,
            'isSelf'      => $isSelf,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $isSelf = $user->id === Auth::id();

        $validated  = $request->validated();
        $updateData = [
            'first_name'    => $validated['first_name'],
            'last_name'     => $validated['last_name'],
            'email'         => $validated['email'],
            'employee_id'   => $validated['employee_id']   ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'phone'         => $validated['phone']         ?? null,
        ];

        $reactivated = false;

        // Admin-only fields
        if (!$isSelf && Auth::user()->hasPermission('user.edit')) {
            $updateData['role_id'] = $validated['role_id'] ?? $user->role_id;

            if (isset($validated['status'])) {
                $newStatus = $validated['status'];
                $oldStatus = $user->status;
                $updateData['status'] = $newStatus;

                // Activation: set to active
                if ($newStatus === 'active') {
                    $updateData['login_attempts'] = 0;
                    $updateData['locked_until']   = null;

                    if ($oldStatus === 'inactive') {
                        $updateData['last_activity_at']     = now();
                        $updateData['deactivated_at']       = null;
                        $updateData['deactivation_reason']  = null;
                        $updateData['inactivity_warned_at'] = null;
                        $reactivated = true;
                    }
                } elseif ($oldStatus !== 'inactive' && $newStatus === 'inactive') {
                    // Manual admin deactivation
                    $updateData['deactivated_at']       = now();
                    $updateData['deactivation_reason']  = 'admin';
                }
            }
        }

        if (!empty($validated['password'])) {
            $updateData['password'] = $validated['password'];
        }

        $user->update($updateData);
        AuditLog::log('UPDATE_USER', 'users', $user->id);

        if ($reactivated) {
            AuditLog::log('REACTIVATED_USER', 'users', $user->id);
        }

        return redirect('/users/' . $user->id . '/edit')->with('success', 'Profile updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->update([
            'status'              => 'inactive',
            'deactivated_at'      => now(),
            'deactivation_reason' => 'admin',
        ]);
        $user->delete(); // Soft delete
        AuditLog::log('DELETE_USER', 'users', $user->id);

        return redirect('/users')->with('success', 'User deleted.');
    }
}
