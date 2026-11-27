<x-layouts.app :title="__('My expense claims')">
    <div class="page-header">
        <div>
            <h1>{{ __('My expense claims') }}</h1>
            <p class="text-secondary mb-0">{{ __('Claim back what you spent for work. Attach the receipt.') }}</p>
        </div>
    </div>

    @if (! $employee)
        @include('partials.no-employee-record')
    @else
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h2>{{ __('New claim') }}</h2>
                    </div>
                    <div class="card-body">
                        @if ($employee->isActive())
                            <form method="POST" action="{{ route('claims.store') }}" enctype="multipart/form-data" x-data="formSubmit" x-on:submit="submit()" novalidate>
                                @csrf

                                <x-form.select name="account_id" bag="claim" :label="__('Category')" :options="$categories" :placeholder="__('Choose a category')" required />
                                <div class="row">
                                    <div class="col-sm-6">
                                        <x-form.input name="date" type="date" bag="claim" :label="__('Date spent')" :value="today()->toDateString()" required />
                                    </div>
                                    <div class="col-sm-6">
                                        <x-form.input name="amount" type="number" step="0.01" min="0.01" bag="claim" :label="__('Amount (:currency)', ['currency' => config('finance.currency')])" required />
                                    </div>
                                </div>
                                <x-form.input name="description" bag="claim" :label="__('What was it for?')" required :placeholder="__('e.g. Grab to client meeting')" />
                                <x-form.input name="receipt" type="file" bag="claim" :label="__('Receipt')" accept=".jpg,.jpeg,.png,.pdf" :help="__('JPG, PNG or PDF, up to 5 MB.')" />

                                <x-submit-button>{{ __('Send claim') }}</x-submit-button>
                            </form>
                        @else
                            <p class="text-secondary mb-0">{{ __('Your employment has ended, so you can no longer send claims.') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h2>{{ __('My claims') }}</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3">{{ __('Claim') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th class="text-end">{{ __('Amount') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="text-end pe-3"><span class="visually-hidden">{{ __('Actions') }}</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($claims as $claim)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-medium">{{ $claim->description }}</div>
                                            <div class="small text-secondary">
                                                {{ $claim->number }} · {{ $claim->account->name }}
                                                @if ($claim->receipt_path)
                                                    · <a href="{{ route('claims.receipt', $claim) }}" target="_blank" rel="noopener">{{ __('Receipt') }}</a>
                                                @endif
                                            </div>
                                            @if ($claim->review_note)
                                                <div class="small text-secondary"><i class="bi bi-chat-left-text me-1"></i>{{ $claim->review_note }}</div>
                                            @endif
                                        </td>
                                        <td class="small text-nowrap">{{ $claim->date->translatedFormat('j M Y') }}</td>
                                        <td class="text-end text-nowrap">@money($claim->amount)</td>
                                        <td>
                                            <x-enum-badge :value="$claim->status" />
                                            @include('partials.approval-waiting', ['item' => $claim])
                                        </td>
                                        <td class="text-end pe-3 text-nowrap">
                                            @if ($claim->isPending())
                                                <form method="POST" action="{{ route('claims.cancel', $claim) }}" x-data="formSubmit" x-on:submit="submit()">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary" x-bind:disabled="submitting">{{ __('Cancel') }}</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-secondary py-5">
                                            <i class="bi bi-receipt fs-3 d-block mb-2"></i>
                                            {{ __('You have not sent any claims yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($claims->hasPages())
                        <div class="card-body border-top pb-0">
                            {{ $claims->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</x-layouts.app>
