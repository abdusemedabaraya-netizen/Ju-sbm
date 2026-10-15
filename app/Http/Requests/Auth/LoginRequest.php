<?php

namespace App\Http\Requests\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LdapAuthenticationService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $username = trim($this->string('username'));
        $password = (string) $this->string('password');

        if ($username === '' || $password === '') {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'username' => __('auth.failed'),
            ]);
        }

        // 1. Try LDAP first.
        $ldapUser = app(LdapAuthenticationService::class)
            ->authenticate($username, $password);

        if ($ldapUser) {
            $user = User::firstOrCreate(
                ['email' => $ldapUser['email']],
                [
                    'name' => $ldapUser['name'],
                    'password' => Hash::make(Str::random(64)),
                    'email_verified_at' => now(),
                    'status' => true,
                    'role' => 'reporter',
                ]
            );

            $user->update(['name' => $ldapUser['name']]);

            $defaultRole = config('ldap.default_role', 'User');

            \Spatie\Permission\Models\Role::firstOrCreate([
                'name' => $defaultRole,
                'guard_name' => 'web',
            ]);

            if (! $user->hasRole($defaultRole)) {
                $user->assignRole($defaultRole);
            }

            Auth::login($user, $this->boolean('remember'));
            RateLimiter::clear($this->throttleKey());

            return;
        }

        // 2. Fallback for seeded/local accounts, such as superadmin@test.com.
        //    Only attempts this when the username looks like an email.
        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            if (Auth::attempt(
                ['email' => $username, 'password' => $password],
                $this->boolean('remember')
            )) {
                RateLimiter::clear($this->throttleKey());

                return;
            }
        }

        // 3. Both LDAP and local login failed.
        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => __('auth.failed'),
        ]);
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('username')) . '|' . $this->ip());
    }
}