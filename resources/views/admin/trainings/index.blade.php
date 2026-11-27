<x-layouts.app :title="__('Training')">
    <div class="page-header">
        <div>
            <h1>{{ __('Training :year', ['year' => $year]) }}</h1>
            <p class="text-secondary mb-0">
                {{ __('Completed: :hours hours, :people people, :cost spent', ['hours' => $totals['hours'], 'people' => $totals['people'], 'cost' => \App\Support\Money::format($totals['cost'])]) }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <div class="btn-group">
                <a href="{{ route('admin.trainings.index', [...$filters, 'year' => $year - 1]) }}" class="btn btn-outline-secondary" title="{{ __('Previous year') }}"><i class="bi bi-chevron-left"></i><span class="visually-hidden">{{ __('Previous year') }}</span></a>
                <a href="{{ route('admin.trainings.index', [...$filters, 'year' => $year + 1]) }}" class="btn btn-outline-secondary" title="{{ __('Next year') }}"><i class="bi bi-chevron-right"></i><span class="visually-hidden">{{ __('Next year') }}</span></a>
            </div>
            <a href="{{ route('admin.trainings.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>{{ __('Add training') }}</a>
        </div>
    </div>

    <div class="card" x-data="confirmDelete">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.trainings.index') }}" class="row g-2 align-items-center" x-data="autoSubmit">
                <input type="hidden" name="year" value="{{ $year }}">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-body"><i class="bi bi-search text-secondary"></i></span>
                        <input type="search" name="search" class="form-control border-start-0 ps-0" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search course or employee') }}" aria-label="{{ __('Search') }}">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select name="status" class="form-select" aria-label="{{ __('Status') }}" x-on:change="submit()">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach (\App\Enums\TrainingStatus::options() as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 d-flex gap-2 justify-content-md-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>{{ __('Filter') }}</button>
                    @if (array_filter(\Illuminate\Support\Arr::except($filters, 'year')))
                        <a href="{{ route('admin.trainings.index', ['year' => $year]) }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">{{ __('Course') }}</th>
                        <th>{{ __('Employee') }}</th>
                        <th>{{ __('Dates') }}</th>
                        <th class="text-end">{{ __('Hours') }}</th>
                        <th class="text-end">{{ __('Cost') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end pe-3">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trainings as $training)
                        <tr>
                            <td class="ps-3">
                                <div class="fw-medium">{{ $training->title }}</div>
                                <div class="small text-secondary">{{ $training->provider ?: '—' }}</div>
                            </td>
                            <td>
                                <a href="{{ route('admin.employees.show', $training->employee) }}">{{ $training->employee->name }}</a>
                                <div class="small text-secondary">{{ $training->employee->department?->name ?? $training->employee->employee_no }}</div>
                            </td>
                            <td class="text-nowrap">{{ \App\Support\DateRange::format($training->start_date, $training->end_date) }}</td>
                            <td class="text-end">{{ $training->hours !== null ? (float) $training->hours : '—' }}</td>
                            <td class="text-end text-nowrap">@money($training->cost)</td>
                            <td><x-enum-badge :value="$training->status" /></td>
                            <td class="text-end pe-3 text-nowrap">
                                <a href="{{ route('admin.trainings.edit', $training) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                                    <i class="bi bi-pencil"></i><span class="visually-hidden">{{ __('Edit') }}</span>
                                </a>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    title="{{ __('Delete') }}"
                                    x-on:click="open(@js($training->title), @js(route('admin.trainings.destroy', $training)))"
                                >
                                    <i class="bi bi-trash"></i><span class="visually-hidden">{{ __('Delete') }}</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">
                                <i class="bi bi-mortarboard fs-3 d-block mb-2"></i>
                                {{ __('No training recorded for :year.', ['year' => $year]) }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($trainings->hasPages())
            <div class="card-body border-top pb-0">
                {{ $trainings->links() }}
            </div>
        @endif

        <x-delete-modal :title="__('Delete training record?')" :button="__('Delete record')">
            {{ __('This permanently deletes') }} <strong x-text="name"></strong>.
        </x-delete-modal>
    </div>
</x-layouts.app>
