{{-- $notification: one database notification, as a link that marks it read. --}}
@php
    $data = $notification->data;
@endphp
<a
    href="{{ route(request()->area() . 'notifications.open', $notification) }}"
    @class(['notification-item d-flex gap-3 text-decoration-none text-body', 'is-unread' => $notification->unread()])
>
    <span class="notification-icon text-{{ $data['context'] ?? 'primary' }} bg-{{ $data['context'] ?? 'primary' }}-subtle">
        <i class="bi {{ $data['icon'] ?? 'bi-bell' }}"></i>
    </span>
    <span class="flex-grow-1" style="min-width: 0">
        <span class="d-block small">{{ __($data['message'], $data['parameters'] ?? []) }}</span>
        <span class="d-block text-secondary" style="font-size: .75rem">{{ $notification->created_at->diffForHumans() }}</span>
    </span>
    @if ($notification->unread())
        <span class="notification-dot" aria-label="{{ __('Unread') }}"></span>
    @endif
</a>
