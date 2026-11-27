<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\JournalEntryRequest;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\Ledger;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class JournalEntryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'account' => ['nullable', 'integer'],
        ]);

        $entries = JournalEntry::query()
            ->search($filters['search'] ?? null)
            ->when($filters['from'] ?? null, fn ($query, string $from) => $query->whereDate('date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, string $to) => $query->whereDate('date', '<=', $to))
            ->when($filters['account'] ?? null, fn ($query, int $id) => $query->whereHas('lines', fn ($query) => $query->where('account_id', $id)))
            ->withSum('lines as total', 'debit')
            ->with('reversal:id,reverses_id,number')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.finance.journal.index', [
            'entries' => $entries,
            'filters' => $filters,
            'accounts' => $this->accountOptions(),
        ]);
    }

    public function create(): View
    {
        return view('admin.finance.journal.create', [
            'accounts' => $this->accountOptions(),
        ]);
    }

    public function store(JournalEntryRequest $request, Ledger $ledger): RedirectResponse
    {
        $data = $request->validated();
        $accounts = Account::query()->findMany(collect($data['lines'])->pluck('account_id'))->keyBy('id');

        $entry = $ledger->post($data['date'], $data['description'], collect($data['lines'])->map(fn (array $line) => [
            'account' => $accounts[$line['account_id']],
            'debit' => Money::toCents($line['debit'] ?? 0),
            'credit' => Money::toCents($line['credit'] ?? 0),
            'description' => $line['description'] ?? null,
        ])->all(), reference: $data['reference'] ?? null);

        return to_route('admin.journal_entries.show', $entry)->with('status', __(':number posted.', ['number' => $entry->number]));
    }

    public function show(JournalEntry $journalEntry): View
    {
        $journalEntry->load(['lines.account', 'creator', 'source', 'reverses', 'reversal']);

        return view('admin.finance.journal.show', [
            'entry' => $journalEntry,
            'debit' => $journalEntry->lines->sum(fn ($line) => Money::toCents($line->debit)),
            'credit' => $journalEntry->lines->sum(fn ($line) => Money::toCents($line->credit)),
        ]);
    }

    /**
     * Only manual entries are reversed here. Entries from invoices, payroll or claims are
     * corrected from their own pages so the documents stay in step with the ledger.
     */
    public function reverse(JournalEntry $journalEntry, Ledger $ledger): RedirectResponse
    {
        if ($journalEntry->source_type !== null || $journalEntry->reverses_id !== null || $journalEntry->reversal()->exists()) {
            return back()->withErrors(['delete' => __(':number cannot be reversed here.', ['number' => $journalEntry->number])]);
        }

        $reversal = $ledger->reverse($journalEntry, today());

        return to_route('admin.journal_entries.show', $reversal)->with('status', __(':number reversed by :reversal.', [
            'number' => $journalEntry->number,
            'reversal' => $reversal->number,
        ]));
    }

    /**
     * @return Collection<int, string>
     */
    protected function accountOptions(): Collection
    {
        return Account::query()->active()->orderBy('code')->get()->mapWithKeys(fn (Account $account) => [$account->id => $account->displayName()]);
    }
}
