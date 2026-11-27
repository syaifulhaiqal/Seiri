{{-- $item: a request with approvals (leave, claim, overtime). Who it is waiting for in the approval chain. --}}
@if ($current = $item->currentApproval())
    <div class="small text-secondary mt-1">{{ __('Waiting for :name', ['name' => $current->approver->name]) }}</div>
@endif
