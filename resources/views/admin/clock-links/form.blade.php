{{-- $link: a new or existing ClockLink --}}
<form
    method="POST"
    action="{{ $link->exists ? route('admin.attendance.links.update', $link) : route('admin.attendance.links.store') }}"
    x-data="formSubmit"
    x-on:submit="submit()"
    novalidate
>
    @csrf
    @if ($link->exists)
        @method('PUT')
    @endif

    <x-form.input name="name" :label="__('Name')" :value="$link->name" required maxlength="100" :placeholder="__('e.g. Work from home')" :help="__('Staff see it on the clock-in page, and it is their work location if they leave it blank.')" />
    <x-form.input name="expires_on" type="date" :label="__('Last day (optional)')" :value="$link->expires_on?->format('Y-m-d')" :help="__('Leave blank to keep it until you turn it off.')" />

    <div class="mb-3">
        <input type="hidden" name="require_location" value="0">
        <div class="form-check form-switch">
            <input
                type="checkbox"
                role="switch"
                id="require_location"
                name="require_location"
                value="1"
                @class(['form-check-input', 'is-invalid' => $errors->has('require_location')])
                @checked(old('require_location', $link->require_location))
            >
            <label class="form-check-label" for="require_location">{{ __('Require location') }}</label>
        </div>
        <div class="form-text">{{ __('On: staff must let the browser share their location to clock in.') }}</div>
    </div>

    <x-submit-button>{{ $link->exists ? __('Save changes') : __('Create link') }}</x-submit-button>
</form>
