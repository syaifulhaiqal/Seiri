{{-- $item: a request with approvals. The approval chain and each approver's decision. --}}
@if ($item->approvals->isNotEmpty())
    <div class="card mb-4">
        <div class="card-header">
            <h2>{{ __('Approval chain') }}</h2>
        </div>
        <ul class="list-group list-group-flush">
            @foreach ($item->approvals as $approval)
                <li class="list-group-item d-flex gap-3 align-items-start">
                    <span class="badge rounded-pill badge-soft badge-soft-secondary">{{ $approval->level }}</span>
                    <div class="flex-grow-1 small">
                        <div class="fw-medium">{{ $approval->approver->name }}</div>
                        @if ($approval->note)
                            <div class="text-secondary">“{{ $approval->note }}”</div>
                        @endif
                        @if ($approval->decided_at && $approval->status !== \App\Enums\ApprovalStatus::Skipped)
                            <div class="text-secondary">{{ $approval->decided_at->translatedFormat('j M Y, g:i A') }}</div>
                        @endif
                    </div>
                    <x-enum-badge :value="$approval->status" />
                </li>
            @endforeach
        </ul>
    </div>
@endif
