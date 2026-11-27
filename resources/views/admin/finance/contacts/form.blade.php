@php
    $editing = $contact->exists;
@endphp

<x-layouts.app :title="$editing ? __('Edit :name', ['name' => $contact->name]) : __('Add contact')">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.contacts.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to customers & suppliers') }}
            </a>
            <h1 class="mt-2">{{ $editing ? __('Edit contact') : __('Add contact') }}</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ $editing ? route('admin.contacts.update', $contact) : route('admin.contacts.store') }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-8">
                                <x-form.input name="name" :label="__('Name')" :value="$contact->name" required />
                            </div>
                            <div class="col-sm-4">
                                <x-form.select name="type" :label="__('Type')" :options="\App\Enums\ContactType::options()" :value="$contact->type" required />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <x-form.input name="email" type="email" :label="__('Email')" :value="$contact->email" />
                            </div>
                            <div class="col-sm-6">
                                <x-form.input name="phone_number" type="tel" :label="__('Phone number')" :value="$contact->phone_number" />
                            </div>
                        </div>
                        <x-form.input name="registration_no" :label="__('Company registration no.')" :value="$contact->registration_no" />
                        <x-form.textarea name="address" :label="__('Address')" :value="$contact->address" rows="3" />
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <x-submit-button>{{ $editing ? __('Save changes') : __('Add contact') }}</x-submit-button>
                    <a href="{{ route('admin.contacts.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
