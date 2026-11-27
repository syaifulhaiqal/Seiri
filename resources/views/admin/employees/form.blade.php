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
            </div>
        </div>

        <div class="d-flex gap-2">
            <x-submit-button>{{ $editing ? __('Save changes') : __('Add employee') }}</x-submit-button>
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-layouts.app>
