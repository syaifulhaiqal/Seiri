{{-- $attendance: null|Attendance. How (office QR or remote) and where the employee clocked in and out. --}}
@if ($attendance?->clock_in_method)
    @foreach (['in', 'out'] as $event)
        @continue (! $attendance->{"clock_{$event}_method"})
        <div class="text-nowrap">
            <span class="text-secondary">{{ $event === 'in' ? __('In') : __('Out') }}:</span>
            <x-enum-badge :value="$attendance->{'clock_' . $event . '_method'}" />
            @if ($event === 'in' && $attendance->work_location)
                <span>{{ $attendance->work_location }}</span>
            @endif
            @if ($mapUrl = $attendance->mapUrl($event))
                <a href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none" title="{{ __('Open location in Google Maps') }}">
                    <i class="bi bi-geo-alt"></i><span class="visually-hidden">{{ __('Open location in Google Maps') }}</span>
                </a>
            @endif
            <span class="text-secondary" title="{{ __('IP address') }}">{{ $attendance->{"clock_{$event}_ip"} }}</span>
        </div>
    @endforeach
@else
    <span class="text-secondary">—</span>
@endif
