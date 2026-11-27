<x-layouts.guest :title="$admin ? __('Admin log in') : __('Log in')">
    @if ($admin)
        <span class="badge badge-soft badge-soft-danger mb-3"><i class="bi bi-shield-check me-1"></i>{{ __('Admin') }}</span>
    @endif
    <h1 class="h4 mb-1">{{ __('Welcome back') }}</h1>
    <p class="text-secondary mb-4">{{ $admin ? __('Log in to the admin area.') : __('Log in to continue to your dashboard.') }}</p>

    <x-flash />

    <form method="POST" action="{{ route($admin ? 'admin.login.store' : 'login.store') }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
        @csrf

        <x-form.input name="email" type="email" :label="__('Email')" required autofocus autocomplete="username" />
        <x-form.input name="password" type="password" :label="__('Password')" required autocomplete="current-password" />

        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember" @checked(old('remember'))>
            <label class="form-check-label" for="remember">{{ __('Remember me') }}</label>
        </div>

        <x-submit-button class="w-100">{{ __('Log in') }}</x-submit-button>
    </form>

    {{-- Resend activation link: opens automatically when an inactive account tries to log in. --}}
    <div class="mt-4 pt-3 border-top">
        <a class="small text-secondary text-decoration-none" data-bs-toggle="collapse" href="#resend-activation" role="button" aria-expanded="{{ session('unverified_email') ? 'true' : 'false' }}" aria-controls="resend-activation">
            <i class="bi bi-envelope me-1"></i>{{ __('Didn\'t get the activation email?') }}
        </a>
        <div @class(['collapse', 'show' => session('unverified_email')]) id="resend-activation">
            <form method="POST" action="{{ route('verification.resend') }}" class="d-flex gap-2 mt-2" x-data="formSubmit" x-on:submit="submit()">
                @csrf
                <input type="email" name="email" class="form-control form-control-sm" value="{{ session('unverified_email') }}" placeholder="{{ __('your@email.com') }}" required aria-label="{{ __('Email') }}">
                <x-submit-button variant="outline-primary" class="btn-sm text-nowrap">{{ __('Resend link') }}</x-submit-button>
            </form>
        </div>
    </div>

    @unless ($admin)
        <x-slot:footer>
            {{ __('Don\'t have an account?') }} <a href="{{ route('register') }}" class="fw-medium">{{ __('Create one') }}</a>
        </x-slot:footer>
    @endunless
</x-layouts.guest>
