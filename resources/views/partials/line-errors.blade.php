{{-- Errors for a repeating lines field, e.g. lines.0.account_id, shown together above the table. --}}
@php
    $lineErrors = collect($errors->get('lines'))->merge(collect($errors->get('lines.*'))->flatten())->unique();
@endphp

@if ($lineErrors->isNotEmpty())
    <div class="alert alert-danger d-flex gap-2 mb-0 rounded-0 border-0 border-bottom" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <ul class="mb-0 ps-3">
            @foreach ($lineErrors as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
