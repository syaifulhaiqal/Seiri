<x-layouts.app :title="__('Office clock-in')">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card text-center">
                <div class="card-body p-4 p-sm-5">
                    <span class="stat-icon badge-soft-primary d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 4rem; height: 4rem">
                        <i class="bi bi-qr-code-scan fs-2"></i>
                    </span>
                    <h1 class="h4">{{ __('Hi, :name', ['name' => str($employee->name)->before(' ')]) }}</h1>
                    <p class="text-secondary">{{ now()->translatedFormat('l, j M Y') }} · <span class="fw-semibold text-body">{{ now()->format('g:i A') }}</span></p>

                    @if ($today?->clock_out_at)
                        <div class="alert alert-success mb-0">
                            <i class="bi bi-check-circle me-1"></i>{{ __('Done for today: :hours hours.', ['hours' => $today->workedHours()]) }}
                        </div>
                    @else
                        <form method="POST" action="{{ route('attendance.qr') }}" x-data="formSubmit" x-on:submit="submit()">
                            @csrf
                            <x-submit-button :variant="$today?->clock_in_at ? 'outline-primary' : 'primary'" class="btn-lg w-100">
                                <i @class(['bi me-1', 'bi-box-arrow-right' => $today?->clock_in_at, 'bi-box-arrow-in-right' => ! $today?->clock_in_at])></i>
                                {{ $today?->clock_in_at ? __('Clock out now') : __('Clock in now') }}
                            </x-submit-button>
                        </form>
                        @if ($today?->clock_in_at)
                            <p class="small text-secondary mt-3 mb-0">{{ __('You clocked in at :time.', ['time' => $today->clock_in_at->format('g:i A')]) }}</p>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
