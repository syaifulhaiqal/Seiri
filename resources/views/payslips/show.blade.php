<x-layouts.app :title="__('Payslip :month', ['month' => $run->periodLabel()])">
    <div class="page-header d-print-none">
        <div>
            <a href="{{ route('payslips.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to my payslips') }}
            </a>
            <h1 class="mt-2">{{ __('Payslip :month', ['month' => $run->periodLabel()]) }}</h1>
        </div>
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>{{ __('Print') }}
        </button>
    </div>

    <div class="row">
        <div class="col-xl-9">
            @include('partials.payslip-sheet')
        </div>
    </div>
</x-layouts.app>
