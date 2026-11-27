{{-- $balances from Employee::leaveBalances(), $year --}}
<div class="card mb-4">
    <div class="card-header">
        <h2>{{ __('Leave balance :year', ['year' => $year]) }}</h2>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @forelse ($balances as $balance)
                <div class="col-sm-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-secondary small">{{ $balance['type']->name }}</div>
                        @if ($balance['available'] === null)
                            <div class="fs-4 fw-bold">{{ __('No limit') }}</div>
                            <div class="small text-secondary">{{ trans_choice(':count day taken|:count days taken', $balance['approved']) }}</div>
                        @else
                            <div class="fs-4 fw-bold">
                                {{ $balance['available'] }}<span class="fs-6 fw-normal text-secondary"> / {{ $balance['entitled'] }}</span>
                            </div>
                            <div class="small text-secondary">
                                {{ trans_choice(':count taken|:count taken', $balance['approved']) }}
                                @if ($balance['pending'])
                                    · {{ trans_choice(':count pending|:count pending', $balance['pending']) }}
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-12 text-secondary">{{ __('No leave types are set up yet.') }}</div>
            @endforelse
        </div>
    </div>
</div>
