@php
    $employee = $claim->employee;
@endphp

<x-layouts.app :title="$claim->number">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.claims.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to expense claims') }}
            </a>
            <h1 class="mt-2">{{ __(':number from :name', ['number' => $claim->number, 'name' => $employee->name]) }}</h1>
            <p class="text-secondary mb-0">
                {{ __('Sent :date', ['date' => $claim->created_at->format('j M Y, g:i A')]) }} · <x-enum-badge :value="$claim->status" />
            </p>
        </div>
    </div>

    @if ($errors->has('review') || $errors->payment->any())
        <div class="alert alert-danger d-flex gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>{{ $errors->first('review') ?: $errors->payment->first() }}</div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Employee') }}</dt>
                        <dd class="col-sm-8">
                            <a href="{{ route('admin.employees.show', $employee) }}">{{ $employee->name }}</a>
                            <span class="text-secondary small">· {{ $employee->employee_no }}{{ $employee->department ? ' · ' . $employee->department->name : '' }}</span>
                        </dd>

                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Amount') }}</dt>
                        <dd class="col-sm-8 fw-semibold">@money($claim->amount)</dd>

                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Spent on') }}</dt>
                        <dd class="col-sm-8">{{ $claim->date->format('j M Y') }}</dd>

                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Category') }}</dt>
                        <dd class="col-sm-8">{{ $claim->account->displayName() }}</dd>

                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Description') }}</dt>
                        <dd class="col-sm-8">{{ $claim->description }}</dd>

                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Receipt') }}</dt>
                        <dd class="col-sm-8">
                            @if ($claim->receipt_path)
                                <a href="{{ route('admin.claims.receipt', $claim) }}" target="_blank" rel="noopener"><i class="bi bi-paperclip me-1"></i>{{ $claim->receipt_name }}</a>
                            @else
                                <span class="text-secondary">{{ __('None attached') }}</span>
                            @endif
                        </dd>

                        @if ($claim->reviewed_at)
                            <dt class="col-sm-4 text-secondary fw-normal">{{ __('Reviewed') }}</dt>
                            <dd class="col-sm-8">{{ __(':name on :date', ['name' => $claim->reviewer?->name ?? __('Unknown'), 'date' => $claim->reviewed_at->format('j M Y')]) }}</dd>

                            <dt class="col-sm-4 text-secondary fw-normal">{{ __('Note') }}</dt>
                            <dd class="col-sm-8">{{ $claim->review_note ?: '—' }}</dd>
                        @endif

                        @if ($claim->journal_entry_id)
                            <dt class="col-sm-4 text-secondary fw-normal">{{ __('Ledger') }}</dt>
                            <dd class="col-sm-8 mb-0">
                                <a href="{{ route('admin.journal_entries.show', $claim->journal_entry_id) }}">{{ __('Expense posting') }}</a>
                                @if ($claim->reimbursement_entry_id)
                                    · <a href="{{ route('admin.journal_entries.show', $claim->reimbursement_entry_id) }}">{{ __('Paid back :date', ['date' => $claim->reimbursed_at?->format('j M Y')]) }}</a>
                                @endif
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            @if ($claim->isPending())
                <div class="card mb-4">
                    <div class="card-header">
                        <h2>{{ __('Decision') }}</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.claims.review', $claim) }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                            @csrf
                            @method('PUT')

                            <x-form.textarea name="review_note" :label="__('Note to the employee')" rows="2" :help="__('Optional when approving. Required when rejecting.')" />
                            <p class="small text-secondary">{{ __('Approving posts the expense and owes the amount to the employee.') }}</p>

                            <div class="d-flex gap-2">
                                <button type="submit" name="decision" value="approve" class="btn btn-success" x-bind:disabled="submitting">
                                    <i class="bi bi-check-lg me-1"></i>{{ __('Approve') }}
                                </button>
                                <button type="submit" name="decision" value="reject" class="btn btn-outline-danger" x-bind:disabled="submitting">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('Reject') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @elseif ($claim->status === \App\Enums\ClaimStatus::Approved)
                <div class="card mb-4">
                    <div class="card-header">
                        <h2>{{ __('Pay back') }}</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.claims.reimburse', $claim) }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                            @csrf
                            <x-form.select name="account_id" bag="payment" :label="__('Paid from')" :options="$bankAccounts" :value="$bankAccounts->keys()->first()" required />
                            <x-form.input name="date" type="date" bag="payment" :label="__('Date paid')" :value="today()->toDateString()" required />
                            @if ($employee->bank_account_no)
                                <p class="small text-secondary">{{ __('Pay to :bank :account.', ['bank' => $employee->bank_name, 'account' => $employee->bank_account_no]) }}</p>
                            @endif
                            <x-submit-button>{{ __('Mark :amount as paid', ['amount' => \App\Support\Money::format($claim->amount)]) }}</x-submit-button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
