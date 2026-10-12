<section>
    <div class="jiacrs-profile-section-header">
        <h2>Profile Information</h2>

        <p>
            Update your account's profile information and email address.
        </p>
    </div>

    <form id="send-verification"
          method="post"
          action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post"
          action="{{ route('profile.update') }}"
          class="jiacrs-profile-form">

        @csrf
        @method('patch')

        <div class="jiacrs-profile-form-group">

            <label for="name" class="jiacrs-profile-label">
                Name
            </label>

            <input
                id="name"
                name="name"
                type="text"
                class="jiacrs-profile-input"
                value="{{ old('name', $user->name) }}"
                required
                autofocus
                autocomplete="name"
            >

            <x-input-error
                class="jiacrs-profile-error"
                :messages="$errors->get('name')"
            />

        </div>

        <div class="jiacrs-profile-form-group">

            <label for="email" class="jiacrs-profile-label">
                Email
            </label>

            <input
                id="email"
                name="email"
                type="email"
                class="jiacrs-profile-input"
                value="{{ old('email', $user->email) }}"
                required
                autocomplete="username"
            >

            <x-input-error
                class="jiacrs-profile-error"
                :messages="$errors->get('email')"
            />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())

                <div class="jiacrs-email-warning">

                    <p>
                        Your email address is unverified.
                    </p>

                    <button
                        form="send-verification"
                        class="jiacrs-link-button">
                        Click here to re-send the verification email.
                    </button>

                    @if (session('status') === 'verification-link-sent')
                        <p class="jiacrs-verification-success">
                            A new verification link has been sent to your email address.
                        </p>
                    @endif

                </div>

            @endif

        </div>

        <div class="jiacrs-profile-form-actions">

            <button type="submit"
                    class="jiacrs-profile-save-btn">
                Save
            </button>

            @if (session('status') === 'profile-updated')
                <span class="jiacrs-profile-saved">
                    Saved.
                </span>
            @endif

        </div>

    </form>

</section>

