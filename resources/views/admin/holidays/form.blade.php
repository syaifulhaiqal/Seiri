{{-- $holiday: a new or existing Holiday --}}
<form
    method="POST"
    action="{{ $holiday->exists ? route('admin.holidays.update', $holiday) : route('admin.holidays.store') }}"
    x-data="formSubmit"
    x-on:submit="submit()"
    novalidate
>
    @csrf
    @if ($holiday->exists)
        @method('PUT')
    @endif

    <x-form.input name="date" type="date" :label="__('Date')" :value="$holiday->date?->format('Y-m-d')" required />
    <x-form.input name="name" :label="__('Name')" :value="$holiday->name" required :placeholder="__('e.g. Hari Raya Aidilfitri')" />

    <x-submit-button>{{ $holiday->exists ? __('Save changes') : __('Add holiday') }}</x-submit-button>
</form>
