<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    private const STAFF = ['Super Admin', 'Admin', 'Investigator'];

    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user'         => $user,
            'departments'  => Department::orderBy('name')->get(['id', 'name']),
            'isStaff'      => $user->hasAnyRole(self::STAFF),
            'recentLogins' => AuditLog::where('user_id', $user->id)
                ->where('action', 'auth.login')->latest('id')->limit(5)->get(['created_at', 'ip_address']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user    = $request->user();
        $isStaff = $user->hasAnyRole(self::STAFF);

        $rules = [
            'name'          => ['required', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'job_title'     => ['nullable', 'string', 'max:100'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'bio'           => ['nullable', 'string', 'max:500'],
        ];

        // Staff e-mail is changed by the Super Admin only: a changed address would
        // need re-verification and could lock a staff member out of the system.
        if (! $isStaff) {
            $rules['email'] = ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)];
        }

        $user->fill($request->validate($rules));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        AuditLogger::log('profile.updated', $user);

        return Redirect::route('profile.edit')->with('success', 'Profile updated.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Staff own cases and audit history, so they cannot delete themselves.
        abort_if($user->hasAnyRole(self::STAFF), 403);

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        AuditLogger::log('profile.deleted', $user);

        if ($user->avatar) {
            Storage::disk('local')->delete($user->avatar);
        }

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
