<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overdue Equipment Notice</title>
    <style>
        body { margin: 0; padding: 0; font-family: 'Inter', Arial, sans-serif; background: #f1f5f9; }
        .wrapper { max-width: 600px; margin: 2rem auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .header { background: linear-gradient(135deg, #041c32, #064663); padding: 2rem; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 1.5rem; }
        .header p  { color: rgba(255,255,255,.7); margin: .25rem 0 0; font-size: .9rem; }
        .body { padding: 2rem; }
        .alert-box { background: #fef2f2; border-left: 4px solid #ef4444; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; }
        .alert-box p { margin: 0; color: #991b1b; font-weight: 600; }
        .detail-row { display: flex; justify-content: space-between; padding: .6rem 0; border-bottom: 1px solid #f1f5f9; font-size: .875rem; }
        .detail-row span:first-child { color: #64748b; }
        .detail-row span:last-child  { color: #0f172a; font-weight: 600; }
        .btn { display: inline-block; margin-top: 1.5rem; padding: .75rem 1.5rem; background: linear-gradient(135deg,#0284c7,#0369a1); color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: .9rem; }
        .footer { background: #f8fafc; padding: 1.25rem 2rem; text-align: center; font-size: .75rem; color: #94a3b8; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>⚠️ Overdue Equipment Notice</h1>
        <p>{{ config('icoep.institution_short') }} — {{ config('icoep.app_name') }}</p>
    </div>
    <div class="body">
        <p>Dear <strong>{{ $borrower->full_name }}</strong>,</p>
        <p>This is a reminder that you have borrowed equipment that is now <strong>{{ $daysOverdue }} day(s) overdue</strong>. Please return it to the campus facility office as soon as possible.</p>

        <div class="alert-box">
            <p>⚠️ This item is {{ $daysOverdue }} day(s) past its expected return date.</p>
        </div>

        <div style="background: #f8fafc; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
            <div class="detail-row">
                <span>Equipment</span><span>{{ $equipment->name }}</span>
            </div>
            <div class="detail-row">
                <span>Category</span><span>{{ $equipment->category }}</span>
            </div>
            <div class="detail-row">
                <span>Quantity Borrowed</span><span>{{ $request->quantity }}</span>
            </div>
            <div class="detail-row">
                <span>Date Borrowed</span><span>{{ $request->updated_at->format('M d, Y') }}</span>
            </div>
            <div class="detail-row" style="border-bottom: none;">
                <span>Expected Return</span><span style="color: #ef4444;">{{ $request->expected_return_date->format('M d, Y') }}</span>
            </div>
        </div>

        <p style="color: #64748b; font-size: .875rem;">Failure to return borrowed equipment may result in account restrictions. If you have already returned the item, please disregard this notice and contact the administrator to update the records.</p>

        <a href="{{ config('app.url') }}/equipment/{{ $equipment->id }}" class="btn">View Equipment Details</a>
    </div>
    <div class="footer">
        <p>This is an automated message from {{ config('icoep.institution_short') }}. Please do not reply to this email.</p>
        <p>© {{ date('Y') }} {{ config('icoep.institution_name') }}</p>
    </div>
</div>
</body>
</html>
