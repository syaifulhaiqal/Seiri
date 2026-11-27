@php
    $route = $type->route();
@endphp

<x-layouts.app :title="$type->pluralLabel()">
    <div class="page-header">
        <div>
            <h1>{{ $type->pluralLabel() }}</h1>
            <p class="text-secondary mb-0">
                {{ $type === \App\Enums\InvoiceType::Sales ? __('Money customers owe you.') : __('Money you owe suppliers.') }}
            </p>
        </div>
        <a href="{{ route($route . '.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>{{ __('New :type', ['type' => strtolower($type->label())]) }}
        </a>
    </div>

    <div class="row g-3 mb-4">
        @foreach ([
            ['label' => __('Outstanding'), 'value' => \App\Support\Money::format($totals['outstanding']), 'icon' => 'bi-hourglass-split', 'color' => 'primary', 'query' => ['status' => 'open']],
            ['label' => __('Overdue'), 'value' => \App\Support\Money::format($totals['overdue']), 'icon' => 'bi-exclamation-octagon', 'color' => 'danger', 'query' => ['status' => 'overdue']],
            ['label' => __('Drafts'), 'value' => number_format($totals['drafts']), 'icon' => 'bi-pencil-square', 'color' => 'secondary', 'query' => ['status' => 'draft']],
        ] as $card)
            <div class="col-sm-4">
                <a href="{{ route($route . '.index', $card['query']) }}" class="card stat-card h-100 text-decoration-none text-body">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="stat-icon badge-soft-{{ $card['color'] }}"><i class="bi {{ $card['icon'] }}"></i></span>
                        <div>
                            <div class="text-secondary small">{{ $card['label'] }}</div>
                            <div class="stat-value">{{ $card['value'] }}</div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route($route . '.index') }}" class="row g-2 align-items-center" x-data="autoSubmit">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-secondary"></i></span>
                        <input type="search" name="search" class="form-control border-start-0 ps-0" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search number, reference or :contact', ['contact' => strtolower($type->contactLabel())]) }}" aria-label="{{ __('Search') }}">
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <select name="status" class="form-select" aria-label="{{ __('Status') }}" x-on:change="submit()">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach (\App\Enums\InvoiceStatus::options() + ['overdue' => __('Overdue')] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-5 d-flex gap-2 justify-content-lg-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>{{ __('Filter') }}</button>
                    @if (array_filter($filters))
                        <a href="{{ route($route . '.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">{{ __('Number') }}</th>
                        <th>{{ $type->contactLabel() }}</th>
                        <th>{{ __('Issued') }}</th>
                        <th>{{ __('Due') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Total') }}</th>
                        <th class="text-end pe-3">{{ __('Balance due') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documents as $document)
                        <tr>
                            <td class="ps-3 text-nowrap">
                                <a href="{{ route($route . '.show', $document) }}" class="fw-medium text-decoration-none">{{ $document->number }}</a>
                                @if ($document->reference)
                                    <div class="small text-secondary">{{ $document->reference }}</div>
                                @endif
                            </td>
                            <td>{{ $document->contact->name }}</td>
                            <td class="small text-nowrap">{{ $document->issue_date->format('j M Y') }}</td>
                            <td @class(['small text-nowrap', 'text-danger fw-medium' => $document->isOverdue()])>{{ $document->due_date->format('j M Y') }}</td>
                            <td>
                                <x-enum-badge :value="$document->status" />
                                @if ($document->isOverdue())
                                    <span class="badge rounded-pill badge-soft badge-soft-danger">{{ __('Overdue') }}</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">@money($document->total)</td>
                            <td class="text-end text-nowrap pe-3">{{ $document->isOutstanding() ? \App\Support\Money::format(\App\Support\Money::fromCents($document->balanceDueCents())) : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">
                                <i class="bi bi-receipt fs-3 d-block mb-2"></i>
                                {{ array_filter($filters) ? __('Nothing matches your filters.') : __('No :type yet.', ['type' => strtolower($type->pluralLabel())]) }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($documents->hasPages())
            <div class="card-body border-top pb-0">
                {{ $documents->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
