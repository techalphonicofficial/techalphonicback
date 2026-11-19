<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        $remember = $request->boolean('remember');
        $logContext = ['email_hash' => hash('sha256', strtolower($credentials['email'])), 'ip' => $request->ip()];

        if (! Auth::attempt($credentials + ['is_active' => true], $remember)) {
            Log::channel('security')->warning('Admin login failed', $logContext);
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        /** @var User $user */
        $user = Auth::user();
        if ($user->getRoleNames()->isEmpty()) {
            Auth::logout();
            Log::channel('security')->warning('Login without admin role blocked', $logContext);
            throw ValidationException::withMessages(['email' => 'Your account does not have admin access.']);
        }

        // Prevent session fixation.
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->saveQuietly();
        ActivityLog::record('login', $user, 'Signed in');
        Log::channel('security')->info('Admin login', ['user_id' => $user->id, 'ip' => $request->ip()]);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        ActivityLog::record('logout', $request->user(), 'Signed out');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
