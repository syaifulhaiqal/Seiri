<?php

namespace App\Enums;

/**
 * Sales invoices go to customers (money in); purchase bills come from suppliers (money out).
 */
enum InvoiceType: string
{
    case Sales = 'sales';
    case Purchase = 'purchase';

    public function label(): string
    {
        return match ($this) {
            self::Sales => __('Invoice'),
            self::Purchase => __('Bill'),
        };
    }

    public function pluralLabel(): string
    {
        return match ($this) {
            self::Sales => __('Invoices'),
            self::Purchase => __('Bills'),
        };
    }

    public function contactLabel(): string
    {
        return match ($this) {
            self::Sales => __('Customer'),
            self::Purchase => __('Supplier'),
        };
    }

    public function numberPrefix(): string
    {
        return match ($this) {
            self::Sales => 'INV-',
            self::Purchase => 'BILL-',
        };
    }

    /**
     * Route name prefix, e.g. "admin.invoices".
     */
    public function route(): string
    {
        return match ($this) {
            self::Sales => 'admin.invoices',
            self::Purchase => 'admin.bills',
        };
    }

    /**
     * Account type used on the lines: income for invoices, expense for bills.
     */
    public function lineAccountType(): AccountType
    {
        return match ($this) {
            self::Sales => AccountType::Income,
            self::Purchase => AccountType::Expense,
        };
    }

    /**
     * Contacts that can appear on this kind of document.
     *
     * @return array<int, ContactType>
     */
    public function contactTypes(): array
    {
        return match ($this) {
            self::Sales => [ContactType::Customer, ContactType::Both],
            self::Purchase => [ContactType::Supplier, ContactType::Both],
        };
    }
}
