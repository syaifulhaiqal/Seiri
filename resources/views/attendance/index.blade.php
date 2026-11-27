<x-layouts.app :title="__('My attendance')">
    <div class="page-header">
        <div>
            <h1>{{ __('My attendance') }}</h1>
            <p class="text-secondary mb-0">{{ now()->format('l, j M Y') }}</p>
        </div>
    </div>

    @if (! $employee)
        @include('partials.no-employee-record')
    @else
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h2>{{ __('Today') }}</h2>
                    </div>
                    <div class="card-body">
                        <dl class="row small mb-4">
                            <dt class="col-6 text-secondary fw-normal">{{ __('Clock in') }}</dt>
                            <dd class="col-6 fw-semibold">{{ $today?->clock_in_at?->format('g:i A') ?? '—' }}</dd>

                            <dt class="col-6 text-secondary fw-normal">{{ __('Clock out') }}</dt>
                            <dd class="col-6 fw-semibold">{{ $today?->clock_out_at?->format('g:i A') ?? '—' }}</dd>

                            <dt class="col-6 text-secondary fw-normal">{{ __('Starts at') }}</dt>
                            <dd class="col-6 mb-0">{{ \Illuminate\Support\Carbon::parse(config('hr.work_start'))->format('g:i A') }}</dd>
                        </dl>

                        @if (! $employee->isActive())
                            <p class="text-secondary small mb-0">{{ __('Your employment has ended, so clocking in is turned off.') }}</p>
                        @elseif (! $today?->clock_in_at)
                            <form method="POST" action="{{ route('attendance.clock_in') }}" x-data="formSubmit" x-on:submit="submit()">
                                @csrf
                                <x-submit-button class="w-100"><i class="bi bi-box-arrow-in-right me-1"></i>{{ __('Clock in') }}</x-submit-button>
                            </form>
                        @elseif (! $today->clock_out_at)
                            <form method="POST" action="{{ route('attendance.clock_out') }}" x-data="formSubmit" x-on:submit="submit()">
                                @csrf
                                <x-submit-button variant="outline-primary" class="w-100"><i class="bi bi-box-arrow-right me-1"></i>{{ __('Clock out') }}</x-submit-button>
                            </form>
                        @else
                            <div class="alert alert-success small mb-0">
                                <i class="bi bi-check-circle me-1"></i>{{ __('Done for today: :hours hours.', ['hours' => $today->workedHours()]) }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h2>{{ __('Last 30 days') }}</h2>
                    </div>
                    @include('partials.attendance-table', ['attendances' => $attendances])
                </div>
            </div>
        </div>
    @endif
</x-layouts.app>
