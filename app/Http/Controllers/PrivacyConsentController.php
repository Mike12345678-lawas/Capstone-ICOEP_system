<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PrivacyConsent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrivacyConsentController extends Controller
{
    /**
     * Show the privacy consent acceptance form.
     */
    public function showConsentForm()
    {
        $user = Auth::user();

        // If user has already consented, redirect to dashboard
        if ($user && $user->privacy_consent) {
            return redirect('/dashboard');
        }

        return view('privacy.consent', [
            'pageTitle' => 'Data Privacy Consent',
            'user'      => $user,
        ]);
    }

    /**
     * Accept the privacy policy and record consent.
     */
    public function accept(Request $request)
    {
        $request->validate([
            'agree' => 'required|accepted',
        ], [
            'agree.accepted' => 'You must check the agreement box to accept the Data Privacy Policy and proceed.',
            'agree.required' => 'You must check the agreement box to accept the Data Privacy Policy and proceed.',
        ]);

        $user = Auth::user();

        // Update user record
        $user->forceFill([
            'privacy_consent'      => true,
            'privacy_consent_date' => now(),
        ])->save();

        // Record consent row in privacy_consents table
        $consent = PrivacyConsent::create([
            'user_id'      => $user->id,
            'consent_type' => 'terms_and_privacy',
            'consented'    => true,
            'ip_address'   => $request->ip(),
        ]);

        AuditLog::log('PRIVACY_CONSENT_ACCEPTED', 'privacy_consents', $consent->id, $user->id);

        return redirect('/dashboard')->with('success', 'Thank you for accepting the Data Privacy Policy. Welcome to ICOEP!');
    }
}
