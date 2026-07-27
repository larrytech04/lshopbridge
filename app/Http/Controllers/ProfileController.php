<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Notifications\SecurityAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('dashboard.profile', [
            'user' => $request->user(),
            'countries' => Country::active()->get(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'country_id' => ['required', 'exists:countries,id'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        // Changing phone invalidates phone verification.
        if ($data['phone'] !== $user->phone) {
            $user->phone_verified_at = null;
        }

        if ($request->hasFile('avatar')) {
            $data['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->fill($data)->save();

        return back()->with('success', 'Profile updated.');
    }

    public function removePhoto(Request $request)
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        return back()->with('success', 'Profile photo removed.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = $request->user();
        $user->update(['password' => Hash::make($request->password), 'password_changed_at' => now()]);

        $user->notify(new SecurityAlert(
            title: 'Your password was changed',
            message: "Your account password was just changed.\n\nIf you didn't do this, secure your account immediately.",
            actionLabel: 'Review account security',
            actionUrl: route('security.index'),
        ));

        return back()->with('success', 'Password changed.');
    }

    public function updateShortcuts(Request $request)
    {
        $request->user()->update([
            'shortcuts_enabled' => $request->boolean('shortcuts_enabled'),
        ]);

        return back()->with('success', 'Keyboard shortcut preference saved.');
    }

    public function resetShortcuts(Request $request)
    {
        $request->user()->update(['shortcut_overrides' => null]);

        return back()->with('success', 'Keyboard shortcuts restored to defaults.');
    }

    public function referrals(Request $request): View
    {
        $user = $request->user();
        $referrals = $user->referrals()->latest()->get();

        return view('dashboard.referrals', [
            'user' => $user,
            'referrals' => $referrals,
            'referredCount' => $referrals->count(),
            'verifiedCount' => $referrals->where('kyc_level', '>=', 2)->count(),
        ]);
    }

    /** Stored inside the existing `preferences` JSON column, no schema change needed. */
    public function updatePreferences(Request $request)
    {
        $keys = ['notify_web_push', 'notify_order_updates', 'notify_wallet_activity', 'notify_security_alerts', 'notify_promotions', 'notify_email'];
        $user = $request->user();
        $prefs = $user->preferences ?? [];

        foreach ($keys as $key) {
            $prefs[$key] = $request->boolean($key);
        }

        $user->update(['preferences' => $prefs]);

        return back()->with('success', 'Notification preferences saved.');
    }

    /** Soft-closes the account (reuses the existing active-account gate, which logs
     *  closed users out on their next request) rather than hard-deleting a user with
     *  real wallet/transaction history. */
    public function deleteAccount(Request $request)
    {
        $request->validate(['current_password' => ['required', 'current_password']]);

        $user = $request->user();
        $user->update(['status' => 'closed', 'status_reason' => 'Closed by user request']);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Your account has been closed.');
    }
}
