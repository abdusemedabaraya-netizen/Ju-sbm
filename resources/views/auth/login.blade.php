<x-guest-layout>

    <div class="jiacrs-auth-page">

        <div class="jiacrs-auth-card">

            {{-- Logo / Branding --}}
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

                <h1>Welcome Back</h1>

                <p>
                    Sign in to access your JIACRS account
                </p>

            </div>


            {{-- Session Status --}}
            <x-auth-session-status
                class="jiacrs-auth-status"
                :status="session('status')"
            />


            {{-- Login Form --}}
            <form method="POST" action="{{ route('login') }}" class="jiacrs-auth-form">

                @csrf


                {{-- Email --}}
                <div class="jiacrs-form-group">

                    <label for="email" class="jiacrs-form-label">
                        Email Address
                    </label>

                    <input
                        id="email"
                        class="jiacrs-form-input"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="Enter your email"
                    >

                    <x-input-error
                        :messages="$errors->get('email')"
                        class="jiacrs-form-error"
                    />

                </div>


                {{-- Password --}}
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
                        autocomplete="current-password"
                        placeholder="Enter your password"
                    >

                    <x-input-error
                        :messages="$errors->get('password')"
                        class="jiacrs-form-error"
                    />

                </div>


                {{-- Remember Me --}}
                <div class="jiacrs-login-options">

                    <label class="jiacrs-remember">

                        <input
                            id="remember_me"
                            type="checkbox"
                            name="remember"
                        >

                        <span>Remember me</span>

                    </label>


                    @if (Route::has('password.request'))

                        <a
                            href="{{ route('password.request') }}"
                            class="jiacrs-forgot-link"
                        >
                            Forgot password?
                        </a>

                    @endif

                </div>


                {{-- Login Button --}}
                <button type="submit" class="jiacrs-login-submit">
                    Log In
                </button>


                <!-- {{-- Register Link --}}
                <div class="jiacrs-auth-register">

                    <span>
                        Don't have an account?
                    </span>

                    @if (Route::has('register'))

                        <a href="{{ route('register') }}">
                            Create an account
                        </a>

                    @endif

                </div> -->

            </form>


            {{-- Anonymous Reporting --}}
            <div class="jiacrs-anonymous-info">

                <div class="jiacrs-anonymous-icon">
                    🔒
                </div>

                <div>
                    <strong>Want to remain anonymous?</strong>

                    <p>
                        You can submit a report without creating an account.
                    </p>

                    <a href="{{ route('reports.anonymous') }}">
                        Submit Anonymous Report
                    </a>
                </div>

            </div>


            {{-- Back to Home --}}
            <div class="jiacrs-back-home">

                <a href="{{ route('home') }}">
                    ← Back to JIACRS Home
                </a>

            </div>

        </div>

    </div>

</x-guest-layout>
