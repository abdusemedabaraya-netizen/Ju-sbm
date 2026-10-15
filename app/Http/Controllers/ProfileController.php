<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteProfileRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\AuditLog;
use App\Models\Department;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user'         => $user,
            'departments'  => Department::orderBy('name')->get(['id', 'name']),
            'isStaff'      => $user->isStaff(),
            'recentLogins' => AuditLog::where('user_id', $user->id)
                ->where('action', 'auth.login')->latest('id')->limit(5)->get(['created_at', 'ip_address']),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->validated());

        // A changed e-mail address must be re-verified.
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        AuditLogger::log('profile.updated', $user);

        return Redirect::route('profile.edit')->with('success', 'Profile updated.');
    }

    public function destroy(DeleteProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Staff own cases and audit history, so they cannot delete themselves.
        abort_if($user->isStaff(), 403);

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
