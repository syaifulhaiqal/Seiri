<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClaimStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClaimReviewRequest;
use App\Http\Requests\Admin\PayFromAccountRequest;
use App\Models\Account;
use App\Models\ExpenseClaim;
use App\Services\ExpenseClaimPosting;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseClaimController extends Controller
{
    public function __construct(protected ExpenseClaimPosting $posting) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(ClaimStatus::class)],
        ]);

        $claims = ExpenseClaim::query()
            ->with(['employee', 'account'])
            ->when($filters['search'] ?? null, fn ($query, string $term) => $query->where(fn (Builder $query) => $query
                ->where('number', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhereHas('employee', fn (Builder $query) => $query->search($term))))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderByRaw('case when status = ? then 0 when status = ? then 1 else 2 end', [ClaimStatus::Pending->value, ClaimStatus::Approved->value])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.finance.claims.index', [
            'claims' => $claims,
            'filters' => $filters,
            'pending' => ExpenseClaim::query()->where('status', ClaimStatus::Pending)->count(),
            'toPay' => (float) ExpenseClaim::query()->where('status', ClaimStatus::Approved)->sum('amount'),
        ]);
    }

    public function show(ExpenseClaim $claim): View
    {
        $claim->load(['employee.department', 'account', 'reviewer']);

        return view('admin.finance.claims.show', [
            'claim' => $claim,
            'bankAccounts' => Account::query()->cashAndBank()->active()->orderBy('code')->get()->mapWithKeys(fn (Account $account) => [$account->id => $account->displayName()]),
        ]);
    }

    public function review(ClaimReviewRequest $request, ExpenseClaim $claim): RedirectResponse
    {
        if (! $claim->isPending()) {
            return back()->withErrors(['review' => __('This claim has already been reviewed.')]);
        }

        $note = $request->validated('review_note');

        $request->validated('decision') === 'approve'
            ? $this->posting->approve($claim, $request->user(), $note)
            : $this->posting->reject($claim, $request->user(), $note);

        return to_route('admin.claims.show', $claim)->with('status', __(':number :status.', [
            'number' => $claim->number,
            'status' => strtolower($claim->status->label()),
        ]));
    }

    public function reimburse(PayFromAccountRequest $request, ExpenseClaim $claim): RedirectResponse
    {
        if ($claim->status !== ClaimStatus::Approved) {
            return back()->withErrors(['review' => __('Only approved claims can be paid back.')]);
        }

        $this->posting->reimburse($claim, Account::query()->findOrFail($request->validated('account_id')), $request->validated('date'));

        return to_route('admin.claims.show', $claim)->with('status', __(':amount paid back to :name.', [
            'amount' => Money::format($claim->amount),
            'name' => $claim->employee->name,
        ]));
    }

    public function receipt(ExpenseClaim $claim): StreamedResponse
    {
        abort_unless($claim->receipt_path && Storage::disk('local')->exists($claim->receipt_path), 404);

        return Storage::disk('local')->response($claim->receipt_path, $claim->receipt_name);
    }
}
