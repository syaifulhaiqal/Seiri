{{-- Link to the document that posted a journal entry (invoice, payroll run, claim, payment), or "Manual entry". --}}
@php
    $source = $entry->source;
    [$sourceLabel, $sourceUrl] = match (true) {
        $source instanceof \App\Models\Invoice => [$source->type->label() . ' ' . $source->number, route($source->type->route() . '.show', $source)],
        $source instanceof \App\Models\Payment => [__('Payment for :number', ['number' => $source->invoice->number]), route($source->invoice->type->route() . '.show', $source->invoice)],
        $source instanceof \App\Models\PayrollRun => [__('Payroll :month', ['month' => $source->periodLabel()]), route('admin.payroll.show', $source)],
        $source instanceof \App\Models\ExpenseClaim => [__('Claim :number', ['number' => $source->number]), route('admin.claims.show', $source)],
        $entry->source_type !== null => [__('Deleted record'), null],
        default => [__('Manual entry'), null],
    };
@endphp

@if ($sourceUrl)
    <a href="{{ $sourceUrl }}">{{ $sourceLabel }}</a>
@else
    <span class="text-secondary">{{ $sourceLabel }}</span>
@endif
