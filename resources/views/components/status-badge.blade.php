@props(['user'])

@if ($user->hasVerifiedEmail())
    <span class="badge rounded-pill badge-soft badge-soft-success"><i class="bi bi-check-circle me-1"></i>{{ __('Active') }}</span>
@else
    <span class="badge rounded-pill badge-soft badge-soft-warning"><i class="bi bi-hourglass-split me-1"></i>{{ __('Pending email') }}</span>
@endif
