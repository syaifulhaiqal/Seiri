<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\FinancialReports;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(protected FinancialReports $reports) {}

    public function trialBalance(Request $request): View
    {
        $asOf = $this->date($request, 'as_of', today());

        return view('admin.finance.reports.trial-balance', [
            'asOf' => $asOf,
            'report' => $this->reports->trialBalance($asOf),
        ]);
    }

    public function profitAndLoss(Request $request): View
    {
        [$from, $to] = $this->range($request);

        return view('admin.finance.reports.profit-and-loss', [
            'from' => $from,
            'to' => $to,
            'report' => $this->reports->profitAndLoss($from, $to),
        ]);
    }

    public function balanceSheet(Request $request): View
    {
        $asOf = $this->date($request, 'as_of', today());

        return view('admin.finance.reports.balance-sheet', [
            'asOf' => $asOf,
            'report' => $this->reports->balanceSheet($asOf),
        ]);
    }

    public function generalLedger(Request $request): View
    {
        [$from, $to] = $this->range($request);
        $request->validate(['account' => ['nullable', 'integer']]);

        $accounts = Account::query()->orderBy('code')->get();
        $account = $accounts->firstWhere('id', (int) $request->query('account')) ?? $accounts->firstWhere('system_key', 'bank');

        return view('admin.finance.reports.general-ledger', [
            'from' => $from,
            'to' => $to,
            'account' => $account,
            'accounts' => $accounts->mapWithKeys(fn (Account $account) => [$account->id => $account->displayName()]),
            'report' => $account ? $this->reports->accountLedger($account, $from, $to) : null,
        ]);
    }

    protected function date(Request $request, string $key, Carbon $default): Carbon
    {
        $request->validate([$key => ['nullable', 'date']]);

        return $request->date($key)?->startOfDay() ?? $default;
    }

    /**
     * From / to dates, defaulting to the current year so far.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function range(Request $request): array
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [
            $request->date('from')?->startOfDay() ?? today()->startOfYear(),
            $request->date('to')?->startOfDay() ?? today(),
        ];
    }
}
