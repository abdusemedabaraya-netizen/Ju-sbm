<x-guest-layout>

    <div class="jiacrs-auth-page">

        <div class="jiacrs-auth-card">

            <div class="jiacrs-auth-header">

                <a href="{{ route('home') }}" class="jiacrs-auth-brand">

                    <img src="{{ asset('images/jimma-logo.png') }}"
                         alt="Jimma University Logo"
                         class="jiacrs-auth-logo">

                    <div>
                        <div class="jiacrs-auth-university">
                            JIMMA UNIVERSITY
                        </div>

                        <div class="jiacrs-auth-system">
                            Integrity and Anti-Corruption Reporting System
                        </div>
                    </div>

                </a>

                <h1>Reset Password</h1>

                <p>
                    Create a new password for your JIACRS account.
                </p>

            </div>

            <form method="POST"
                  action="{{ route('password.store') }}"
                  class="jiacrs-auth-form">

                @csrf

                <input type="hidden"
                       name="token"
                       value="{{ $request->route('token') }}">

                <div class="jiacrs-form-group">

                    <label for="email" class="jiacrs-form-label">
                        Email Address
                    </label>

                    <input
                        id="email"
                        class="jiacrs-form-input"
                        type="email"
                        name="email"
                        value="{{ old('email', $request->email) }}"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="Enter your email address"
                    >

                    <x-input-error
                        :messages="$errors->get('email')"
                        class="jiacrs-form-error"
                    />

                </div>

                <div class="jiacrs-form-group">

                    <label for="password" class="jiacrs-form-label">
                        New Password
                    </label>

                    <input
                        id="password"
                        class="jiacrs-form-input"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        placeholder="Enter your new password"
                    >

                    <x-input-error
                        :messages="$errors->get('password')"
                        class="jiacrs-form-error"
                    />

                </div>

                <div class="jiacrs-form-group">

                    <label for="password_confirmation"
                           class="jiacrs-form-label">
                        Confirm New Password
                    </label>

                    <input
                        id="password_confirmation"
                        class="jiacrs-form-input"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Confirm your new password"
                    >

                    <x-input-error
                        :messages="$errors->get('password_confirmation')"
                        class="jiacrs-form-error"
                    />

                </div>

                <button type="submit" class="jiacrs-login-submit">
                    Reset Password
                </button>

                <div class="jiacrs-auth-register">

                    <span>Remember your password?</span>

                    <a href="{{ route('login') }}">
                        Log in
                    </a>

                </div>

            </form>

            <div class="jiacrs-back-home">

                <a href="{{ route('home') }}">
                    ← Back to JIACRS Home
                </a>

            </div>

        </div>

    </div>

</x-guest-layout>
