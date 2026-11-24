<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Show the user profile view.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user()->loadMissing(['company', 'branch']);
        $activeTab = 'profile';

        return view('admin.profile.index', compact('user', 'activeTab'));
    }

    /**
     * Update the user's profile details.
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($user->id)
                    ->where(fn ($q) => $q->where('company_id', $user->company_id)->whereNull('deleted_at')),
            ],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        return redirect()->route('profile.index')->with('success', __('messages.profile_updated_success'));
    }

    /**
     * Show the change password view.
     */
    public function passwordView(Request $request): View
    {
        /** @var User $user */
        $user = $request->user()->loadMissing(['company', 'branch']);
        $activeTab = 'password';

        return view('admin.profile.index', compact('user', 'activeTab'));
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('messages.current_password_incorrect')],
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
            'force_password_change' => false,
        ]);

        return redirect()->route('profile.password')->with('success', __('messages.password_updated_success'));
    }

    /**
     * Clear application cache.
     */
    public function clearCache(Request $request): RedirectResponse
    {
        try {
            Artisan::call('optimize:clear');

            return redirect()->back()->with('success', __('messages.cache_cleared_success'));
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Error clearing cache: ' . $e->getMessage());
        }
    }
}
