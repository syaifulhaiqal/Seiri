<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Models\Account;
use Illuminate\Database\Seeder;

/**
 * A starter chart of accounts for a Malaysian SME. Accounts with a system key are the ones
 * payroll, invoices and claims post to; they are created here and cannot be deleted.
 */
class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->accounts() as [$code, $name, $type, $systemKey]) {
            $account = Account::query()->firstOrNew(['code' => $code]);

            if (! $account->exists) {
                $account->fill(['name' => $name, 'type' => $type, 'is_active' => true]);
            }

            $account->system_key = $systemKey;
            $account->save();
        }
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: AccountType, 3: string|null}>
     */
    protected function accounts(): array
    {
        return [
            ['1000', 'Cash on hand', AccountType::Asset, 'cash'],
            ['1010', 'Bank account', AccountType::Asset, 'bank'],
            ['1100', 'Accounts receivable', AccountType::Asset, 'accounts_receivable'],
            ['1200', 'Prepayments and deposits', AccountType::Asset, null],
            ['1500', 'Office equipment', AccountType::Asset, null],

            ['2000', 'Accounts payable', AccountType::Liability, 'accounts_payable'],
            ['2100', 'Salaries payable', AccountType::Liability, 'salaries_payable'],
            ['2110', 'EPF payable', AccountType::Liability, 'epf_payable'],
            ['2120', 'SOCSO payable', AccountType::Liability, 'socso_payable'],
            ['2130', 'EIS payable', AccountType::Liability, 'eis_payable'],
            ['2140', 'PCB payable', AccountType::Liability, 'pcb_payable'],
            ['2150', 'Other payroll deductions payable', AccountType::Liability, 'other_payroll_payable'],
            ['2200', 'Expense claims payable', AccountType::Liability, 'claims_payable'],
            ['2300', 'SST payable', AccountType::Liability, 'sales_tax'],

            ['3000', "Owner's capital", AccountType::Equity, 'capital'],
            ['3100', 'Retained earnings', AccountType::Equity, 'retained_earnings'],

            ['4000', 'Sales revenue', AccountType::Income, 'sales'],
            ['4100', 'Service revenue', AccountType::Income, null],
            ['4900', 'Other income', AccountType::Income, null],

            ['5000', 'Cost of sales', AccountType::Expense, null],
            ['6000', 'Salaries and wages', AccountType::Expense, 'salary_expense'],
            ['6010', 'Employer EPF', AccountType::Expense, 'epf_expense'],
            ['6020', 'Employer SOCSO', AccountType::Expense, 'socso_expense'],
            ['6030', 'Employer EIS', AccountType::Expense, 'eis_expense'],
            ['6100', 'Rent', AccountType::Expense, null],
            ['6200', 'Utilities', AccountType::Expense, null],
            ['6300', 'Travel and transport', AccountType::Expense, null],
            ['6400', 'Meals and entertainment', AccountType::Expense, null],
            ['6500', 'Office supplies', AccountType::Expense, null],
            ['6600', 'Professional fees', AccountType::Expense, null],
            ['6700', 'Software and subscriptions', AccountType::Expense, null],
            ['6900', 'Other expenses', AccountType::Expense, null],
        ];
    }
}
