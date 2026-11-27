<x-layouts.app :title="__('My EA forms')">
    <div class="page-header">
        <div>
            <a href="{{ route('payslips.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to my payslips') }}
            </a>
            <h1 class="mt-2">{{ __('My EA forms') }}</h1>
            <p class="text-secondary mb-0">{{ __('Your yearly pay and deductions, for your income tax return.') }}</p>
        </div>
    </div>

    @if (! $employee)
        @include('partials.no-employee-record')
    @else
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <ul class="list-group list-group-flush">
                        @forelse ($years as $year)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="fw-medium">{{ __('EA form :year', ['year' => $year]) }}</span>
                                <a href="{{ route('ea_forms.show', $year) }}" class="btn btn-sm btn-outline-secondary">{{ __('View') }}</a>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-secondary py-5">
                                <i class="bi bi-file-earmark-text fs-3 d-block mb-2"></i>
                                {{ __('Your EA form appears here after your first approved payroll.') }}
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    @endif
</x-layouts.app>
