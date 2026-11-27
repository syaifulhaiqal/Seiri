{{-- Report title, print button and either an as-of date or a from/to range. --}}
<div class="page-header">
    <div>
        <h1>{{ $heading }}</h1>
        <p class="text-secondary mb-0">{{ $subheading }}</p>
    </div>
    <button type="button" class="btn btn-outline-secondary d-print-none" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>{{ __('Print') }}
    </button>
</div>

@include('admin.finance.reports._nav')

<form method="GET" class="row g-2 align-items-end mb-4 d-print-none">
    @isset($asOf)
        <div class="col-sm-4 col-lg-3">
            <label for="as_of" class="form-label small text-secondary">{{ __('As of') }}</label>
            <input type="date" id="as_of" name="as_of" class="form-control" value="{{ $asOf->toDateString() }}">
        </div>
    @else
        @isset($accountOptions)
            <div class="col-sm-12 col-lg-4">
                <label for="account" class="form-label small text-secondary">{{ __('Account') }}</label>
                <select id="account" name="account" class="form-select">
                    @foreach ($accountOptions as $id => $name)
                        <option value="{{ $id }}" @selected($account?->id === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        @endisset
        <div class="col-6 col-sm-4 col-lg-2">
            <label for="from" class="form-label small text-secondary">{{ __('From') }}</label>
            <input type="date" id="from" name="from" class="form-control" value="{{ $from->toDateString() }}">
        </div>
        <div class="col-6 col-sm-4 col-lg-2">
            <label for="to" class="form-label small text-secondary">{{ __('To') }}</label>
            <input type="date" id="to" name="to" class="form-control" value="{{ $to->toDateString() }}">
        </div>
    @endisset
    <div class="col-auto">
        <button type="submit" class="btn btn-primary">{{ __('Show') }}</button>
    </div>
    @if ($errors->any())
        <div class="col-12 text-danger small">{{ $errors->first() }}</div>
    @endif
</form>
