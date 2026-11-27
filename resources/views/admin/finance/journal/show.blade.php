<x-layouts.app :title="$entry->number">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.journal_entries.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to journal') }}
            </a>
            <h1 class="mt-2">{{ $entry->number }}</h1>
            <p class="text-secondary mb-0">{{ $entry->description }}</p>
        </div>
        @if ($entry->source_type === null && $entry->reverses_id === null && ! $entry->reversal)
            <form method="POST" action="{{ route('admin.journal_entries.reverse', $entry) }}" x-data="formSubmit" x-on:submit="if (! confirm(@js(__('Post a reversing entry dated today?')))) { $event.preventDefault() } else { submit() }">
                @csrf
                <button type="submit" class="btn btn-outline-danger" x-bind:disabled="submitting">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>{{ __('Reverse entry') }}
                </button>
            </form>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-secondary fw-normal">{{ __('Date') }}</dt>
                        <dd class="col-7">{{ $entry->date->format('j M Y') }}</dd>

                        <dt class="col-5 text-secondary fw-normal">{{ __('Source') }}</dt>
                        <dd class="col-7">@include('partials.journal-source', ['entry' => $entry])</dd>

                        <dt class="col-5 text-secondary fw-normal">{{ __('Reference') }}</dt>
                        <dd class="col-7">{{ $entry->reference ?: '—' }}</dd>

                        <dt class="col-5 text-secondary fw-normal">{{ __('Posted by') }}</dt>
                        <dd class="col-7">{{ $entry->creator?->name ?? __('System') }}</dd>

                        <dt class="col-5 text-secondary fw-normal">{{ __('Posted at') }}</dt>
                        <dd @class(['col-7', 'mb-0' => ! $entry->reverses && ! $entry->reversal])>{{ $entry->created_at->format('j M Y, g:i A') }}</dd>

                        @if ($entry->reverses)
                            <dt class="col-5 text-secondary fw-normal">{{ __('Reverses') }}</dt>
                            <dd class="col-7 mb-0"><a href="{{ route('admin.journal_entries.show', $entry->reverses) }}">{{ $entry->reverses->number }}</a></dd>
                        @endif

                        @if ($entry->reversal)
                            <dt class="col-5 text-secondary fw-normal">{{ __('Reversed by') }}</dt>
                            <dd class="col-7 mb-0"><a href="{{ route('admin.journal_entries.show', $entry->reversal) }}">{{ $entry->reversal->number }}</a></dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">{{ __('Account') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th class="text-end">{{ __('Debit') }}</th>
                                <th class="text-end pe-3">{{ __('Credit') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entry->lines as $line)
                                <tr>
                                    <td class="ps-3">
                                        <a href="{{ route('admin.reports.general_ledger', ['account' => $line->account_id, 'from' => $entry->date->copy()->startOfMonth()->toDateString(), 'to' => $entry->date->copy()->endOfMonth()->toDateString()]) }}" class="text-decoration-none">
                                            {{ $line->account->displayName() }}
                                        </a>
                                    </td>
                                    <td class="small text-secondary">{{ $line->description }}</td>
                                    <td class="text-end text-nowrap">{{ (float) $line->debit ? \App\Support\Money::format($line->debit, false) : '' }}</td>
                                    <td class="text-end text-nowrap pe-3">{{ (float) $line->credit ? \App\Support\Money::format($line->credit, false) : '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-semibold">
                                <td class="ps-3" colspan="2">{{ __('Total') }}</td>
                                <td class="text-end text-nowrap">@money(\App\Support\Money::fromCents($debit))</td>
                                <td class="text-end text-nowrap pe-3">@money(\App\Support\Money::fromCents($credit))</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
