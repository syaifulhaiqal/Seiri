<x-layouts.app :title="__('Leave requests')">
    <div class="page-header">
        <div>
            <h1>{{ __('Leave requests') }}</h1>
            <p class="text-secondary mb-0">
                {{ trans_choice(':count request waiting for review|:count requests waiting for review', $pendingCount) }}
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.leave_requests.index') }}" class="row g-2 align-items-center" x-data="autoSubmit">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-secondary"></i></span>
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
                        <a href="{{ route('admin.leave_requests.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">{{ __('Employee') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Dates') }}</th>
                        <th>{{ __('Days') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Requested') }}</th>
                        <th class="text-end pe-3">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leaveRequests as $leave)
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar">{{ str($leave->employee->name)->substr(0, 1) }}</span>
                                    <div class="min-w-0">
                                        <div class="fw-medium">{{ $leave->employee->name }}</div>
                                        <div class="small text-secondary">{{ $leave->employee->department?->name ?? $leave->employee->employee_no }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $leave->leaveType->name }}</td>
                            <td class="text-nowrap">@include('partials.leave-dates', ['leave' => $leave])</td>
                            <td>{{ $leave->days }}</td>
                            <td><x-enum-badge :value="$leave->status" /></td>
                            <td class="text-secondary small text-nowrap">{{ $leave->created_at->diffForHumans() }}</td>
                            <td class="text-end pe-3 text-nowrap">
                                @if ($leave->isPending())
                                    <a href="{{ route('admin.leave_requests.show', $leave) }}" class="btn btn-sm btn-primary">{{ __('Review') }}</a>
                                @else
                                    <a href="{{ route('admin.leave_requests.show', $leave) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('View') }}">
                                        <i class="bi bi-eye"></i><span class="visually-hidden">{{ __('View') }}</span>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                {{ array_filter($filters) ? __('No leave requests match your filters.') : __('No leave requests yet.') }}
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
</x-layouts.app>
