@php
    $employee = $overtime->employee;
@endphp

<x-layouts.app :title="__('Overtime claim')">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.overtime.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to overtime') }}
            </a>
            <h1 class="mt-2">{{ __('Overtime for :name', ['name' => $employee->name]) }}</h1>
            <p class="text-secondary mb-0">
                {{ __('Requested :date', ['date' => $overtime->created_at->translatedFormat('j M Y, g:i A')]) }} · <x-enum-badge :value="$overtime->status" />
            </p>
        </div>
    </div>

    @error('review')
        <div class="alert alert-danger d-flex gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>{{ $message }}</div>
        </div>
    @enderror

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header">
                    <h2>{{ __('Claim') }}</h2>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Employee') }}</dt>
                        <dd class="col-sm-8">
                            <a href="{{ route('admin.employees.show', $employee) }}">{{ $employee->name }}</a>
                            <span class="text-secondary small">· {{ $employee->employee_no }}{{ $employee->department ? ' · ' . $employee->department->name : '' }}</span>
                        </dd>

                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Date') }}</dt>
                        <dd class="col-sm-8">{{ $overtime->date->translatedFormat('l, j M Y') }} · {{ $overtime->timeRange() }}</dd>

                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Hours') }}</dt>
                        <dd class="col-sm-8">
                            {{ (float) $overtime->hours }}
                            <span class="badge rounded-pill badge-soft badge-soft-{{ $overtime->day_type->context() }} ms-1">{{ $overtime->day_type->label() }} ×{{ $overtime->day_type->rate() }}</span>
                        </dd>

                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Reason') }}</dt>
                        <dd class="col-sm-8">{{ $overtime->reason }}</dd>

                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Attendance that day') }}</dt>
                        <dd class="col-sm-8">
                            @if ($attendance)
                                {{ $attendance->clock_in_at?->format('g:i A') ?? '—' }} – {{ $attendance->clock_out_at?->format('g:i A') ?? '—' }}
                            @else
                                <span class="text-secondary">{{ __('No clock-in recorded') }}</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Approved this month') }}</dt>
                        <dd class="col-sm-8">{{ __(':hours h', ['hours' => $monthHours]) }}</dd>

                        @if ($overtime->payslip)
                            <dt class="col-sm-4 text-secondary fw-normal">{{ __('Paid') }}</dt>
                            <dd class="col-sm-8">
                                <a href="{{ route('admin.payroll.payslip', [$overtime->payslip->payrollRun, $overtime->payslip]) }}">{{ $overtime->payslip->payrollRun->periodLabel() }}</a>
                            </dd>
                        @endif

                        @if ($overtime->reviewed_at)
                            <dt class="col-sm-4 text-secondary fw-normal">{{ __('Reviewed') }}</dt>
                            <dd class="col-sm-8">
                                {{ __(':name on :date', ['name' => $overtime->reviewer?->name ?? __('Unknown'), 'date' => $overtime->reviewed_at->translatedFormat('j M Y')]) }}
                            </dd>

                            <dt class="col-sm-4 text-secondary fw-normal">{{ __('Note') }}</dt>
                            <dd class="col-sm-8 mb-0">{{ $overtime->review_note ?: '—' }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            @if ($overtime->isPending())
                <div class="card mb-4">
                    <div class="card-header">
                        <h2>{{ __('Decision') }}</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.overtime.review', $overtime) }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                            @csrf
                            @method('PUT')

                            <x-form.textarea
                                name="review_note"
                                :label="__('Note to the employee')"
                                rows="2"
                                :help="__('Optional when approving. Required when rejecting.')"
                            />

                            <div class="d-flex gap-2">
                                <button type="submit" name="decision" value="approve" class="btn btn-success" x-bind:disabled="submitting">
                                    <i class="bi bi-check-lg me-1"></i>{{ __('Approve') }}
                                </button>
                                <button type="submit" name="decision" value="reject" class="btn btn-outline-danger" x-bind:disabled="submitting">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('Reject') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-5">
            @include('partials.approval-chain', ['item' => $overtime])
        </div>
    </div>
</x-layouts.app>
