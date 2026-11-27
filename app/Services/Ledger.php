<?php

namespace App\Services;

use App\Enums\AuditEvent;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\JournalEntry;
use App\Support\DocumentNumber;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Posts double-entry journal entries. Every money movement in the app goes through here,
 * so the books always balance.
 */
class Ledger
{
    /**
     * Post a balanced entry. Amounts are in sen; a line takes either a debit or a credit.
     *
     * @param  array<int, array{account: Account|string, debit?: int, credit?: int, description?: string|null}>  $lines
     *                                                                                                                   `account` is an Account or a system key such as "bank".
     */
    public function post(Carbon|string $date, string $description, array $lines, null|Model $source = null, null|string $reference = null, null|JournalEntry $reverses = null): JournalEntry
    {
        $lines = collect($lines)
            ->map(fn (array $line) => [
                'account' => $line['account'] instanceof Account ? $line['account'] : Account::system($line['account']),
                'debit' => (int) ($line['debit'] ?? 0),
                'credit' => (int) ($line['credit'] ?? 0),
                'description' => $line['description'] ?? null,
            ])
            ->reject(fn (array $line) => $line['debit'] === 0 && $line['credit'] === 0)
            ->values();

        foreach ($lines as $line) {
            if ($line['debit'] < 0 || $line['credit'] < 0 || ($line['debit'] > 0 && $line['credit'] > 0)) {
                throw new InvalidArgumentException('Each journal line needs either a positive debit or a positive credit.');
            }
        }

        $debits = $lines->sum('debit');
        $credits = $lines->sum('credit');

        if ($lines->count() < 2 || $debits !== $credits || $debits === 0) {
            throw new InvalidArgumentException(sprintf('Journal entry does not balance: debits %s, credits %s.', Money::fromCents($debits), Money::fromCents($credits)));
        }

        return DB::transaction(function () use ($date, $description, $lines, $source, $reference, $reverses, $debits): JournalEntry {
            $entry = new JournalEntry([
                'number' => DocumentNumber::next(JournalEntry::query()->lockForUpdate(), 'JE-', 6),
                'date' => Carbon::parse($date)->toDateString(),
                'description' => $description,
                'reference' => $reference,
            ]);
            $entry->source()->associate($source);
            $entry->reverses()->associate($reverses);
            $entry->created_by = auth()->id();
            $entry->save();

            $entry->lines()->createMany($lines->map(fn (array $line) => [
                'account_id' => $line['account']->id,
                'description' => $line['description'],
                'debit' => Money::fromCents($line['debit']),
                'credit' => Money::fromCents($line['credit']),
            ])->all());

            AuditLog::record(AuditEvent::Posted, $entry, new: [
                'date' => $entry->date->toDateString(),
                'description' => $description,
                'total' => Money::fromCents($debits),
                'lines' => $lines->map(fn (array $line) => sprintf(
                    '%s %s %s',
                    $line['debit'] > 0 ? 'Dr' : 'Cr',
                    $line['account']->code,
                    Money::fromCents($line['debit'] ?: $line['credit']),
                ))->implode('; '),
            ]);

            return $entry;
        });
    }

    /**
     * Cancel an entry by posting its mirror image. The original stays for the record.
     */
    public function reverse(JournalEntry $entry, Carbon|string $date, null|string $description = null, null|Model $source = null): JournalEntry
    {
        if ($entry->reversal()->exists()) {
            throw new InvalidArgumentException("{$entry->number} has already been reversed.");
        }

        return $this->post(
            $date,
            $description ?? __('Reversal of :number', ['number' => $entry->number]),
            $entry->lines()->with('account')->get()->map(fn ($line) => [
                'account' => $line->account,
                'debit' => Money::toCents($line->credit),
                'credit' => Money::toCents($line->debit),
                'description' => $line->description,
            ])->all(),
            $source ?? $entry->source,
            $entry->number,
            $entry,
        );
    }
}
