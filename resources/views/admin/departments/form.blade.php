@php
    $editing = $department->exists;
@endphp

<x-layouts.app :title="$editing ? __('Edit :name', ['name' => $department->name]) : __('Add department')">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.departments.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to departments') }}
            </a>
            <h1 class="mt-2">{{ $editing ? __('Edit department') : __('Add department') }}</h1>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <form
                method="POST"
                action="{{ $editing ? route('admin.departments.update', $department) : route('admin.departments.store') }}"
                x-data="formSubmit"
                x-on:submit="submit()"
                novalidate
            >
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif

                <div class="card mb-4">
                    <div class="card-header">
                        <h2>{{ __('Details') }}</h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-8">
                                <x-form.input name="name" :label="__('Name')" :value="$department->name" required />
                            </div>
                            <div class="col-sm-4">
                                <x-form.input name="code" :label="__('Code')" :value="$department->code" required maxlength="10" :help="__('Short code, e.g. FIN.')" />
                            </div>
                        </div>
                        <x-form.textarea name="description" :label="__('Description')" :value="$department->description" />
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <x-submit-button>{{ $editing ? __('Save changes') : __('Add department') }}</x-submit-button>
                    <a href="{{ route('admin.departments.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
