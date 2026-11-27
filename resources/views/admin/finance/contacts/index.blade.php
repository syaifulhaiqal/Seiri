<x-layouts.app :title="__('Customers & suppliers')">
    <div class="page-header">
        <div>
            <h1>{{ __('Customers & suppliers') }}</h1>
            <p class="text-secondary mb-0">{{ trans_choice(':count contact|:count contacts', $contacts->total()) }}</p>
        </div>
        <a href="{{ route('admin.contacts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>{{ __('Add contact') }}
        </a>
    </div>

    <div x-data="confirmDelete">
        <div class="card">
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('admin.contacts.index') }}" class="row g-2 align-items-center" x-data="autoSubmit">
                    <div class="col-md-6 col-lg-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-secondary"></i></span>
                            <input type="search" name="search" class="form-control border-start-0 ps-0" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search name, email or registration no.') }}" aria-label="{{ __('Search') }}">
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <select name="type" class="form-select" aria-label="{{ __('Type') }}" x-on:change="submit()">
                            <option value="">{{ __('All types') }}</option>
                            @foreach (\App\Enums\ContactType::options() as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-5 d-flex gap-2 justify-content-lg-end">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>{{ __('Filter') }}</button>
                        @if (array_filter($filters))
                            <a href="{{ route('admin.contacts.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">{{ __('Name') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Contact') }}</th>
                            <th class="text-end">{{ __('Documents') }}</th>
                            <th class="text-end">{{ __('Outstanding') }}</th>
                            <th class="text-end pe-3">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contacts as $contact)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-medium">{{ $contact->name }}</div>
                                    @if ($contact->registration_no)
                                        <div class="small text-secondary">{{ $contact->registration_no }}</div>
                                    @endif
                                </td>
                                <td><x-enum-badge :value="$contact->type" /></td>
                                <td class="small">
                                    <div class="text-break">{{ $contact->email ?: '—' }}</div>
                                    <div class="text-secondary">{{ $contact->phone_number }}</div>
                                </td>
                                <td class="text-end">{{ $contact->invoices_count }}</td>
                                <td class="text-end text-nowrap">@money((float) $contact->outstanding - (float) $contact->outstanding_paid)</td>
                                <td class="text-end pe-3 text-nowrap">
                                    <a href="{{ route('admin.contacts.edit', $contact) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                                        <i class="bi bi-pencil"></i><span class="visually-hidden">{{ __('Edit') }}</span>
                                    </a>
                                    @if ($contact->invoices_count === 0)
                                        <button type="button" class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}" x-on:click="open(@js($contact->name), @js(route('admin.contacts.destroy', $contact)))">
                                            <i class="bi bi-trash"></i><span class="visually-hidden">{{ __('Delete') }}</span>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-5">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    {{ array_filter($filters) ? __('No contacts match your filters.') : __('No customers or suppliers yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($contacts->hasPages())
                <div class="card-body border-top pb-0">
                    {{ $contacts->links() }}
                </div>
            @endif
        </div>

        <x-delete-modal :title="__('Delete contact?')" :button="__('Delete contact')">
            {{ __('This permanently deletes') }} <strong x-text="name"></strong>.
        </x-delete-modal>
    </div>
</x-layouts.app>
