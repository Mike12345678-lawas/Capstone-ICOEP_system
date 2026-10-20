<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notification->title }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .wrapper {
            width: 100%;
            background-color: #f1f5f9;
            padding: 32px 12px;
            box-sizing: border-box;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #041c32 0%, #064663 60%, #0a567a 100%);
            padding: 28px 24px;
            text-align: center;
            color: #ffffff;
        }
        .header-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #ffffff;
        }
        .header-subtitle {
            margin: 4px 0 0 0;
            font-size: 0.825rem;
            color: rgba(255, 255, 255, 0.78);
            font-weight: 400;
        }
        .accent-bar {
            height: 4px;
            width: 100%;
            background-color: {{ $typeTheme['border'] }};
        }
        .content {
            padding: 32px 28px;
        }
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background-color: {{ $typeTheme['bg_badge'] }};
            color: {{ $typeTheme['text_badge'] }};
            border: 1px solid {{ $typeTheme['border_sub'] }};
            margin-bottom: 16px;
        }
        .greeting {
            font-size: 1rem;
            color: #475569;
            margin: 0 0 12px 0;
        }
        .greeting strong {
            color: #0f172a;
        }
        .notification-title {
            margin: 0 0 16px 0;
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.35;
        }
        .message-box {
            background-color: {{ $typeTheme['bg_badge'] }};
            border-left: 4px solid {{ $typeTheme['border'] }};
            border-radius: 8px;
            padding: 18px 20px;
            margin: 20px 0 24px 0;
            font-size: 0.975rem;
            line-height: 1.6;
            color: #1e293b;
        }
        .message-box p {
            margin: 0;
        }
        .details-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 28px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 0.85rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .detail-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .detail-row:first-child {
            padding-top: 0;
        }
        .detail-label {
            color: #64748b;
        }
        .detail-value {
            color: #0f172a;
            font-weight: 600;
            text-align: right;
        }
        .btn-wrapper {
            text-align: center;
            margin: 28px 0 24px 0;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff !important;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.28);
            letter-spacing: 0.01em;
        }
        .btn:hover {
            opacity: 0.95;
        }
        .direct-link {
            font-size: 0.775rem;
            color: #94a3b8;
            word-break: break-all;
            text-align: center;
            line-height: 1.4;
            margin-top: 14px;
        }
        .direct-link a {
            color: #0284c7;
            text-decoration: underline;
        }
        .footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 24px 28px;
            text-align: center;
            font-size: 0.775rem;
            color: #94a3b8;
            line-height: 1.55;
        }
        .footer p {
            margin: 4px 0;
        }
        .footer strong {
            color: #64748b;
        }
        @media only screen and (max-width: 600px) {
            .wrapper { padding: 12px 8px; }
            .content { padding: 24px 18px; }
            .notification-title { font-size: 1.2rem; }
            .btn { display: block; width: 100%; box-sizing: border-box; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1 class="header-title">{{ config('icoep.institution_name', 'St. Louise de Marillac College of Bogo') }}</h1>
            <p class="header-subtitle">{{ config('icoep.app_full_name', 'Integrated Campus Operations and Engagement Portal') }} ({{ config('icoep.app_name', 'ICOEP') }})</p>
        </div>

        <!-- Color Accent Strip based on Notification Type -->
        <div class="accent-bar"></div>

        <!-- Main Content -->
        <div class="content">
            <div class="badge">
                {{ $typeTheme['icon'] }} {{ $typeTheme['badge'] }}
            </div>

            <p class="greeting">Hello <strong>{{ $user->full_name }}</strong>,</p>

            <h2 class="notification-title">{{ $notification->title }}</h2>

            <div class="message-box">
                <p>{{ $notification->message }}</p>
            </div>

            <!-- Metadata Details -->
            <table class="details-box" width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                <tr>
                    <td class="detail-label" style="padding: 6px 0; color: #64748b; font-size: 0.85rem;">Date & Time</td>
                    <td class="detail-value" style="padding: 6px 0; color: #0f172a; font-weight: 600; font-size: 0.85rem; text-align: right;">{{ $notification->created_at ? $notification->created_at->setTimezone(config('app.timezone', 'Asia/Manila'))->format('M d, Y • h:i A') : now()->setTimezone(config('app.timezone', 'Asia/Manila'))->format('M d, Y • h:i A') }}</td>
                </tr>
                <tr>
                    <td class="detail-label" style="padding: 6px 0; color: #64748b; font-size: 0.85rem; border-top: 1px solid #f1f5f9;">Recipient Account</td>
                    <td class="detail-value" style="padding: 6px 0; color: #0f172a; font-weight: 600; font-size: 0.85rem; text-align: right; border-top: 1px solid #f1f5f9;">{{ $user->email }}</td>
                </tr>
                @if($user->role)
                <tr>
                    <td class="detail-label" style="padding: 6px 0; color: #64748b; font-size: 0.85rem; border-top: 1px solid #f1f5f9;">Role</td>
                    <td class="detail-value" style="padding: 6px 0; color: #0f172a; font-weight: 600; font-size: 0.85rem; text-align: right; border-top: 1px solid #f1f5f9;">{{ $user->role->name }}</td>
                </tr>
                @endif
            </table>

            <!-- Call to Action -->
            <div class="btn-wrapper">
                <a href="{{ $actionUrl }}" class="btn" target="_blank" rel="noopener noreferrer">
                    Open in Portal &rarr;
                </a>
            </div>

            <div class="direct-link">
                Having trouble with the button? Copy and paste this link into your browser:<br>
                <a href="{{ $actionUrl }}">{{ $actionUrl }}</a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is an automated notification sent to <strong>{{ $user->email }}</strong> from your account in the {{ config('icoep.app_name', 'ICOEP') }} portal.</p>
            <p>&copy; {{ date('Y') }} {{ config('icoep.institution_name', 'St. Louise de Marillac College of Bogo') }}. All rights reserved.</p>
        </div>
    </div>
</div>
</body>
</html>
