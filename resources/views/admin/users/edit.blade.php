<x-layouts.app :title="__('Edit :name', ['name' => $user->name])">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.users.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to users') }}
            </a>
            <h1 class="mt-2">{{ __('Edit user') }}</h1>
            <p class="text-secondary mb-0">
                {{ __('Joined :date', ['date' => $user->created_at->format('j M Y')]) }}
                @if ($user->email_verified_at)
                    · {{ __('Activated :date', ['date' => $user->email_verified_at->format('j M Y')]) }}
                @endif
            </p>
        </div>
    </div>

    @php
        $isSelf = $user->is(auth()->user());
    @endphp

    <div class="row g-4" x-data="deleteUser">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('admin.users.update', $user) }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                @csrf
                @method('PUT')

                <div class="card mb-4">
                    <div class="card-header">
                        <h2>{{ __('Details') }}</h2>
                    </div>
                    <div class="card-body">
                        <x-form.input name="name" :label="__('Full name')" :value="$user->name" required />
                        <x-form.input name="email" type="email" :label="__('Email')" :value="$user->email" required />
                        <x-form.input name="phone_number" type="tel" :label="__('Phone number')" :value="$user->phone_number" />

                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <label for="role" class="form-label fw-medium"><span class="required">{{ __('Role') }}</span></label>
                                <select id="role" name="role" @class(['form-select', 'is-invalid' => $errors->has('role')])>
                                    @foreach (\App\Enums\UserRole::options() as $value => $label)
                                        <option value="{{ $value }}" @selected(old('role', $user->role->value) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-sm-6 mb-3">
                                <span class="form-label fw-medium d-block">{{ __('Account status') }}</span>
                                <input type="hidden" name="is_active" value="0">
                                <div class="form-check form-switch mt-2">
                                    <input
                                        type="checkbox"
                                        role="switch"
                                        id="is_active"
                                        name="is_active"
                                        value="1"
                                        @class(['form-check-input', 'is-invalid' => $errors->has('is_active')])
                                        @checked(old('is_active', $user->hasVerifiedEmail()))
                                    >
                                    <label class="form-check-label" for="is_active">{{ __('Active (email confirmed)') }}</label>
                                    @error('is_active')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text">{{ __('Turn off to block this user from logging in.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h2>{{ __('Reset password') }}</h2>
                    </div>
                    <div class="card-body">
                        <p class="text-secondary small">{{ __('Leave blank to keep the current password.') }}</p>
                        <div class="row">
                            <div class="col-sm-6">
                                <x-form.input name="password" type="password" :label="__('New password')" autocomplete="new-password" />
                            </div>
                            <div class="col-sm-6">
                                <x-form.input name="password_confirmation" type="password" :label="__('Confirm new password')" autocomplete="new-password" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <x-submit-button>{{ __('Save changes') }}</x-submit-button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card border-danger-subtle">
                <div class="card-header">
                    <h2 class="text-danger">{{ __('Delete account') }}</h2>
                </div>
                <div class="card-body">
                    @if ($isSelf)
                        <p class="text-secondary small mb-0">{{ __('You cannot delete your own account while signed in.') }}</p>
                    @else
                        <p class="text-secondary small">{{ __('Permanently remove this user and all of their data.') }}</p>
                        <button type="button" class="btn btn-outline-danger" x-on:click="open(@js($user->name), @js(route('admin.users.destroy', $user)))">
                            <i class="bi bi-trash me-1"></i>{{ __('Delete user') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

        @include('admin.users.partials.delete-modal')
    </div>
</x-layouts.app>
