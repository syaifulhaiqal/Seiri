{{-- $leave: a LeaveRequest --}}
@if ($leave->start_date->isSameDay($leave->end_date))
    {{ $leave->start_date->format('D, j M Y') }}
@elseif ($leave->start_date->isSameMonth($leave->end_date))
    {{ $leave->start_date->format('j') }}–{{ $leave->end_date->format('j M Y') }}
@else
    {{ $leave->start_date->format('j M') }} – {{ $leave->end_date->format('j M Y') }}
@endif
