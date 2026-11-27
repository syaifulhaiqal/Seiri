<x-layouts.app :title="__('Movements')">
    <div class="page-header">
        <div>
            <h1>{{ __('Movements') }}</h1>
            <p class="text-secondary mb-0">
                {{ $date->isToday() ? __('Today, :date', ['date' => $date->translatedFormat('l j M Y')]) : $date->translatedFormat('l, j M Y') }}
                @if ($date->isToday())
                    · {{ trans_choice(':count person out of the office now|:count people out of the office now', $outCount) }}
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.movements.index', [...$filters, 'date' => $date->copy()->subDay()->toDateString()]) }}" class="btn btn-outline-secondary" title="{{ __('Previous day') }}">
                <i class="bi bi-chevron-left"></i><span class="visually-hidden">{{ __('Previous day') }}</span>
            </a>
            @unless ($date->isToday())
                <a href="{{ route('admin.movements.index', [...$filters, 'date' => null]) }}" class="btn btn-outline-secondary">{{ __('Today') }}</a>
            @endunless
            <a href="{{ route('admin.movements.index', [...$filters, 'date' => $date->copy()->addDay()->toDateString()]) }}" class="btn btn-outline-secondary" title="{{ __('Next day') }}">
                <i class="bi bi-chevron-right"></i><span class="visually-hidden">{{ __('Next day') }}</span>
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.movements.index') }}" class="row g-2 align-items-center" x-data="autoSubmit">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-body"><i class="bi bi-search text-secondary"></i></span>
                        <input type="search" name="search" class="form-control border-start-0 ps-0" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search employee name or number') }}" aria-label="{{ __('Search') }}">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <input type="date" name="date" class="form-control" value="{{ $date->toDateString() }}" aria-label="{{ __('Date') }}" x-on:change="submit()">
                </div>
                <div class="col-6 col-md-3">
                    <select name="department" class="form-select" aria-label="{{ __('Department') }}" x-on:change="submit()">
                        <option value="">{{ __('All departments') }}</option>
                        @foreach ($departments as $id => $name)
                            <option value="{{ $id }}" @selected((string) ($filters['department'] ?? '') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 d-flex justify-content-md-end">
                    @if (array_filter($filters))
                        <a href="{{ route('admin.movements.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">{{ __('Employee') }}</th>
                        <th>{{ __('Where') }}</th>
                        <th>{{ __('Purpose') }}</th>
                        <th>{{ __('Out') }}</th>
                        <th class="pe-3">{{ __('Back') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                        <tr>
                            <td class="ps-3">
                                <a href="{{ route('admin.employees.show', $movement->employee) }}" class="d-flex align-items-center gap-2 text-decoration-none text-body">
                                    <span class="avatar">{{ str($movement->employee->name)->substr(0, 1) }}</span>
                                    <div class="min-w-0">
                                        <div class="fw-medium">{{ $movement->employee->name }}</div>
                                        <div class="small text-secondary">{{ $movement->employee->department?->name ?? $movement->employee->employee_no }}</div>
                                    </div>
                                </a>
                            </td>
                            <td class="fw-medium">{{ $movement->destination }}</td>
                            <td class="small">{{ $movement->purpose }}</td>
                            <td class="text-nowrap">{{ $movement->formatTime($movement->time_out) }}</td>
                            <td class="pe-3 text-nowrap">
                                @if ($movement->isOut())
                                    <span class="badge rounded-pill badge-soft badge-soft-warning">{{ __('Still out') }}</span>
                                @else
                                    {{ $movement->formatTime($movement->time_back) }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-5">
                                <i class="bi bi-signpost fs-3 d-block mb-2"></i>
                                {{ __('Nobody logged a movement on this day.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
