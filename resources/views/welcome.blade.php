<x-layouts.guest :title="__('Welcome')">
    <h1 class="h4 mb-1 text-center">{{ __('Welcome to :app', ['app' => config('app.name')]) }}</h1>
    <p class="text-secondary mb-4 text-center">{{ __('Choose where you want to log in.') }}</p>

    <div class="d-grid gap-2">
        <a href="{{ route('login') }}" class="btn btn-primary"><i class="bi bi-person me-2"></i>{{ __('User login') }}</a>
        <a href="{{ route('admin.login') }}" class="btn btn-outline-secondary"><i class="bi bi-shield-check me-2"></i>{{ __('Admin login') }}</a>
    </div>
</x-layouts.guest>
