@php
    $editing = $account->exists;
@endphp

<x-layouts.app :title="$editing ? __('Edit account') : __('Add account')">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.accounts.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to chart of accounts') }}
            </a>
            <h1 class="mt-2">{{ $editing ? __('Edit account') : __('Add account') }}</h1>
            @if ($editing)
                <p class="text-secondary mb-0">{{ $account->displayName() }}</p>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <form method="POST" action="{{ $editing ? route('admin.accounts.update', $account) : route('admin.accounts.store') }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif

                <div class="card mb-4">
                    <div class="card-body">
                        @if ($account->isSystem())
                            <div class="alert alert-info small d-flex gap-2">
                                <i class="bi bi-info-circle"></i>
                                <div>{{ __('The app posts to this account automatically, so its type cannot change and it stays on.') }}</div>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-sm-4">
                                <x-form.input name="code" :label="__('Code')" :value="$account->code" required placeholder="6100" />
                            </div>
                            <div class="col-sm-8">
                                <x-form.input name="name" :label="__('Name')" :value="$account->name" required />
                            </div>
                        </div>
                        <x-form.select name="type" :label="__('Type')" :options="\App\Enums\AccountType::options()" :value="$account->type" required :disabled="$account->isSystem()" />
                        @if ($account->isSystem())
                            <input type="hidden" name="type" value="{{ $account->type->value }}">
                        @endif
                        <x-form.input name="description" :label="__('Description')" :value="$account->description" />

                        <input type="hidden" name="is_active" value="{{ $account->isSystem() ? 1 : 0 }}">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $account->is_active)) @disabled($account->isSystem())>
                            <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                            <div class="form-text">{{ __('Inactive accounts keep their history but cannot be chosen for new entries.') }}</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <x-submit-button>{{ $editing ? __('Save changes') : __('Add account') }}</x-submit-button>
                    <a href="{{ route('admin.accounts.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
