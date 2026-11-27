<x-layouts.guest :title="__('Create account')">
    <h1 class="h4 mb-1">{{ __('Create your account') }}</h1>
    <p class="text-secondary mb-4">{{ __('We\'ll email you a link to activate it.') }}</p>

    <form method="POST" action="{{ route('register.store') }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
        @csrf

        <x-form.input name="name" :label="__('Full name')" required autofocus autocomplete="name" />
        <x-form.input name="email" type="email" :label="__('Email')" required autocomplete="email" />
        <x-form.input name="phone_number" type="tel" :label="__('Phone number')" :help="__('Optional.')" autocomplete="tel" placeholder="012-345 6789" />
        <x-form.input name="password" type="password" :label="__('Password')" required autocomplete="new-password" :help="__('At least 8 characters, with letters and numbers.')" />
        <x-form.input name="password_confirmation" type="password" :label="__('Confirm password')" required autocomplete="new-password" />

        <x-submit-button class="w-100 mt-2">{{ __('Create account') }}</x-submit-button>
    </form>

    <x-slot:footer>
        {{ __('Already have an account?') }} <a href="{{ route('login') }}" class="fw-medium">{{ __('Log in') }}</a>
    </x-slot:footer>
</x-layouts.guest>
