@php
    $editing = $leaveType->exists;
@endphp

<x-layouts.app :title="$editing ? __('Edit :name', ['name' => $leaveType->name]) : __('Add leave type')">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.leave_types.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to leave types') }}
            </a>
            <h1 class="mt-2">{{ $editing ? __('Edit leave type') : __('Add leave type') }}</h1>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <form
                method="POST"
                action="{{ $editing ? route('admin.leave_types.update', $leaveType) : route('admin.leave_types.store') }}"
                x-data="formSubmit"
                x-on:submit="submit()"
                novalidate
            >
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif

                <div class="card mb-4">
                    <div class="card-header">
                        <h2>{{ __('Details') }}</h2>
                    </div>
                    <div class="card-body">
                        <x-form.input name="name" :label="__('Name')" :value="$leaveType->name" required />
                        <div class="row">
                            <div class="col-sm-6">
                                <x-form.input
                                    name="days_per_year"
                                    type="number"
                                    min="0"
                                    max="365"
                                    :label="__('Days per year')"
                                    :value="$leaveType->days_per_year"
                                    required
                                    :help="__('Working days each employee gets per calendar year. Enter 0 for no limit.')"
                                />
                            </div>
                            <div class="col-sm-6 mb-3">
                                <span class="form-label fw-medium d-block">{{ __('Pay') }}</span>
                                <input type="hidden" name="is_paid" value="0">
                                <div class="form-check form-switch mt-2">
                                    <input
                                        type="checkbox"
                                        role="switch"
                                        id="is_paid"
                                        name="is_paid"
                                        value="1"
                                        @class(['form-check-input', 'is-invalid' => $errors->has('is_paid')])
                                        @checked(old('is_paid', $leaveType->is_paid))
                                    >
                                    <label class="form-check-label" for="is_paid">{{ __('Paid leave') }}</label>
                                    @error('is_paid')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <x-submit-button>{{ $editing ? __('Save changes') : __('Add leave type') }}</x-submit-button>
                    <a href="{{ route('admin.leave_types.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
