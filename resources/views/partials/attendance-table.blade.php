{{-- $attendances: Attendance records, newest first --}}
<div class="table-responsive">
    <table class="table mb-0">
        <thead>
            <tr>
                <th class="ps-3">{{ __('Date') }}</th>
                <th>{{ __('Clock in') }}</th>
                <th>{{ __('Clock out') }}</th>
                <th>{{ __('Hours') }}</th>
                <th class="pe-3">{{ __('Status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attendances as $attendance)
                <tr>
                    <td class="ps-3 text-nowrap">{{ $attendance->date->format('D, j M') }}</td>
                    <td>{{ $attendance->clock_in_at?->format('g:i A') ?? '—' }}</td>
                    <td>{{ $attendance->clock_out_at?->format('g:i A') ?? '—' }}</td>
                    <td>{{ $attendance->workedHours() ?? '—' }}</td>
                    <td class="pe-3">
                        <x-enum-badge :value="$attendance->isLate() ? \App\Enums\AttendanceStatus::Late : \App\Enums\AttendanceStatus::Present" />
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-secondary py-4">{{ __('No attendance recorded yet.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
