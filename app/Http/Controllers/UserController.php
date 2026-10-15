<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserFilterRequest;
use App\Models\User;

class UserController extends Controller
{
    public function index(UserFilterRequest $request)
    {
        $search = $request->validated('search');

        $users = User::with('roles:id,name')
            ->when($search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load('roles:id,name');

        return view('users.show', compact('user'));
    }
}
