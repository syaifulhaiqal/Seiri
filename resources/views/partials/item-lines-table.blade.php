{{-- Read-only item lines of a document with HasLineItems. Expects $document and $totals (label => amount; the last row is bold). --}}
<div class="table-responsive">
    <table class="table mb-0">
        <thead>
            <tr>
                <th class="ps-3">{{ __('Description') }}</th>
                <th class="text-end">{{ __('Qty') }}</th>
                <th class="text-end">{{ __('Unit price') }}</th>
                <th class="text-end pe-3">{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($document->lines as $line)
                <tr>
                    <td class="ps-3">
                        {{ $line->description }}
                        @if ($line->product)
                            <div class="small text-secondary">{{ $line->product->sku }}</div>
                        @endif
                    </td>
                    <td class="text-end">{{ (float) $line->quantity }}{{ $line->product ? ' ' . $line->product->unit : '' }}</td>
                    <td class="text-end text-nowrap">{{ \App\Support\Money::format($line->unit_price, false) }}</td>
                    <td class="text-end text-nowrap pe-3">{{ \App\Support\Money::format($line->amount, false) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            @foreach ($totals as $label => $amount)
                <tr @class(['fw-semibold' => $loop->last])>
                    <td colspan="3" class="text-end ps-3">{{ $label }}</td>
                    <td class="text-end text-nowrap pe-3">@money($amount)</td>
                </tr>
            @endforeach
        </tfoot>
    </table>
</div>
