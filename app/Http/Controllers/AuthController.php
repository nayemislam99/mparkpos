<?php

namespace App\Http\Controllers;

use App\Models\User;
// use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function login()
    {
        return view('admin.auth.login');
    }    

    /**
     * Authenticate and log in a user.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function authenticate(Request $request)
    {       
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember'); // checkbox safe

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // ActivityLog::createNewActivityLog('user login', 'users', "User login: Email - {$request->email}");

            return redirect()->intended(route('dashboard.index'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Log out the current user and invalidate session.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $email = optional($request->user())->email ?? optional(Auth::user())->email;

        // ActivityLog::createNewActivityLog('user logout', 'users', "User logout: Email - {$email}");

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('auth.login'); 
    }
}
