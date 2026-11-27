<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\AuditEvent;
use App\Enums\ClaimStatus;
use App\Http\Requests\ClaimSubmitRequest;
use App\Models\Account;
use App\Models\ExpenseClaim;
use App\Support\DocumentNumber;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Self-service expense claims for the signed-in employee.
 */
class ClaimController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;

        return view('claims.index', [
            'employee' => $employee,
            'categories' => Account::query()->active()->ofType(AccountType::Expense)->whereNull('system_key')->orderBy('name')->pluck('name', 'id'),
            'claims' => $employee
                ? $employee->expenseClaims()->with('account')->orderByDesc('date')->orderByDesc('id')->paginate(10)
                : null,
        ]);
    }

    public function store(ClaimSubmitRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $employee = $request->user()->employee;

        $claim = DB::transaction(function () use ($data, $employee, $request): ExpenseClaim {
            $claim = new ExpenseClaim($data);
            $claim->employee()->associate($employee);
            $claim->number = DocumentNumber::next(ExpenseClaim::query()->lockForUpdate(), 'CLM-');
            $claim->status = ClaimStatus::Pending;

            if ($request->hasFile('receipt')) {
                $file = $request->file('receipt');
                $claim->receipt_path = $file->store('receipts/' . $employee->id, 'local');
                $claim->receipt_name = str($file->getClientOriginalName())->limit(200, '')->toString();
            }

            $claim->save();

            return $claim;
        });

        return to_route('claims.index')->with('status', __('Claim :number for :amount sent for approval.', [
            'number' => $claim->number,
            'amount' => Money::format($claim->amount),
        ]));
    }

    public function cancel(Request $request, ExpenseClaim $claim): RedirectResponse
    {
        abort_unless($claim->employee_id === $request->user()->employee?->id, 403);

        if (! $claim->isPending()) {
            return back()->withErrors(['delete' => __('Only pending claims can be cancelled.')]);
        }

        $claim->status = ClaimStatus::Cancelled;
        $claim->save();
        $claim->audit(AuditEvent::Cancelled);

        return to_route('claims.index')->with('status', __('Claim :number cancelled.', ['number' => $claim->number]));
    }

    public function receipt(Request $request, ExpenseClaim $claim): StreamedResponse
    {
        abort_unless($claim->employee_id === $request->user()->employee?->id, 403);
        abort_unless($claim->receipt_path && Storage::disk('local')->exists($claim->receipt_path), 404);

        return Storage::disk('local')->response($claim->receipt_path, $claim->receipt_name);
    }
}
