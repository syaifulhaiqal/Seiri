@php
    $employee = $leaveRequest->employee;
@endphp

<x-layouts.app :title="__('Leave request')">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.leave_requests.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to leave requests') }}
            </a>
            <h1 class="mt-2">{{ __(':type for :name', ['type' => $leaveRequest->leaveType->name, 'name' => $employee->name]) }}</h1>
            <p class="text-secondary mb-0">
                {{ __('Requested :date', ['date' => $leaveRequest->created_at->format('j M Y, g:i A')]) }} · <x-enum-badge :value="$leaveRequest->status" />
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
                    <h2>{{ __('Request') }}</h2>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Employee') }}</dt>
                        <dd class="col-sm-8">
                            <a href="{{ route('admin.employees.show', $employee) }}">{{ $employee->name }}</a>
                            <span class="text-secondary small">· {{ $employee->employee_no }}{{ $employee->department ? ' · ' . $employee->department->name : '' }}</span>
                        </dd>

                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Dates') }}</dt>
                        <dd class="col-sm-8">@include('partials.leave-dates', ['leave' => $leaveRequest])</dd>

                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Working days') }}</dt>
                        <dd class="col-sm-8">{{ $leaveRequest->days }}</dd>

                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Reason') }}</dt>
                        <dd class="col-sm-8">{{ $leaveRequest->reason ?: '—' }}</dd>

                        @if ($leaveRequest->reviewed_at)
                            <dt class="col-sm-4 text-secondary fw-normal">{{ __('Reviewed') }}</dt>
                            <dd class="col-sm-8">
                                {{ __(':name on :date', ['name' => $leaveRequest->reviewer?->name ?? __('Unknown'), 'date' => $leaveRequest->reviewed_at->format('j M Y')]) }}
                            </dd>

                            <dt class="col-sm-4 text-secondary fw-normal">{{ __('Note') }}</dt>
                            <dd class="col-sm-8 mb-0">{{ $leaveRequest->review_note ?: '—' }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            @if ($leaveRequest->isPending())
                <div class="card mb-4">
                    <div class="card-header">
                        <h2>{{ __('Decision') }}</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.leave_requests.review', $leaveRequest) }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
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
            @include('partials.leave-balances', ['balances' => $balances, 'year' => $leaveRequest->start_date->year])

            @if ($employee->department)
                <div class="card">
                    <div class="card-header">
                        <h2>{{ __('Also away from :department', ['department' => $employee->department->name]) }}</h2>
                    </div>
                    <ul class="list-group list-group-flush">
                        @forelse ($overlapping as $other)
                            <li class="list-group-item d-flex justify-content-between gap-2">
                                <span>{{ $other->employee->name }}</span>
                                <span class="text-secondary small text-nowrap">@include('partials.leave-dates', ['leave' => $other])</span>
                            </li>
                        @empty
                            <li class="list-group-item text-secondary small">{{ __('Nobody else in this department has approved leave on these days.') }}</li>
                        @endforelse
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
