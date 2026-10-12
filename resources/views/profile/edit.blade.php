<x-app-layout>
    <div class="jiacrs-profile-page">

        <div class="container-fluid">

            <div class="jiacrs-profile-header">
                <h1>Profile</h1>
                <p>Manage your JIACRS account information and security settings.</p>
            </div>

            <div class="row">

                <div class="col-lg-8">

                    {{-- Profile Information --}}
                    <div class="jiacrs-profile-card">
                        @include('profile.partials.update-profile-information-form')
                    </div>

                    {{-- Update Password --}}
                    <div class="jiacrs-profile-card">
                        @include('profile.partials.update-password-form')
                    </div>

                    {{-- Delete Account --}}
                    <div class="jiacrs-profile-card jiacrs-danger-card">
                        @include('profile.partials.delete-user-form')
                    </div>

                </div>

                <div class="col-lg-4">

                    <div class="jiacrs-profile-summary">

                        <div class="jiacrs-profile-avatar">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>

                        <h3>{{ auth()->user()->name }}</h3>

                        <p>{{ auth()->user()->email }}</p>

                        @if (auth()->user()->roles->count())
                            <span class="jiacrs-profile-role">
                                {{ auth()->user()->roles->first()->name }}
                            </span>
                        @endif

                        <div class="jiacrs-profile-divider"></div>

                        <a href="{{ route('dashboard') }}"
                           class="jiacrs-profile-dashboard-btn">
                            ← Back to Dashboard
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>
