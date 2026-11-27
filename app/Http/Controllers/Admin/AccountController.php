<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AccountRequest;
use App\Models\Account;
use App\Services\FinancialReports;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request, FinancialReports $reports): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::enum(AccountType::class)],
        ]);

        $accounts = Account::query()
            ->search($filters['search'] ?? null)
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->withCount('lines')
            ->orderBy('code')
            ->get();

        return view('admin.finance.accounts.index', [
            'groups' => $accounts->groupBy(fn (Account $account) => $account->type->value),
            'balances' => $reports->balances(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.finance.accounts.form', [
            'account' => new Account(['type' => AccountType::Expense, 'is_active' => true]),
        ]);
    }

    public function store(AccountRequest $request): RedirectResponse
    {
        $account = Account::query()->create($request->validated());

        return to_route('admin.accounts.index')->with('status', __(':name has been added.', ['name' => $account->displayName()]));
    }

    public function edit(Account $account): View
    {
        return view('admin.finance.accounts.form', [
            'account' => $account,
        ]);
    }

    public function update(AccountRequest $request, Account $account): RedirectResponse
    {
        $account->update($request->validated());

        return to_route('admin.accounts.index')->with('status', __('Account updated.'));
    }

    public function destroy(Account $account): RedirectResponse
    {
        if ($account->isSystem()) {
            return back()->withErrors(['delete' => __('The app posts to :name, so it cannot be deleted.', ['name' => $account->displayName()])]);
        }

        if ($account->lines()->exists()) {
            return back()->withErrors(['delete' => __(':name has journal lines. Turn it off instead of deleting it.', ['name' => $account->displayName()])]);
        }

        $name = $account->displayName();
        $account->delete();

        return to_route('admin.accounts.index')->with('status', __(':name has been deleted.', ['name' => $name]));
    }
}
