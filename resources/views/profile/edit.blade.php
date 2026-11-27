<x-layouts.app :title="__('Profile')">
    <div class="page-header">
        <div>
            <h1>{{ __('Profile') }}</h1>
            <p class="text-secondary mb-0">{{ __('Manage your account details and password.') }}</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h2>{{ __('Account information') }}</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route(request()->area() . 'profile.update') }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                        @csrf
                        @method('PUT')

                        <x-form.input name="name" :label="__('Full name')" :value="$user->name" required autocomplete="name" />
                        <x-form.input
                            name="email"
                            type="email"
                            :label="__('Email')"
                            :value="$user->email"
                            required
                            autocomplete="email"
                            :help="__('If you change your email, you will be logged out until you confirm the new address.')"
                        />
                        <x-form.input name="phone_number" type="tel" :label="__('Phone number')" :value="$user->phone_number" autocomplete="tel" placeholder="012-345 6789" />

                        <x-submit-button>{{ __('Save changes') }}</x-submit-button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card" id="change-password">
                <div class="card-header">
                    <h2>{{ __('Change password') }}</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route(request()->area() . 'profile.update_password') }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                        @csrf
                        @method('PUT')

                        <x-form.input name="current_password" type="password" bag="updatePassword" :label="__('Current password')" required autocomplete="current-password" />
                        <x-form.input name="password" type="password" bag="updatePassword" :label="__('New password')" required autocomplete="new-password" :help="__('At least 8 characters, with letters and numbers.')" />
                        <x-form.input name="password_confirmation" type="password" bag="updatePassword" :label="__('Confirm new password')" required autocomplete="new-password" />

                        <x-submit-button>{{ __('Update password') }}</x-submit-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
