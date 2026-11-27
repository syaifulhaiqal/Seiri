<x-layouts.app :title="__('Leads')">
    <div class="page-header">
        <div>
            <h1>{{ __('Leads') }}</h1>
            <p class="text-secondary mb-0">{{ __('Track every opportunity from first contact to won or lost.') }}</p>
        </div>
        <a href="{{ route('admin.leads.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>{{ __('Add lead') }}</a>
    </div>

    <div class="row g-3 mb-4">
        @foreach (\App\Enums\LeadStatus::cases() as $status)
            @php
                $row = $pipeline->get($status->value);
            @endphp
            <div class="col-6 col-md-4 col-xl">
                <a href="{{ route('admin.leads.index', [...$filters, 'status' => $status->value]) }}" class="card stat-card h-100 text-decoration-none text-body">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <x-enum-badge :value="$status" />
                            <span class="fw-semibold">{{ number_format($row?->count ?? 0) }}</span>
                        </div>
                        <div class="small text-secondary mt-2">@money($row?->value ?? 0)</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.leads.index') }}" class="row g-2 align-items-center" x-data="autoSubmit">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-body"><i class="bi bi-search text-secondary"></i></span>
                        <input type="search" name="search" class="form-control border-start-0 ps-0" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search lead, person or company') }}" aria-label="{{ __('Search') }}">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select name="status" class="form-select" aria-label="{{ __('Status') }}" x-on:change="submit()">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach (\App\Enums\LeadStatus::options() as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="owner" class="form-select" aria-label="{{ __('Owner') }}" x-on:change="submit()">
                        <option value="">{{ __('All owners') }}</option>
                        @foreach ($owners as $id => $name)
                            <option value="{{ $id }}" @selected((string) ($filters['owner'] ?? '') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2 justify-content-md-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>{{ __('Filter') }}</button>
                    @if (array_filter($filters))
                        <a href="{{ route('admin.leads.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">{{ __('Lead') }}</th>
                        <th>{{ __('Contact') }}</th>
                        <th class="text-end">{{ __('Value') }}</th>
                        <th>{{ __('Owner') }}</th>
                        <th>{{ __('Last activity') }}</th>
                        <th class="pe-3">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leads as $lead)
                        <tr>
                            <td class="ps-3">
                                <a href="{{ route('admin.leads.show', $lead) }}" class="fw-medium text-decoration-none">{{ $lead->title }}</a>
                                @if ($lead->expected_close)
                                    <div class="small text-secondary">{{ __('Close by :date', ['date' => $lead->expected_close->translatedFormat('j M Y')]) }}</div>
                                @endif
                            </td>
                            <td>
                                {{ $lead->contact_name }}
                                <div class="small text-secondary">{{ $lead->company ?: '—' }}</div>
                            </td>
                            <td class="text-end text-nowrap">@money($lead->value)</td>
                            <td>{{ $lead->owner?->name ?? '—' }}</td>
                            <td class="small text-secondary text-nowrap">
                                {{ $lead->activities_max_happened_at ? \Illuminate\Support\Carbon::parse($lead->activities_max_happened_at)->diffForHumans() : __('None yet') }}
                            </td>
                            <td class="pe-3"><x-enum-badge :value="$lead->status" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-5">
                                <i class="bi bi-funnel fs-3 d-block mb-2"></i>
                                {{ array_filter($filters) ? __('No leads match your filters.') : __('No leads yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($leads->hasPages())
            <div class="card-body border-top pb-0">
                {{ $leads->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
