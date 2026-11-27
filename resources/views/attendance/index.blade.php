<x-layouts.app :title="__('My attendance')">
    <div class="page-header">
        <div>
            <h1>{{ __('My attendance') }}</h1>
            <p class="text-secondary mb-0">{{ now()->translatedFormat('l, j M Y') }}</p>
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
                            <dd class="col-6 fw-semibold">
                                {{ $today?->clock_in_at?->format('g:i A') ?? '—' }}
                                @if ($today?->clock_in_method)
                                    <x-enum-badge :value="$today->clock_in_method" class="ms-1" />
                                @endif
                            </dd>

                            <dt class="col-6 text-secondary fw-normal">{{ __('Clock out') }}</dt>
                            <dd class="col-6 fw-semibold">
                                {{ $today?->clock_out_at?->format('g:i A') ?? '—' }}
                                @if ($today?->clock_out_method)
                                    <x-enum-badge :value="$today->clock_out_method" class="ms-1" />
                                @endif
                            </dd>

                            <dt class="col-6 text-secondary fw-normal">{{ __('Starts at') }}</dt>
                            <dd class="col-6 mb-0">{{ \Illuminate\Support\Carbon::parse(config('hr.work_start'))->format('g:i A') }}</dd>
                        </dl>

                        @if (! $employee->isActive())
                            <p class="text-secondary small mb-0">{{ __('Your employment has ended, so clocking in is turned off.') }}</p>
                        @elseif ($today?->clock_out_at)
                            <div class="alert alert-success small mb-0">
                                <i class="bi bi-check-circle me-1"></i>{{ __('Done for today: :hours hours.', ['hours' => $today->workedHours()]) }}
                            </div>
                        @else
                            <div class="d-flex gap-2 align-items-start small text-secondary mb-3">
                                <i class="bi bi-qr-code-scan fs-5 lh-1"></i>
                                <span>{{ __('At the office, scan the QR code on the office screen with your phone camera.') }}</span>
                            </div>

                            @if ($employee->can_work_remotely)
                                <form method="POST" action="{{ route($today?->clock_in_at ? 'attendance.clock_out' : 'attendance.clock_in') }}" x-data="remoteClock" x-on:submit.prevent="submit()" novalidate>
                                    @csrf
                                    <input type="hidden" name="latitude" x-bind:value="latitude">
                                    <input type="hidden" name="longitude" x-bind:value="longitude">

                                    @unless ($today?->clock_in_at)
                                        <x-form.input name="work_location" :label="__('Working from')" :placeholder="__('e.g. Home, client site')" maxlength="100" />
                                    @endunless

                                    <x-submit-button :variant="$today?->clock_in_at ? 'outline-primary' : 'primary'" class="w-100">
                                        <i @class(['bi me-1', 'bi-box-arrow-right' => $today?->clock_in_at, 'bi-house' => ! $today?->clock_in_at])></i>
                                        {{ $today?->clock_in_at ? __('Clock out remotely') : __('Clock in remotely') }}
                                    </x-submit-button>
                                    <p class="form-text mb-0">{{ __('Your browser may ask to share your location. HR sees it with your clock-in.') }}</p>
                                </form>
                            @endif
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
