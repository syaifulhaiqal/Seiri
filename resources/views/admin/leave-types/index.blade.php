<x-layouts.app :title="__('Leave types')">
    <div class="page-header">
        <div>
            <h1>{{ __('Leave types') }}</h1>
            <p class="text-secondary mb-0">{{ __('How many days each employee gets per calendar year.') }}</p>
        </div>
        <a href="{{ route('admin.leave_types.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>{{ __('Add leave type') }}
        </a>
    </div>

    <div x-data="confirmDelete">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">{{ __('Leave type') }}</th>
                            <th>{{ __('Days per year') }}</th>
                            <th>{{ __('Pay') }}</th>
                            <th>{{ __('Requests') }}</th>
                            <th class="text-end pe-3">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leaveTypes as $leaveType)
                            <tr>
                                <td class="ps-3 fw-medium">{{ $leaveType->name }}</td>
                                <td>{{ $leaveType->isUnlimited() ? __('No limit') : $leaveType->days_per_year }}</td>
                                <td>
                                    <span @class(['badge rounded-pill badge-soft', 'badge-soft-success' => $leaveType->is_paid, 'badge-soft-secondary' => ! $leaveType->is_paid])>
                                        {{ $leaveType->is_paid ? __('Paid') : __('Unpaid') }}
                                    </span>
                                </td>
                                <td>{{ number_format($leaveType->leave_requests_count) }}</td>
                                <td class="text-end pe-3 text-nowrap">
                                    <a href="{{ route('admin.leave_types.edit', $leaveType) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                                        <i class="bi bi-pencil"></i><span class="visually-hidden">{{ __('Edit') }}</span>
                                    </a>
                                    @if ($leaveType->leave_requests_count === 0)
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            title="{{ __('Delete') }}"
                                            x-on:click="open(@js($leaveType->name), @js(route('admin.leave_types.destroy', $leaveType)))"
                                        >
                                            <i class="bi bi-trash"></i><span class="visually-hidden">{{ __('Delete') }}</span>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-5">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    {{ __('No leave types yet. Add one so employees can apply for leave.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-delete-modal :title="__('Delete leave type?')" :button="__('Delete leave type')">
            {{ __('This permanently deletes') }} <strong x-text="name"></strong>. {{ __('This cannot be undone.') }}
        </x-delete-modal>
    </div>
</x-layouts.app>
