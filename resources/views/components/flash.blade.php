@if (session('status'))
    <div class="alert alert-success alert-dismissible fade show d-flex gap-2" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        <div>{{ session('status') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
    </div>
@endif

@error('delete')
    <div class="alert alert-danger alert-dismissible fade show d-flex gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>{{ $message }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
    </div>
@enderror
