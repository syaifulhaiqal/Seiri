<x-layouts.app :title="__('Office clock-in QR')">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.attendance.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to attendance') }}
            </a>
            <h1 class="mt-2">{{ __('Office clock-in QR') }}</h1>
            <p class="text-secondary mb-0">{{ __('Show this on a screen or tablet at the office entrance. Staff scan it with their phone camera to clock in and out.') }}</p>
        </div>
    </div>

    <div class="qr-kiosk card" x-data="qrKiosk(@js(route('admin.attendance.qr_code')), {{ $refreshSeconds }})">
        <div class="card-body text-center py-5">
            <p class="text-uppercase fw-semibold text-secondary small mb-1">{{ config('app.name') }}</p>
            <h2 class="h3 mb-4">{{ __('Scan to clock in or out') }}</h2>

            <div class="qr-kiosk-code d-inline-block p-3 rounded-3 bg-white" x-ref="code">{!! $svg !!}</div>

            <p class="text-secondary small mt-4 mb-1">
                <i class="bi bi-arrow-repeat me-1"></i>{{ trans_choice('The code changes every :seconds second. Photos of it stop working after :minutes minutes.|The code changes every :seconds seconds. Photos of it stop working after :minutes minutes.', $refreshSeconds, ['seconds' => $refreshSeconds, 'minutes' => config('hr.qr_valid_minutes')]) }}
            </p>
            <p class="text-danger small mb-0" x-show="offline" x-cloak><i class="bi bi-wifi-off me-1"></i>{{ __('Cannot reach the server. The code will update when the connection is back.') }}</p>

            <button type="button" class="btn btn-outline-secondary mt-4 qr-kiosk-fullscreen" x-on:click="fullscreen()">
                <i class="bi bi-arrows-fullscreen me-1"></i>{{ __('Full screen') }}
            </button>
        </div>
    </div>
</x-layouts.app>
