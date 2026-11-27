<x-layouts.app :title="__('My training')">
    <div class="page-header">
        <div>
            <h1>{{ __('My training') }}</h1>
            <p class="text-secondary mb-0">
                {{ __('Courses HR has recorded for you.') }}
                @if ($employee)
                    · {{ __(':hours training hours completed this year', ['hours' => $hoursThisYear]) }}
                @endif
            </p>
        </div>
    </div>

    @if (! $employee)
        @include('partials.no-employee-record')
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">{{ __('Course') }}</th>
                            <th>{{ __('Dates') }}</th>
                            <th>{{ __('Hours') }}</th>
                            <th class="pe-3">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trainings as $training)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-medium">{{ $training->title }}</div>
                                    <div class="small text-secondary">{{ $training->provider ?: '—' }}</div>
                                </td>
                                <td class="text-nowrap">{{ \App\Support\DateRange::format($training->start_date, $training->end_date) }}</td>
                                <td>{{ $training->hours !== null ? (float) $training->hours : '—' }}</td>
                                <td class="pe-3"><x-enum-badge :value="$training->status" /></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-secondary py-5">
                                    <i class="bi bi-mortarboard fs-3 d-block mb-2"></i>
                                    {{ __('No training recorded yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-layouts.app>
