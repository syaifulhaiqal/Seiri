<x-layouts.app :title="__('Journal')">
    <div class="page-header">
        <div>
            <h1>{{ __('Journal') }}</h1>
            <p class="text-secondary mb-0">{{ __('Every posting in the ledger. Entries are never edited; mistakes are reversed.') }}</p>
        </div>
        <a href="{{ route('admin.journal_entries.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>{{ __('New journal entry') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.journal_entries.index') }}" class="row g-2 align-items-center" x-data="autoSubmit">
                <div class="col-md-6 col-lg-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-secondary"></i></span>
                        <input type="search" name="search" class="form-control border-start-0 ps-0" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Number, description or ref.') }}" aria-label="{{ __('Search') }}">
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <select name="account" class="form-select" aria-label="{{ __('Account') }}" x-on:change="submit()">
                        <option value="">{{ __('All accounts') }}</option>
                        @foreach ($accounts as $id => $name)
                            <option value="{{ $id }}" @selected((string) ($filters['account'] ?? '') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <input type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}" aria-label="{{ __('From') }}" x-on:change="submit()">
                </div>
                <div class="col-6 col-lg-2">
                    <input type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}" aria-label="{{ __('To') }}" x-on:change="submit()">
                </div>
                <div class="col-12 col-lg-2 d-flex gap-2 justify-content-lg-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>{{ __('Filter') }}</button>
                    @if (array_filter($filters))
                        <a href="{{ route('admin.journal_entries.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    @endif
                </div>
            </form>
            @if ($errors->any())
                <div class="text-danger small mt-2">{{ $errors->first() }}</div>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">{{ __('Number') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Reference') }}</th>
                        <th class="text-end pe-3">{{ __('Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td class="ps-3 text-nowrap">
                                <a href="{{ route('admin.journal_entries.show', $entry) }}" class="fw-medium text-decoration-none">{{ $entry->number }}</a>
                                @if ($entry->reverses_id)
                                    <span class="badge rounded-pill badge-soft badge-soft-warning ms-1">{{ __('Reversal') }}</span>
                                @elseif ($entry->reversal)
                                    <span class="badge rounded-pill badge-soft badge-soft-secondary ms-1">{{ __('Reversed') }}</span>
                                @endif
                            </td>
                            <td class="text-nowrap small">{{ $entry->date->format('j M Y') }}</td>
                            <td>{{ $entry->description }}</td>
                            <td class="small text-secondary">{{ $entry->reference ?: '—' }}</td>
                            <td class="text-end pe-3 text-nowrap">@money($entry->total ?? 0)</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-5">
                                <i class="bi bi-journal fs-3 d-block mb-2"></i>
                                {{ array_filter($filters) ? __('No entries match your filters.') : __('Nothing has been posted yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($entries->hasPages())
            <div class="card-body border-top pb-0">
                {{ $entries->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
