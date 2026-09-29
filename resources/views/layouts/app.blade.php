<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ config('icoep.app_full_name') }} for {{ config('icoep.institution_name') }}. Centralized campus operations, facility reservations, equipment logistics, and school-wide event management.">
    <meta name="author" content="{{ config('icoep.institution_short') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('icoep.app_full_name'))</title>

    <!-- PWA / Mobile First Meta Tags -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0ea5e9">
    <link rel="apple-touch-icon" href="{{ asset('icon-512.jpg') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Vite assets: Tailwind CSS v4 + app scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    <div class="app-layout">
        @include('partials.sidebar')

        <div class="main-content">
            <!-- Top Navbar -->
            <header class="top-navbar">
                <div class="navbar-left">
                    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                    <div class="breadcrumb">
                        @if(isset($breadcrumb))
                            @foreach($breadcrumb as $i => $crumb)
                                @if($i > 0)<span class="breadcrumb-sep">/</span>@endif
                                @if(isset($crumb['url']))
                                    <a href="{{ url($crumb['url']) }}" class="breadcrumb-link">{{ $crumb['label'] }}</a>
                                @else
                                    <span class="breadcrumb-current">{{ $crumb['label'] }}</span>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="navbar-right">
                    <!-- Dark Mode Toggle -->
                    <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">
                        <svg class="sun-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="5"></circle>
                            <line x1="12" y1="1" x2="12" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="23"></line>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                            <line x1="1" y1="12" x2="3" y2="12"></line>
                            <line x1="21" y1="12" x2="23" y2="12"></line>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                            <line x1="18.36" y1="4.22" x2="19.78" y2="5.64"></line>
                        </svg>
                        <svg class="moon-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                    </button>

                    @include('partials.notifications')

                    <!-- User Menu -->
                    <div class="user-menu-wrapper" id="userMenuWrapper">
                        <button class="user-menu-trigger" id="userMenuTrigger" aria-label="User menu">
                            <div class="user-avatar">
                                {{ auth()->user()->initials }}
                            </div>
                            <div class="user-info-mini">
                                <span class="user-name-mini">{{ auth()->user()->full_name }}</span>
                                <span class="user-role-mini">{{ auth()->user()->role->name }}</span>
                            </div>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>

                        <div class="user-dropdown" id="userDropdown">
                            <div class="user-dropdown-header">
                                <div class="user-avatar large">
                                    {{ auth()->user()->initials }}
                                </div>
                                <div>
                                    <p class="user-dropdown-name">{{ auth()->user()->full_name }}</p>
                                    <p class="user-dropdown-email">{{ auth()->user()->email }}</p>
                                </div>
                            </div>
                            <div class="user-dropdown-divider"></div>
                            <a href="{{ url('/users/' . auth()->id() . '/edit') }}" class="user-dropdown-item">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                My Profile
                            </a>
                            <a href="{{ url('/privacy/policy') }}" class="user-dropdown-item">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                Privacy Policy
                            </a>
                            <div class="user-dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="user-dropdown-item logout" style="width:100%;border:none;background:none;cursor:pointer;text-align:left;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="page-content">
                @include('partials.flash')
                @yield('content')
            </main>
        </div><!-- /.main-content -->
    </div><!-- /.app-layout -->

    <!-- Footer -->
    <footer class="app-footer">
        <div class="footer-content">
            <p>&copy; {{ date('Y') }} {{ config('icoep.institution_name') }}. All rights reserved.</p>
            <p class="footer-sub">{{ config('icoep.app_full_name') }} v{{ config('icoep.app_version') }} &bull;
                <a href="{{ url('/privacy/policy') }}">Privacy Policy</a>
            </p>
        </div>
    </footer>

    <!-- Additional page-level scripts -->
    @stack('scripts')
    
    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('Service Worker registered successfully.', reg))
                    .catch(err => console.error('Service Worker registration failed: ', err));
            });
        }
    </script>
</body>
</html>
