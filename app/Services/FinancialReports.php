<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\JournalLine;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Trial balance, profit and loss, balance sheet and account ledgers, worked out from journal lines.
 * Amounts are returned in sen.
 */
class FinancialReports
{
    /**
     * Debit and credit totals per account between two dates (either may be open-ended).
     *
     * @return Collection<int, array{debit: int, credit: int}>
     */
    public function totals(null|Carbon $from, null|Carbon $to): Collection
    {
        return JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->when($from, fn ($query) => $query->whereDate('journal_entries.date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('journal_entries.date', '<=', $to))
            ->groupBy('journal_lines.account_id')
            ->selectRaw('journal_lines.account_id, sum(journal_lines.debit) as debit, sum(journal_lines.credit) as credit')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->account_id => [
                'debit' => Money::toCents($row->debit),
                'credit' => Money::toCents($row->credit),
            ]]);
    }

    /**
     * Balance on the account's normal side: positive means a normal balance.
     *
     * @param  array{debit: int, credit: int}|null  $totals
     */
    public function balance(Account $account, null|array $totals): int
    {
        $totals ??= ['debit' => 0, 'credit' => 0];

        return $account->type->isDebitNormal()
            ? $totals['debit'] - $totals['credit']
            : $totals['credit'] - $totals['debit'];
    }

    /**
     * @return array{rows: Collection<int, array{account: Account, debit: int, credit: int}>, debit: int, credit: int}
     */
    public function trialBalance(Carbon $asOf): array
    {
        $totals = $this->totals(null, $asOf);

        $rows = Account::query()->orderBy('code')->get()
            ->map(function (Account $account) use ($totals) {
                $net = ($totals[$account->id]['debit'] ?? 0) - ($totals[$account->id]['credit'] ?? 0);

                return ['account' => $account, 'debit' => max($net, 0), 'credit' => max(-$net, 0)];
            })
            ->reject(fn (array $row) => $row['debit'] === 0 && $row['credit'] === 0)
            ->values();

        return ['rows' => $rows, 'debit' => $rows->sum('debit'), 'credit' => $rows->sum('credit')];
    }

    /**
     * @return array{income: Collection<int, array{account: Account, amount: int}>, expenses: Collection<int, array{account: Account, amount: int}>, total_income: int, total_expenses: int, net: int}
     */
    public function profitAndLoss(Carbon $from, Carbon $to): array
    {
        $totals = $this->totals($from, $to);

        $income = $this->section(AccountType::Income, $totals);
        $expenses = $this->section(AccountType::Expense, $totals);

        return [
            'income' => $income,
            'expenses' => $expenses,
            'total_income' => $income->sum('amount'),
            'total_expenses' => $expenses->sum('amount'),
            'net' => $income->sum('amount') - $expenses->sum('amount'),
        ];
    }

    /**
     * Assets = liabilities + equity. Profit not yet closed into retained earnings is shown on its own line.
     *
     * @return array{assets: Collection<int, array{account: Account, amount: int}>, liabilities: Collection<int, array{account: Account, amount: int}>, equity: Collection<int, array{account: Account, amount: int}>, total_assets: int, total_liabilities: int, total_equity: int, earnings: int}
     */
    public function balanceSheet(Carbon $asOf): array
    {
        $totals = $this->totals(null, $asOf);

        $assets = $this->section(AccountType::Asset, $totals);
        $liabilities = $this->section(AccountType::Liability, $totals);
        $equity = $this->section(AccountType::Equity, $totals);
        $earnings = $this->section(AccountType::Income, $totals)->sum('amount') - $this->section(AccountType::Expense, $totals)->sum('amount');

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => $assets->sum('amount'),
            'total_liabilities' => $liabilities->sum('amount'),
            'total_equity' => $equity->sum('amount') + $earnings,
            'earnings' => $earnings,
        ];
    }

    /**
     * One account's lines in a period with a running balance.
     *
     * @return array{opening: int, lines: Collection<int, array{line: JournalLine, balance: int}>, closing: int, debit: int, credit: int}
     */
    public function accountLedger(Account $account, Carbon $from, Carbon $to): array
    {
        $opening = $this->balance($account, $this->totals(null, $from->copy()->subDay())[$account->id] ?? null);
        $sign = $account->type->isDebitNormal() ? 1 : -1;
        $running = $opening;

        $lines = JournalLine::query()
            ->select('journal_lines.*')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.account_id', $account->id)
            ->whereDate('journal_entries.date', '>=', $from)
            ->whereDate('journal_entries.date', '<=', $to)
            ->orderBy('journal_entries.date')
            ->orderBy('journal_entries.id')
            ->orderBy('journal_lines.id')
            ->with('entry')
            ->get()
            ->map(function (JournalLine $line) use (&$running, $sign) {
                $running += $sign * (Money::toCents($line->debit) - Money::toCents($line->credit));

                return ['line' => $line, 'balance' => $running];
            });

        return [
            'opening' => $opening,
            'lines' => $lines,
            'closing' => $running,
            'debit' => $lines->sum(fn (array $row) => Money::toCents($row['line']->debit)),
            'credit' => $lines->sum(fn (array $row) => Money::toCents($row['line']->credit)),
        ];
    }

    /**
     * Current balance of every account, keyed by account id.
     *
     * @return Collection<int, int>
     */
    public function balances(null|Carbon $asOf = null): Collection
    {
        $totals = $this->totals(null, $asOf);

        return Account::query()->get()->mapWithKeys(fn (Account $account) => [$account->id => $this->balance($account, $totals[$account->id] ?? null)]);
    }

    /**
     * @param  Collection<int, array{debit: int, credit: int}>  $totals
     * @return Collection<int, array{account: Account, amount: int}>
     */
    protected function section(AccountType $type, Collection $totals): Collection
    {
        return Account::query()->ofType($type)->orderBy('code')->get()
            ->map(fn (Account $account) => ['account' => $account, 'amount' => $this->balance($account, $totals[$account->id] ?? null)])
            ->reject(fn (array $row) => $row['amount'] === 0)
            ->values();
    }
}
