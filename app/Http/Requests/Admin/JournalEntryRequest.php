<?php

namespace App\Http\Requests\Admin;

use App\Models\Account;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A manual journal entry: at least two lines, each a debit or a credit, totals equal.
 */
class JournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        // Drop rows the person left completely empty.
        $this->merge([
            'lines' => collect($this->input('lines', []))
                ->reject(fn ($line) => blank($line['account_id'] ?? null) && blank($line['debit'] ?? null) && blank($line['credit'] ?? null))
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'lines' => ['required', 'array', 'min:2', 'max:50'],
            'lines.*.account_id' => ['required', Rule::exists(Account::class, 'id')->where('is_active', true)],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lines.min' => __('A journal entry needs at least two lines.'),
            'lines.*.account_id.required' => __('Choose an account for every line.'),
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $debits = 0;
                $credits = 0;

                foreach ($this->input('lines') as $index => $line) {
                    $debit = Money::toCents($line['debit'] ?? 0);
                    $credit = Money::toCents($line['credit'] ?? 0);

                    if (($debit > 0) === ($credit > 0)) {
                        $validator->errors()->add("lines.{$index}.debit", __('Enter a debit or a credit on line :number, not both.', ['number' => $index + 1]));
                    }

                    $debits += $debit;
                    $credits += $credit;
                }

                if ($debits !== $credits) {
                    $validator->errors()->add('lines', __('Debits (:debits) and credits (:credits) must be equal.', [
                        'debits' => Money::format(Money::fromCents($debits)),
                        'credits' => Money::format(Money::fromCents($credits)),
                    ]));
                }
            },
        ];
    }
}
