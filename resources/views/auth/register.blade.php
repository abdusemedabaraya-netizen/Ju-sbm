 <x-guest-layout>
    <div class="jiacrs-auth-page">

        <div class="jiacrs-auth-card">

            {{-- Branding --}}
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

                <h1>Create Your Account</h1>

                <p>
                    Register to submit and track your reports
                </p>

            </div>


            {{-- Register Form --}}
            <form method="POST"
                  action="{{ route('register') }}"
                  class="jiacrs-auth-form">

                @csrf


                {{-- Full Name --}}
                <div class="jiacrs-form-group">

                    <label for="name" class="jiacrs-form-label">
                        Full Name
                    </label>

                    <input
                        id="name"
                        class="jiacrs-form-input"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Enter your full name"
                    >

                    <x-input-error
                        :messages="$errors->get('name')"
                        class="jiacrs-form-error"
                    />

                </div>


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
                        autocomplete="username"
                        placeholder="Enter your email address"
                    >

                    <x-input-error
                        :messages="$errors->get('email')"
                        class="jiacrs-form-error"
                    />

                </div>


                {{-- User Type --}}
                <div class="jiacrs-form-group">

                    <label for="user_type" class="jiacrs-form-label">
                        User Type
                    </label>

                    <select
                        id="user_type"
                        name="user_type"
                        class="jiacrs-form-input"
                        required
                    >

                        <option value="">
                            Select your user type
                        </option>

                        <option value="student"
                            {{ old('user_type') == 'student' ? 'selected' : '' }}>
                            Student
                        </option>

                        <option value="staff"
                            {{ old('user_type') == 'staff' ? 'selected' : '' }}>
                            University Staff
                        </option>

                        <option value="other"
                            {{ old('user_type') == 'other' ? 'selected' : '' }}>
                            Other
                        </option>

                    </select>
                        <x-input-error
                        :messages="$errors->get('user_type')"
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
                        autocomplete="new-password"
                        placeholder="Create a password"
                    >

                    <x-input-error
                        :messages="$errors->get('password')"
                        class="jiacrs-form-error"
                    />

                </div>


                {{-- Confirm Password --}}
                <div class="jiacrs-form-group">

                    <label for="password_confirmation"
                           class="jiacrs-form-label">

                        Confirm Password

                    </label>

                    <input
                        id="password_confirmation"
                        class="jiacrs-form-input"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Confirm your password"
                    >

                    <x-input-error
                        :messages="$errors->get('password_confirmation')"
                        class="jiacrs-form-error"
                    />

                </div>


                {{-- Terms --}}
                <div class="jiacrs-terms">

                    <label>

                        <input
                            type="checkbox"
                            name="terms"
                            required
                        >

                        <span>
                            I agree to use the JIACRS system responsibly
                            and provide truthful information.
                        </span>

                    </label>

                </div>


                {{-- Register Button --}}
                <button
                    type="submit"
                    class="jiacrs-login-submit"
                >
                    Create Account
                </button>


                {{-- Login Link --}}
                <div class="jiacrs-auth-register">

                    <span>
                        Already have an account?
                    </span>

                    <a href="{{ route('login') }}">
                        Log in
                    </a>

                </div>

            </form>


            {{-- Anonymous Reporting --}}
            <div class="jiacrs-anonymous-info">

                <div class="jiacrs-anonymous-icon">
                    🔒
                </div>

                <div>

                    <strong>
                        Don't want to create an account?
                    </strong>

                    <p>
                        You can submit a report anonymously without registering.
                    </p>

                    <a href="{{ route('reports.anonymous') }}">
                        Submit Anonymous Report
                    </a>

                </div>

            </div>


            {{-- Back Home --}}
            <div class="jiacrs-back-home">

                <a href="{{ route('home') }}">
                    ← Back to JIACRS Home
                </a>

            </div>

        </div>

    </div>

</x-guest-layout>