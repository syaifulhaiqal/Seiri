<x-layouts.app :title="__('My leave')">
    <div class="page-header">
        <div>
            <h1>{{ __('My leave') }}</h1>
            <p class="text-secondary mb-0">{{ __('Apply for leave and follow your requests.') }}</p>
        </div>
    </div>

    @if (! $employee)
        @include('partials.no-employee-record')
    @else
        @include('partials.leave-balances', ['balances' => $balances, 'year' => now()->year])

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h2>{{ __('Apply for leave') }}</h2>
                    </div>
                    <div class="card-body">
                        @if ($employee->isActive())
                            <form method="POST" action="{{ route('leave.store') }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                                @csrf

                                <x-form.select name="leave_type_id" bag="apply" :label="__('Leave type')" :options="$leaveTypes" :placeholder="__('Choose a leave type')" required />
                                <div class="row">
                                    <div class="col-sm-6">
                                        <x-form.input name="start_date" type="date" bag="apply" :label="__('From')" required />
                                    </div>
                                    <div class="col-sm-6">
                                        <x-form.input name="end_date" type="date" bag="apply" :label="__('To')" required />
                                    </div>
                                </div>
                                <x-form.textarea name="reason" bag="apply" :label="__('Reason')" rows="2" />
                                <p class="small text-secondary">{{ __('Only working days count. Weekends and public holidays inside the range are not deducted.') }}</p>

                                <x-submit-button>{{ __('Send request') }}</x-submit-button>
                            </form>
                        @else
                            <p class="text-secondary mb-0">{{ __('Your employment has ended, so you can no longer apply for leave.') }}</p>
                        @endif
                    </div>
                </div>

                @if ($holidays->isNotEmpty())
                    <div class="card mt-4">
                        <div class="card-header">
                            <h2>{{ __('Upcoming public holidays') }}</h2>
                        </div>
                        <ul class="list-group list-group-flush">
                            @foreach ($holidays as $holiday)
                                <li class="list-group-item d-flex justify-content-between gap-3 small">
                                    <span>{{ $holiday->name }}</span>
                                    <span class="text-secondary text-nowrap">{{ $holiday->date->translatedFormat('D, j M Y') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h2>{{ __('My requests') }}</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3">{{ __('Dates') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Days') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="text-end pe-3"><span class="visually-hidden">{{ __('Actions') }}</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($leaveRequests as $leave)
                                    <tr>
                                        <td class="ps-3 text-nowrap">
                                            @include('partials.leave-dates', ['leave' => $leave])
                                            @if ($leave->review_note)
                                                <div class="small text-secondary text-wrap">
                                                    <i class="bi bi-chat-left-text me-1"></i>{{ $leave->review_note }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>{{ $leave->leaveType->name }}</td>
                                        <td>{{ $leave->days }}</td>
                                        <td>
                                            <x-enum-badge :value="$leave->status" />
                                            @include('partials.approval-waiting', ['item' => $leave])
                                        </td>
                                        <td class="text-end pe-3 text-nowrap">
                                            @if ($leave->isPending())
                                                <form method="POST" action="{{ route('leave.cancel', $leave) }}" x-data="formSubmit" x-on:submit="submit()">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary" x-bind:disabled="submitting">{{ __('Cancel request') }}</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-secondary py-5">
                                            <i class="bi bi-calendar2 fs-3 d-block mb-2"></i>
                                            {{ __('You have not applied for leave yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($leaveRequests->hasPages())
                        <div class="card-body border-top pb-0">
                            {{ $leaveRequests->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</x-layouts.app>
