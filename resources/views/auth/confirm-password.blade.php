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

                <h1>Confirm Password</h1>

                <p>
                    Please confirm your password before continuing.
                </p>

            </div>

            <div class="jiacrs-verification-message">
                {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
            </div>

            <form method="POST"
                  action="{{ route('password.confirm') }}"
                  class="jiacrs-auth-form">

                @csrf

                <div class="jiacrs-form-group">

                    <label for="password" class="jiacrs-form-label">
                        Password
                    </label>

                    <input
                        id="password"
                        class="jiacrs-form-input"
                        type="password"
                        name="password"
                        required
                        autofocus
                        autocomplete="current-password"
                        placeholder="Enter your password"
                    >

                    <x-input-error
                        :messages="$errors->get('password')"
                        class="jiacrs-form-error"
                    />

                </div>

                <button type="submit" class="jiacrs-login-submit">
                    Confirm Password
                </button>

            </form>

            <div class="jiacrs-back-home">
                <a href="{{ route('home') }}">
                    ← Back to JIACRS Home
                </a>
            </div>

        </div>

    </div>

</x-guest-layout>
