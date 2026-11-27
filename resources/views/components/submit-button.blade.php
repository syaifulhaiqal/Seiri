@props(['variant' => 'primary'])

{{-- Use inside a form with x-data="formSubmit" x-on:submit="submit()". --}}
<button type="submit" {{ $attributes->class(['btn', 'btn-' . $variant]) }} x-bind:disabled="submitting">
    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="submitting" x-cloak></span>
    {{ $slot }}
</button>
