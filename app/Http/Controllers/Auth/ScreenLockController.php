<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ScreenLockController extends Controller
{
    public function setPin(Request $request)
    {
        $data = $request->validate([
            'pin' => ['required', 'digits:6', 'confirmed'],
        ]);

        $request->user()->forceFill([
            'screen_pin' => Hash::make($data['pin']),
        ])->save();

        return response()->json(['message' => 'PIN set successfully.']);
    }

    public function lock(Request $request)
    {
        abort_unless($request->user()?->screen_pin, 422, 'Please set a PIN first.');
        $request->session()->put('screen_locked', true);

        return response()->json(['message' => 'Screen locked.']);
    }

    public function unlock(Request $request)
    {
        $data = $request->validate(['pin' => ['required', 'digits:6']]);
        $user = $request->user();

        if (!$user || !$user->screen_pin || !Hash::check($data['pin'], $user->screen_pin)) {
            throw ValidationException::withMessages(['pin' => 'Invalid PIN.']);
        }

        $request->session()->forget('screen_locked');

        return response()->json(['message' => 'Unlocked.']);
    }

    public function pinLogin(Request $request)
    {
        $data = $request->validate(['pin' => ['required', 'digits:6']]);
        $userId = $request->session()->get('pin_login_user_id');
        $user = $userId ? User::find($userId) : null;

        if (!$user || !$user->screen_pin || !Hash::check($data['pin'], $user->screen_pin)) {
            throw ValidationException::withMessages(['pin' => 'Invalid PIN.']);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget(['screen_locked', 'pin_login_user_id']);

        return redirect()->route('admin.dashboard');
    }
}
