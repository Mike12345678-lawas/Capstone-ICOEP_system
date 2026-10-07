@php
    $unreadCount = auth()->user()->notifications()->unread()->count();
    $notifications = auth()->user()->notifications()->latest()->limit(5)->get();
@endphp
<!-- Notification Bell -->
<div class="notification-wrapper" id="notificationWrapper">
    <button class="notification-bell" id="notificationBell" aria-label="Notifications">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        @if($unreadCount > 0)
            <span class="notification-badge" id="notifBadge">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </button>

    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notif-header">
            <h4>Notifications</h4>
            <div class="notif-actions" id="notifActions" @if($notifications->isEmpty()) style="display:none;" @endif>
                @if($unreadCount > 0)
                    <button type="button" id="markAllRead" class="notif-action-btn notif-mark-all" title="Mark all as read">Mark all read</button>
                @endif
                <button type="button" id="deleteAllNotifs" class="notif-action-btn notif-delete-all" title="Delete all notifications">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>
                    Clear all
                </button>
            </div>
        </div>
        <div class="notif-list" id="notifList">
            <div class="notif-empty" id="notifEmpty" @if($notifications->isNotEmpty()) style="display:none;" @endif>
                <p>No notifications yet</p>
            </div>
            @foreach($notifications as $notif)
                <div class="notif-item {{ !$notif->is_read ? 'unread' : '' }}" data-id="{{ $notif->id }}">
                    <a href="{{ $notif->link ? url($notif->link) : '#' }}" class="notif-item-link">
                        <div class="notif-icon notif-{{ $notif->type }}">
                            @if($notif->type === 'success')
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            @elseif($notif->type === 'warning')
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line></svg>
                            @elseif($notif->type === 'error')
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line></svg>
                            @else
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                            @endif
                        </div>
                        <div class="notif-content">
                            <p class="notif-title">{{ $notif->title }}</p>
                            <p class="notif-message">{{ $notif->message }}</p>
                            <span class="notif-time">{{ $notif->created_at->diffForHumans() }}</span>
                        </div>
                    </a>
                    <button type="button" class="notif-delete-single" data-id="{{ $notif->id }}" aria-label="Delete notification" title="Delete">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            @endforeach
        </div>
    </div>
</div>
