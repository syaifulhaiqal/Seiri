@php
    $editing = $employee->exists;
    $backUrl = $editing ? route('admin.employees.show', $employee) : route('admin.employees.index');
@endphp

<x-layouts.app :title="$editing ? __('Edit :name', ['name' => $employee->name]) : __('Add employee')">
    <div class="page-header">
        <div>
            <a href="{{ $backUrl }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ $editing ? __('Back to :name', ['name' => $employee->name]) : __('Back to employees') }}
            </a>
            <h1 class="mt-2">{{ $editing ? __('Edit employee') : __('Add employee') }}</h1>
            @if ($editing)
                <p class="text-secondary mb-0">{{ $employee->employee_no }}</p>
            @endif
        </div>
    </div>

    <form
        method="POST"
        action="{{ $editing ? route('admin.employees.update', $employee) : route('admin.employees.store') }}"
        x-data="formSubmit"
        x-on:submit="submit()"
        novalidate
    >
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2>{{ __('Personal details') }}</h2>
                    </div>
                    <div class="card-body">
                        <x-form.input name="name" :label="__('Full name')" :value="$employee->name" required />
                        <div class="row">
                            <div class="col-sm-6">
                                <x-form.input name="email" type="email" :label="__('Work email')" :value="$employee->email" required />
                            </div>
                            <div class="col-sm-6">
                                <x-form.input name="phone_number" type="tel" :label="__('Phone number')" :value="$employee->phone_number" placeholder="012-345 6789" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <x-form.input name="date_of_birth" type="date" :label="__('Date of birth')" :value="$employee->date_of_birth?->toDateString()" />
                            </div>
                        </div>
                        <x-form.textarea name="address" :label="__('Address')" :value="$employee->address" rows="2" />
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h2>{{ __('Employment') }}</h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <x-form.input name="job_title" :label="__('Job title')" :value="$employee->job_title" required />
                            </div>
                            <div class="col-sm-6">
                                <x-form.select name="department_id" :label="__('Department')" :options="$departments" :value="$employee->department_id" :placeholder="__('No department')" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <x-form.select name="employment_type" :label="__('Employment type')" :options="\App\Enums\EmploymentType::options()" :value="$employee->employment_type" required />
                            </div>
                            <div class="col-sm-6">
                                <x-form.select name="status" :label="__('Status')" :options="\App\Enums\EmployeeStatus::options()" :value="$employee->status" required />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <x-form.input name="join_date" type="date" :label="__('Join date')" :value="$employee->join_date?->toDateString()" required />
                            </div>
                            <div class="col-sm-6">
                                <x-form.input name="end_date" type="date" :label="__('Last working day')" :value="$employee->end_date?->toDateString()" :help="__('Required once the employee has resigned or been terminated.')" />
                            </div>
                        </div>
                        <input type="hidden" name="can_work_remotely" value="0">
                        <div class="form-check form-switch">
                            <input
                                type="checkbox"
                                role="switch"
                                id="can_work_remotely"
                                name="can_work_remotely"
                                value="1"
                                @class(['form-check-input', 'is-invalid' => $errors->has('can_work_remotely')])
                                @checked(old('can_work_remotely', $employee->can_work_remotely))
                            >
                            <label class="form-check-label" for="can_work_remotely">{{ __('Can clock in remotely (work from home)') }}</label>
                        </div>
                        <div class="form-text">{{ __('Off: clocks in only by scanning the office QR code. On: can also clock in from the My attendance page, with location shared.') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2>{{ __('System') }}</h2>
                    </div>
                    <div class="card-body">
                        <x-form.input
                            name="employee_no"
                            :label="__('Employee number')"
                            :value="$employee->employee_no"
                            :placeholder="$editing ? null : \App\Models\Employee::nextEmployeeNo()"
                            :help="$editing ? null : __('Leave blank to use the next number.')"
                        />
                        <x-form.select
                            name="user_id"
                            :label="__('Login account')"
                            :options="$users"
                            :value="$employee->user_id"
                            :placeholder="__('Not linked')"
                            :help="__('Link a user login so this employee can apply for leave and clock in.')"
                        />
                    </div>
                </div>

                @can('payroll.manage')
                <div class="card mb-4">
                    <div class="card-header">
                        <h2>{{ __('Pay') }}</h2>
                    </div>
                    <div class="card-body">
                        <x-form.input name="basic_salary" type="number" step="0.01" min="0" :label="__('Basic salary per month (:currency)', ['currency' => config('finance.currency')])" :value="$employee->basic_salary" :help="__('Leave at 0 to keep this employee out of payroll.')" />
                        <x-form.input name="monthly_pcb" type="number" step="0.01" min="0" :label="__('Monthly PCB (:currency)', ['currency' => config('finance.currency')])" :value="$employee->monthly_pcb" :help="__('Income tax deduction from the LHDN schedule or e-PCB calculator.')" />
                        <x-form.input name="bank_name" :label="__('Bank')" :value="$employee->bank_name" placeholder="Maybank" />
                        <x-form.input name="bank_account_no" :label="__('Bank account no.')" :value="$employee->bank_account_no" />
                        <div class="row">
                            <div class="col-sm-6">
                                <x-form.input name="ic_no" :label="__('IC / passport no.')" :value="$employee->ic_no" placeholder="900101-14-5678" />
                            </div>
                            <div class="col-sm-6">
                                <x-form.input name="tax_no" :label="__('Income tax no.')" :value="$employee->tax_no" placeholder="IG 12345678090" :help="__('Printed on the EA form.')" />
                            </div>
                        </div>
                    </div>
                </div>
                @endcan
            </div>
        </div>

        <div class="d-flex gap-2">
            <x-submit-button>{{ $editing ? __('Save changes') : __('Add employee') }}</x-submit-button>
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-layouts.app>
