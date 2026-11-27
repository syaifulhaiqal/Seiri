<x-layouts.app :title="__('Approvers for :name', ['name' => $employee->name])">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.employees.show', $employee) }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to :name', ['name' => $employee->name]) }}
            </a>
            <h1 class="mt-2">{{ __('Approvers for :name', ['name' => $employee->name]) }}</h1>
            <p class="text-secondary mb-0">{{ __('Each request goes to level 1 first, then level 2, then level 3. Leave a kind empty to send it straight to HR or finance.') }}</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-9">
            <form method="POST" action="{{ route('admin.employees.approvers.update', $employee) }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                @csrf
                @method('PUT')

                @if ($choices === [])
                    <div class="alert alert-warning">{{ __('Approvers must be employees with a login. Link a user login to the team leads and managers first.') }}</div>
                @endif

                @foreach (\App\Enums\ApprovalType::cases() as $type)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h2>{{ $type->label() }}</h2>
                        </div>
                        <div class="card-body">
                            @error("approvers.{$type->value}")
                                <div class="alert alert-danger small py-2">{{ $message }}</div>
                            @enderror
                            <div class="row">
                                @foreach (range(1, \App\Enums\ApprovalType::LEVELS) as $level)
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium" for="approver-{{ $type->value }}-{{ $level }}">{{ __('Level :level', ['level' => $level]) }}</label>
                                        <select name="approvers[{{ $type->value }}][{{ $level }}]" id="approver-{{ $type->value }}-{{ $level }}" class="form-select mb-3">
                                            <option value="">{{ $level === 1 ? __('No approver: HR / finance decides') : __('None') }}</option>
                                            @foreach ($choices as $userId => $label)
                                                <option value="{{ $userId }}" @selected((string) old("approvers.{$type->value}.{$level}", $chains[$type->value][$level] ?? '') === (string) $userId)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="d-flex gap-2">
                    <x-submit-button>{{ __('Save approvers') }}</x-submit-button>
                    <a href="{{ route('admin.employees.show', $employee) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
