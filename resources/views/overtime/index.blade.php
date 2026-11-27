<x-layouts.app :title="__('My overtime')">
    <div class="page-header">
        <div>
            <h1>{{ __('My overtime') }}</h1>
            <p class="text-secondary mb-0">{{ __('Claim overtime you worked. Once approved, it is paid in the next payroll.') }}</p>
        </div>
    </div>

    @if (! $employee)
        @include('partials.no-employee-record')
    @else
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h2>{{ __('Claim overtime') }}</h2>
                    </div>
                    <div class="card-body">
                        @if ($employee->isActive())
                            <form method="POST" action="{{ route('overtime.store') }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                                @csrf

                                <x-form.input name="date" type="date" bag="apply" :label="__('Date worked')" :max="today()->toDateString()" required />
                                <div class="row">
                                    <div class="col-6">
                                        <x-form.input name="start_time" type="time" bag="apply" :label="__('From')" required />
                                    </div>
                                    <div class="col-6">
                                        <x-form.input name="end_time" type="time" bag="apply" :label="__('To')" required />
                                    </div>
                                </div>
                                <x-form.textarea name="reason" bag="apply" :label="__('What you worked on')" rows="2" required />

                                <p class="small text-secondary">
                                    {{ __('Rates: :normal× on working days, :rest× on rest days, :holiday× on public holidays, of your hourly rate (basic ÷ :days days ÷ :hours hours).', [
                                        'normal' => \App\Enums\OvertimeDayType::Normal->rate(),
                                        'rest' => \App\Enums\OvertimeDayType::RestDay->rate(),
                                        'holiday' => \App\Enums\OvertimeDayType::PublicHoliday->rate(),
                                        'days' => config('hr.overtime.days_per_month'),
                                        'hours' => config('hr.overtime.hours_per_day'),
                                    ]) }}
                                </p>

                                <x-submit-button>{{ __('Send claim') }}</x-submit-button>
                            </form>
                        @else
                            <p class="text-secondary mb-0">{{ __('Your employment has ended, so you can no longer claim overtime.') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h2>{{ __('My claims') }}</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3">{{ __('Date') }}</th>
                                    <th>{{ __('Hours') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="text-end pe-3"><span class="visually-hidden">{{ __('Actions') }}</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($requests as $overtime)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="text-nowrap">{{ $overtime->date->translatedFormat('D, j M Y') }}</div>
                                            <div class="small text-secondary">{{ $overtime->timeRange() }}</div>
                                            @if ($overtime->review_note)
                                                <div class="small text-secondary"><i class="bi bi-chat-left-text me-1"></i>{{ $overtime->review_note }}</div>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            {{ (float) $overtime->hours }} h
                                            <span class="badge rounded-pill badge-soft badge-soft-{{ $overtime->day_type->context() }} ms-1">×{{ $overtime->day_type->rate() }}</span>
                                        </td>
                                        <td>
                                            <x-enum-badge :value="$overtime->status" />
                                            @include('partials.approval-waiting', ['item' => $overtime])
                                            @if ($overtime->payslip)
                                                <div class="small text-secondary mt-1">{{ __('Paid in :month', ['month' => $overtime->payslip->payrollRun->periodLabel()]) }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end pe-3 text-nowrap">
                                            @if ($overtime->isPending())
                                                <form method="POST" action="{{ route('overtime.cancel', $overtime) }}" x-data="formSubmit" x-on:submit="submit()">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary" x-bind:disabled="submitting">{{ __('Cancel claim') }}</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-secondary py-5">
                                            <i class="bi bi-hourglass fs-3 d-block mb-2"></i>
                                            {{ __('You have not claimed overtime yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($requests->hasPages())
                        <div class="card-body border-top pb-0">
                            {{ $requests->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</x-layouts.app>
