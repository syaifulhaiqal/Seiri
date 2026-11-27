<x-layouts.app :title="__('Notifications')">
    <div class="page-header">
        <div>
            <h1>{{ __('Notifications') }}</h1>
            <p class="text-secondary mb-0">{{ __('Updates about leave, claims, payroll and your access.') }}</p>
        </div>
        @if (auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route(request()->area() . 'notifications.read_all') }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-check2-all me-1"></i>{{ __('Mark all as read') }}</button>
            </form>
        @endif
    </div>

    <div class="card">
        @forelse ($notifications as $notification)
            <div @class(['border-top' => ! $loop->first])>
                @include('notifications.item')
            </div>
        @empty
            <div class="card-body text-center text-secondary py-5">
                <i class="bi bi-bell-slash fs-2 d-block mb-2"></i>
                {{ __('No notifications yet.') }}
            </div>
        @endforelse
    </div>

    <div class="mt-3">
        {{ $notifications->links() }}
    </div>
</x-layouts.app>
