<x-layouts.app :title="__('Approvals')">
    <div class="page-header">
        <div>
            <h1>{{ __('Approvals') }}</h1>
            <p class="text-secondary mb-0">{{ __('Leave, claims and overtime from the people you approve for. After you approve, the next approver (or HR) is asked.') }}</p>
        </div>
    </div>

    <h2 class="h6 text-secondary text-uppercase mb-3">{{ __('Waiting for you') }}</h2>

    @forelse ($waiting as $approval)
        @php
            $item = $approval->approvable;
        @endphp
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3">
                    <div class="d-flex gap-3">
                        <span class="avatar">{{ str($item->employee->name)->substr(0, 1) }}</span>
                        <div>
                            <div class="fw-semibold">{{ $item->employee->name }}</div>
                            <div class="small text-secondary">{{ $item->employee->job_title }}{{ $item->employee->department ? ' · ' . $item->employee->department->name : '' }}</div>
                            <div class="mt-2">
                                <span class="badge rounded-pill badge-soft badge-soft-primary me-1">{{ $item->approvalType()->label() }}</span>
                                {{ $item->approvalSummary() }}
                            </div>
                            @if ($item->reason ?? null)
                                <div class="small text-secondary mt-1">{{ __('Reason') }}: {{ $item->reason }}</div>
                            @endif
                            @if ($item instanceof \App\Models\ExpenseClaim && $item->receipt_path)
                                <a href="{{ route('approvals.receipt', $approval) }}" target="_blank" class="small d-inline-block mt-1"><i class="bi bi-paperclip me-1"></i>{{ __('Receipt') }}</a>
                            @endif
                            <div class="small text-secondary mt-1">
                                {{ trans_choice('Step :level of :count|Step :level of :count', $item->approvals->count(), ['level' => $approval->level, 'count' => $item->approvals->count()]) }}
                                · {{ __('Requested :date', ['date' => $item->created_at->translatedFormat('j M Y')]) }}
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('approvals.decide', $approval) }}" class="approval-decision" x-data="formSubmit" x-on:submit="submit()" novalidate>
                        @csrf
                        @method('PUT')
                        <textarea name="note" rows="2" @class(['form-control form-control-sm mb-2', 'is-invalid' => $errors->has('note') && old('approval') == $approval->id]) placeholder="{{ __('Note (required when rejecting)') }}" aria-label="{{ __('Note') }}" maxlength="500"></textarea>
                        <input type="hidden" name="approval" value="{{ $approval->id }}">
                        @if ($errors->has('note') && old('approval') == $approval->id)
                            <div class="invalid-feedback d-block mb-2">{{ $errors->first('note') }}</div>
                        @endif
                        <div class="d-flex gap-2">
                            <button type="submit" name="decision" value="approve" class="btn btn-success btn-sm" x-bind:disabled="submitting"><i class="bi bi-check-lg me-1"></i>{{ __('Approve') }}</button>
                            <button type="submit" name="decision" value="reject" class="btn btn-outline-danger btn-sm" x-bind:disabled="submitting"><i class="bi bi-x-lg me-1"></i>{{ __('Reject') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="card mb-3">
            <div class="card-body text-center text-secondary py-5">
                <i class="bi bi-check2-all fs-2 d-block mb-2"></i>
                {{ __('Nothing is waiting for you.') }}
            </div>
        </div>
    @endforelse

    @if ($decided->isNotEmpty())
        <h2 class="h6 text-secondary text-uppercase mt-5 mb-3">{{ __('Recently decided') }}</h2>
        <div class="card">
            <ul class="list-group list-group-flush">
                @foreach ($decided as $approval)
                    <li class="list-group-item d-flex flex-wrap justify-content-between gap-2 small">
                        <span>
                            <span class="fw-medium">{{ $approval->approvable->employee->name }}</span>
                            · {{ $approval->approvable->approvalSummary() }}
                        </span>
                        <span class="text-nowrap">
                            <x-enum-badge :value="$approval->status" />
                            <span class="text-secondary ms-1">{{ $approval->decided_at->translatedFormat('j M Y') }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-layouts.app>
