<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Inactivity Warning</title>
    <style>
        body { margin: 0; padding: 0; font-family: 'Inter', Arial, sans-serif; background: #f1f5f9; color: #1e293b; }
        .wrapper { max-width: 600px; margin: 2rem auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .header { background: linear-gradient(135deg, #041c32, #064663); padding: 2rem; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 1.5rem; }
        .header p  { color: rgba(255,255,255,.7); margin: .25rem 0 0; font-size: .9rem; }
        .body { padding: 2rem; }
        .alert-box { background: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; }
        .alert-box p { margin: 0; color: #b45309; font-weight: 600; font-size: .95rem; }
        .detail-row { display: flex; justify-content: space-between; padding: .6rem 0; border-bottom: 1px solid #f1f5f9; font-size: .875rem; }
        .detail-row span:first-child { color: #64748b; }
        .detail-row span:last-child  { color: #0f172a; font-weight: 600; }
        .btn { display: inline-block; margin-top: 1.5rem; padding: .75rem 1.5rem; background: linear-gradient(135deg,#0284c7,#0369a1); color: #fff !important; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: .9rem; text-align: center; }
        .footer { background: #f8fafc; padding: 1.25rem 2rem; text-align: center; font-size: .75rem; color: #94a3b8; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>⚠️ Account Inactivity Warning</h1>
        <p>{{ config('icoep.institution_short') }} — {{ config('icoep.app_name') }}</p>
    </div>
    <div class="body">
        <p>Dear <strong>{{ $user->full_name }}</strong>,</p>
        <p>We noticed that you have not logged in to your account for <strong>{{ $daysInactive }} days</strong>. To maintain system security and resources, accounts without activity are automatically deactivated.</p>

        <div class="alert-box">
            <p>Action Required: Your account will be deactivated in approximately {{ $daysRemaining }} day(s) if you do not sign in.</p>
        </div>

        <p>To keep your account active, simply log in to the portal using your credentials:</p>

        <div style="text-align: center;">
            <a href="{{ url('/login') }}" class="btn">Log In to Keep Account Active</a>
        </div>

        <p style="margin-top: 2rem; font-size: .85rem; color: #64748b;">If your account becomes deactivated, you will need to contact an administrator to reactivate it.</p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('icoep.institution_name') }}. All rights reserved.</p>
    </div>
</div>
</body>
</html>
