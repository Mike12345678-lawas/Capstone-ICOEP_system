<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $roleName = $user->role->name;

        $query = Announcement::with('author')->active();

        // Audience filtering
        $query->where(function ($q) use ($roleName, $user) {
            $q->where('audience', 'all');
            if ($roleName === 'Student') $q->orWhere('audience', 'students');
            elseif ($roleName === 'Faculty') $q->orWhere('audience', 'faculty');
            elseif ($roleName === 'Staff') $q->orWhere('audience', 'staff');
            elseif ($roleName === 'Administrator') $q->orWhere('audience', 'administrators');
            // Admin/Staff with manage permission see all
            if ($user->hasPermission('announcement.manage')) {
                $q->orWhereIn('audience', ['students', 'faculty', 'staff', 'administrators']);
            }
        });

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        $announcements = $query->ordered()
            ->paginate(config('icoep.items_per_page'));

        return view('announcements.index', [
            'pageTitle'      => 'Announcements',
            'breadcrumb'     => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Announcements'],
            ],
            'announcements'  => $announcements,
            'search'         => $search,
            'priorityFilter' => $priority,
        ]);
    }

    public function show(Announcement $announcement)
    {
        $user = Auth::user();
        $roleName = $user->role->name;

        // Check audience permission
        $allowed = $announcement->audience === 'all'
            || ($announcement->audience === 'students' && $roleName === 'Student')
            || ($announcement->audience === 'faculty' && $roleName === 'Faculty')
            || ($announcement->audience === 'staff' && $roleName === 'Staff')
            || ($announcement->audience === 'administrators' && $roleName === 'Administrator')
            || $user->hasPermission('announcement.manage');

        if (!$allowed) {
            return redirect('/announcements')->with('error', 'You do not have permission to view this announcement.');
        }

        $announcement->load('author');

        return view('announcements.show', [
            'pageTitle' => $announcement->title,
            'breadcrumb' => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Announcements', 'url' => '/announcements'],
                ['label' => 'View Announcement'],
            ],
            'announcement' => $announcement,
        ]);
    }

    public function create()
    {
        return view('announcements.create', [
            'pageTitle' => 'Post Announcement',
            'breadcrumb' => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Announcements', 'url' => '/announcements'],
                ['label' => 'Post Announcement'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'      => 'required|string|max:255',
            'content'    => 'required|string',
            'priority'   => 'required|in:general,important,urgent',
            'audience'   => 'required|in:all,students,faculty,staff',
            'expires_at' => 'nullable|date|after:now',
            'is_pinned'  => 'nullable|boolean',
        ]);

        $announcement = Announcement::create([
            'title'      => $request->title,
            'content'    => $request->content,
            'priority'   => $request->priority,
            'audience'   => $request->audience,
            'posted_by'  => Auth::id(),
            'is_pinned'  => $request->boolean('is_pinned'),
            'expires_at' => $request->expires_at,
        ]);

        AuditLog::log('CREATE_ANNOUNCEMENT', 'announcements', $announcement->id);

        return redirect('/announcements')->with('success', 'Announcement posted successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        $user = Auth::user();

        if (!$user->hasPermission('announcement.manage') && $announcement->posted_by !== $user->id) {
            return back()->with('error', 'You do not have permission to delete this announcement.');
        }

        $announcement->delete(); // Soft delete
        AuditLog::log('DELETE_ANNOUNCEMENT', 'announcements', $announcement->id);

        return redirect('/announcements')->with('success', 'Announcement deleted successfully.');
    }
}
