@props(['title', 'button'])

{{-- Must be inside an x-data="confirmDelete" element. Triggers call open(name, url); the slot can show the name with x-text="name". --}}
<div class="modal fade" tabindex="-1" aria-labelledby="delete-modal-title" aria-hidden="true" x-ref="modal">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" x-bind:action="action" x-on:submit="submitting = true">
            @csrf
            @method('DELETE')

            <div class="modal-body p-4 text-center">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle badge-soft-danger mb-3" style="width: 3.5rem; height: 3.5rem">
                    <i class="bi bi-trash fs-4"></i>
                </span>
                <h2 class="h5" id="delete-modal-title">{{ $title }}</h2>
                <p class="text-secondary mb-0">{{ $slot }}</p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-danger" x-bind:disabled="submitting">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="submitting" x-cloak></span>
                    {{ $button }}
                </button>
            </div>
        </form>
    </div>
</div>
