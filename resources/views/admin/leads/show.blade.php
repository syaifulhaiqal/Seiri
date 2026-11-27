<x-layouts.app :title="$lead->title">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.leads.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to leads') }}
            </a>
            <h1 class="mt-2">{{ $lead->title }}</h1>
            <p class="text-secondary mb-0">
                {{ $lead->company ?: $lead->contact_name }} · @money($lead->value) · <x-enum-badge :value="$lead->status" />
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2" x-data="confirmDelete">
            @if ($lead->contact)
                <a href="{{ route('admin.quotations.create', ['lead' => $lead->id]) }}" class="btn btn-primary"><i class="bi bi-file-earmark-richtext me-1"></i>{{ __('New quotation') }}</a>
            @else
                <form method="POST" action="{{ route('admin.leads.convert', $lead) }}" x-data="formSubmit" x-on:submit="submit()">
                    @csrf
                    <button type="submit" class="btn btn-primary" x-bind:disabled="submitting"><i class="bi bi-person-check me-1"></i>{{ __('Make customer') }}</button>
                </form>
            @endif
            <a href="{{ route('admin.leads.edit', $lead) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i>{{ __('Edit') }}</a>
            @if ($lead->quotations->isEmpty())
                <button type="button" class="btn btn-outline-danger" x-on:click="open(@js($lead->title), @js(route('admin.leads.destroy', $lead)))">
                    <i class="bi bi-trash me-1"></i>{{ __('Delete') }}
                </button>
            @endif

            <x-delete-modal :title="__('Delete lead?')" :button="__('Delete lead')">
                {{ __('This permanently deletes') }} <strong x-text="name"></strong> {{ __('and its activity history.') }}
            </x-delete-modal>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body">
                    <dl class="row small mb-0">
                        <dt class="col-5 text-secondary fw-normal">{{ __('Person') }}</dt>
                        <dd class="col-7">{{ $lead->contact_name }}</dd>
                        <dt class="col-5 text-secondary fw-normal">{{ __('Email') }}</dt>
                        <dd class="col-7 text-break">
                            @if ($lead->email)
                                <a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a>
                            @else
                                —
                            @endif
                        </dd>
                        <dt class="col-5 text-secondary fw-normal">{{ __('Phone') }}</dt>
                        <dd class="col-7">{{ $lead->phone_number ?: '—' }}</dd>
                        <dt class="col-5 text-secondary fw-normal">{{ __('Source') }}</dt>
                        <dd class="col-7">{{ $lead->source ?: '—' }}</dd>
                        <dt class="col-5 text-secondary fw-normal">{{ __('Owner') }}</dt>
                        <dd class="col-7">{{ $lead->owner?->name ?? '—' }}</dd>
                        <dt class="col-5 text-secondary fw-normal">{{ __('Expected close') }}</dt>
                        <dd class="col-7">{{ $lead->expected_close?->translatedFormat('j M Y') ?? '—' }}</dd>
                        <dt class="col-5 text-secondary fw-normal">{{ __('Customer') }}</dt>
                        <dd class="col-7 mb-0">
                            @if ($lead->contact)
                                @can('invoices.manage')
                                    <a href="{{ route('admin.contacts.edit', $lead->contact) }}">{{ $lead->contact->name }}</a>
                                @else
                                    {{ $lead->contact->name }}
                                @endcan
                            @else
                                {{ __('Not yet') }}
                            @endif
                        </dd>
                    </dl>
                    @if ($lead->notes)
                        <hr>
                        <div class="small" style="white-space: pre-line">{{ $lead->notes }}</div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>{{ __('Quotations') }}</h2>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($lead->quotations as $quotation)
                        <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                            <span>
                                <a href="{{ route('admin.quotations.show', $quotation) }}">{{ $quotation->number }}</a>
                                <span class="small text-secondary d-block">@money($quotation->total)</span>
                            </span>
                            <x-enum-badge :value="$quotation->status" />
                        </li>
                    @empty
                        <li class="list-group-item text-secondary small">{{ $lead->contact ? __('No quotations yet.') : __('Make the lead a customer to send a quotation.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h2>{{ __('Log activity') }}</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.leads.activity', $lead) }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                        @csrf
                        <div class="row">
                            <div class="col-sm-6">
                                <x-form.select name="type" bag="activity" :label="__('Type')" :options="\App\Enums\ActivityType::options()" value="call" required />
                            </div>
                            <div class="col-sm-6">
                                <x-form.input name="happened_at" type="datetime-local" bag="activity" :label="__('When')" :value="now()->format('Y-m-d\TH:i')" required />
                            </div>
                        </div>
                        <x-form.textarea name="summary" bag="activity" :label="__('What happened')" rows="2" required />
                        <x-submit-button>{{ __('Save') }}</x-submit-button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>{{ __('History') }}</h2>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($lead->activities as $activity)
                        <li class="list-group-item d-flex gap-3">
                            <span class="stat-icon badge-soft-primary flex-shrink-0"><i class="bi {{ $activity->type->icon() }}"></i></span>
                            <div class="min-w-0">
                                <div class="small text-secondary">
                                    {{ $activity->type->label() }} · {{ $activity->happened_at->translatedFormat('j M Y, g:i A') }}
                                    @if ($activity->user)
                                        · {{ $activity->user->name }}
                                    @endif
                                </div>
                                <div style="white-space: pre-line">{{ $activity->summary }}</div>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-secondary py-4">{{ __('Nothing logged yet.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-layouts.app>
