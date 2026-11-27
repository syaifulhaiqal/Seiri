<x-layouts.app :title="__('Clock in')">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card text-center">
                <div class="card-body p-4 p-sm-5">
                    <span class="stat-icon badge-soft-success d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 4rem; height: 4rem">
                        <i class="bi bi-link-45deg fs-2"></i>
                    </span>
                    <h1 class="h4">{{ __('Hi, :name', ['name' => str($employee->name)->before(' ')]) }}</h1>
                    <p class="text-secondary">
                        {{ $link->name }} · {{ now()->translatedFormat('l, j M Y') }} · <span class="fw-semibold text-body">{{ now()->format('g:i A') }}</span>
                    </p>

                    @if ($today?->clock_out_at)
                        <div class="alert alert-success mb-0">
                            <i class="bi bi-check-circle me-1"></i>{{ __('Done for today: :hours hours.', ['hours' => $today->workedHours()]) }}
                        </div>
                    @else
                        <form method="POST" action="{{ route('clock_link.store', $link->token) }}" class="text-start" x-data="remoteClock" x-on:submit.prevent="submit()" novalidate>
                            @csrf
                            <input type="hidden" name="latitude" x-bind:value="latitude">
                            <input type="hidden" name="longitude" x-bind:value="longitude">

                            @unless ($today?->clock_in_at)
                                <x-form.input name="work_location" :label="__('Working from')" :placeholder="$link->name" maxlength="100" />
                            @endunless

                            <x-submit-button :variant="$today?->clock_in_at ? 'outline-primary' : 'primary'" class="btn-lg w-100">
                                <i @class(['bi me-1', 'bi-box-arrow-right' => $today?->clock_in_at, 'bi-box-arrow-in-right' => ! $today?->clock_in_at])></i>
                                {{ $today?->clock_in_at ? __('Clock out now') : __('Clock in now') }}
                            </x-submit-button>
                            <p class="form-text text-center mb-0">
                                {{ $link->require_location
                                    ? __('Allow your browser to share your location. This link does not work without it.')
                                    : __('Your browser may ask to share your location. HR sees it with your clock-in.') }}
                            </p>
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
