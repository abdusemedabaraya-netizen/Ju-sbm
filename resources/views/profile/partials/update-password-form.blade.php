<section>
    <div class="jiacrs-profile-section-header">

        <h2>Update Password</h2>

        <p>
            Ensure your account is using a long, random password to stay secure.
        </p>

    </div>

    <form method="post"
          action="{{ route('password.update') }}"
          class="jiacrs-profile-form">

        @csrf
        @method('put')

        <div class="jiacrs-profile-form-group">

            <label
                for="update_password_current_password"
                class="jiacrs-profile-label">
                Current Password
            </label>

            <input
                id="update_password_current_password"
                name="current_password"
                type="password"
                class="jiacrs-profile-input"
                autocomplete="current-password"
            >

            <x-input-error
                class="jiacrs-profile-error"
                :messages="$errors->updatePassword->get('current_password')"
            />

        </div>

        <div class="jiacrs-profile-form-group">

            <label
                for="update_password_password"
                class="jiacrs-profile-label">
                New Password
            </label>

            <input
                id="update_password_password"
                name="password"
                type="password"
                class="jiacrs-profile-input"
                autocomplete="new-password"
            >

            <x-input-error
                class="jiacrs-profile-error"
                :messages="$errors->updatePassword->get('password')"
            />

        </div>

        <div class="jiacrs-profile-form-group">

            <label
                for="update_password_password_confirmation"
                class="jiacrs-profile-label">
                Confirm Password
            </label>

            <input
                id="update_password_password_confirmation"
                name="password_confirmation"
                type="password"
                class="jiacrs-profile-input"
                autocomplete="new-password"
            >

            <x-input-error
                class="jiacrs-profile-error"
                :messages="$errors->updatePassword->get('password_confirmation')"
            />

        </div>

        <div class="jiacrs-profile-form-actions">

            <button type="submit"
                    class="jiacrs-profile-save-btn">
                Save
            </button>

            @if (session('status') === 'password-updated')
                <span class="jiacrs-profile-saved">
                    Saved.
                </span>
            @endif

        </div>

    </form>

</section>
