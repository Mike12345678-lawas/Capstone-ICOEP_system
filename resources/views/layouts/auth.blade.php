<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ config('icoep.app_full_name') }} - Login">
    <title>@yield('title', 'Login') &mdash; {{ config('icoep.app_name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Vite assets: Tailwind CSS v4 -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">
    @yield('content')
</body>
</html>
