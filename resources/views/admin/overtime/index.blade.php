<x-layouts.app :title="__('Overtime')">
    <div class="page-header">
        <div>
            <h1>{{ __('Overtime') }}</h1>
            <p class="text-secondary mb-0">
                {{ trans_choice(':count claim waiting for review|:count claims waiting for review', $pendingCount) }}
                · {{ __('Approved overtime is paid in the next payroll run.') }}
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.overtime.index') }}" class="row g-2 align-items-center" x-data="autoSubmit">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text bg-body"><i class="bi bi-search text-secondary"></i></span>
                        <input type="search" name="search" class="form-control border-start-0 ps-0" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search employee name or number') }}" aria-label="{{ __('Search') }}">
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <select name="department" class="form-select" aria-label="{{ __('Department') }}" x-on:change="submit()">
                        <option value="">{{ __('All departments') }}</option>
                        @foreach ($departments as $id => $name)
                            <option value="{{ $id }}" @selected((string) ($filters['department'] ?? '') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <select name="status" class="form-select" aria-label="{{ __('Status') }}" x-on:change="submit()">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach (\App\Enums\LeaveStatus::options() as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-3 d-flex gap-2 justify-content-lg-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>{{ __('Filter') }}</button>
                    @if (array_filter($filters))
                        <a href="{{ route('admin.overtime.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">{{ __('Employee') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Hours') }}</th>
                        <th>{{ __('Day') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end pe-3">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $overtime)
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar">{{ str($overtime->employee->name)->substr(0, 1) }}</span>
                                    <div class="min-w-0">
                                        <div class="fw-medium">{{ $overtime->employee->name }}</div>
                                        <div class="small text-secondary">{{ $overtime->employee->department?->name ?? $overtime->employee->employee_no }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                {{ $overtime->date->translatedFormat('D, j M Y') }}
                                <div class="small text-secondary">{{ $overtime->timeRange() }}</div>
                            </td>
                            <td>{{ (float) $overtime->hours }}</td>
                            <td><span class="badge rounded-pill badge-soft badge-soft-{{ $overtime->day_type->context() }}">{{ $overtime->day_type->label() }} ×{{ $overtime->day_type->rate() }}</span></td>
                            <td>
                                <x-enum-badge :value="$overtime->status" />
                                @if ($overtime->payslip)
                                    <div class="small text-secondary mt-1">{{ __('Paid in :month', ['month' => $overtime->payslip->payrollRun->periodLabel()]) }}</div>
                                @endif
                            </td>
                            <td class="text-end pe-3 text-nowrap">
                                @if ($overtime->isPending())
                                    <a href="{{ route('admin.overtime.show', $overtime) }}" class="btn btn-sm btn-primary">{{ __('Review') }}</a>
                                @else
                                    <a href="{{ route('admin.overtime.show', $overtime) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('View') }}">
                                        <i class="bi bi-eye"></i><span class="visually-hidden">{{ __('View') }}</span>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-5">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                {{ array_filter($filters) ? __('No overtime claims match your filters.') : __('No overtime claims yet.') }}
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
</x-layouts.app>
