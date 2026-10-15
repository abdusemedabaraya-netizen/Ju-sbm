<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignRoleRequest;
use App\Http\Requests\StoreStaffRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->orderBy('id')->get();
        $staff = User::with('roles:id,name')->orderBy('name')->paginate(15);

        return view('roles.index', ['roles' => $roles, 'staff' => $staff, 'allRoles' => User::ROLES]);
    }

    public function assign(AssignRoleRequest $request, User $user)
    {
        $data = $request->validated();

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot change your own role.');
        }

        // Never leave the system without a Super Admin.
        if ($user->hasRole('Super Admin') && $data['role'] !== 'Super Admin'
            && User::role('Super Admin')->count() <= 1) {
            return back()->with('error', 'The last Super Admin cannot be demoted.');
        }

        $old = $user->getRoleNames()->implode(', ') ?: 'none';
        $user->syncRoles([$data['role']]);

        AuditLogger::log('user.role_changed', $user, "{$old} -> {$data['role']}");

        return back()->with('success', "{$user->name} is now {$data['role']}.");
    }

    /** Super Admin creates staff (Admin / Investigator) accounts. */
    public function storeStaff(StoreStaffRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],   // hashed by the model cast
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();
        $user->assignRole($data['role']);

        AuditLogger::log('user.created', $user, "Role: {$data['role']}");

        return back()->with('success', "Account created for {$user->name}. Give them the password privately.");
    }
}
