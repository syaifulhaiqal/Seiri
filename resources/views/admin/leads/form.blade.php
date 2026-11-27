@php
    $editing = $lead->exists;
    $backUrl = $editing ? route('admin.leads.show', $lead) : route('admin.leads.index');
@endphp

<x-layouts.app :title="$editing ? __('Edit :name', ['name' => $lead->title]) : __('Add lead')">
    <div class="page-header">
        <div>
            <a href="{{ $backUrl }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ $editing ? __('Back to :name', ['name' => $lead->title]) : __('Back to leads') }}
            </a>
            <h1 class="mt-2">{{ $editing ? __('Edit lead') : __('Add lead') }}</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ $editing ? route('admin.leads.update', $lead) : route('admin.leads.store') }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif

                <div class="card mb-4">
                    <div class="card-header">
                        <h2>{{ __('Opportunity') }}</h2>
                    </div>
                    <div class="card-body">
                        <x-form.input name="title" :label="__('What are they interested in?')" :value="$lead->title" required :placeholder="__('e.g. Payroll outsourcing for 40 staff')" />
                        <div class="row">
                            <div class="col-sm-4">
                                <x-form.select name="status" :label="__('Status')" :options="\App\Enums\LeadStatus::options()" :value="$lead->status" required />
                            </div>
                            <div class="col-sm-4">
                                <x-form.input name="value" type="number" step="0.01" min="0" :label="__('Estimated value (:currency)', ['currency' => config('finance.currency')])" :value="$lead->value" />
                            </div>
                            <div class="col-sm-4">
                                <x-form.input name="expected_close" type="date" :label="__('Expected close')" :value="$lead->expected_close?->toDateString()" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <x-form.select name="owner_id" :label="__('Owner')" :options="$owners" :value="$lead->owner_id" :placeholder="__('Nobody yet')" />
                            </div>
                            <div class="col-sm-6">
                                <x-form.input name="source" :label="__('Source')" :value="$lead->source" :placeholder="__('e.g. Referral, website, exhibition')" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h2>{{ __('Contact') }}</h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <x-form.input name="contact_name" :label="__('Person')" :value="$lead->contact_name" required />
                            </div>
                            <div class="col-sm-6">
                                <x-form.input name="company" :label="__('Company')" :value="$lead->company" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <x-form.input name="email" type="email" :label="__('Email')" :value="$lead->email" />
                            </div>
                            <div class="col-sm-6">
                                <x-form.input name="phone_number" :label="__('Phone')" :value="$lead->phone_number" />
                            </div>
                        </div>
                        <x-form.textarea name="notes" :label="__('Notes')" :value="$lead->notes" rows="3" />
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <x-submit-button>{{ $editing ? __('Save changes') : __('Add lead') }}</x-submit-button>
                    <a href="{{ $backUrl }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
