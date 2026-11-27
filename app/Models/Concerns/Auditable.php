<?php

namespace App\Models\Concerns;

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use Illuminate\Support\Arr;

/**
 * Writes an audit entry whenever the model is created, updated or deleted.
 *
 * Hidden attributes (such as passwords) and timestamps are never stored.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (self $model): void {
            AuditLog::record(AuditEvent::Created, $model, new: $model->auditableValues($model->getAttributes()));
        });

        static::updated(function (self $model): void {
            $new = $model->auditableValues($model->getChanges());
            $old = Arr::only($model->auditableValues($model->getRawOriginal()), array_keys($new));

            // Hidden fields such as the password are noted as changed, never stored.
            $hiddenChanges = array_diff(array_intersect(array_keys($model->getChanges()), $model->getHidden()), ['remember_token']);

            foreach ($hiddenChanges as $field) {
                $old[$field] = '••••••';
                $new[$field] = __('(changed)');
            }

            if ($new === []) {
                return;
            }

            AuditLog::record(AuditEvent::Updated, $model, $old, $new);
        });

        static::deleted(function (self $model): void {
            AuditLog::record(AuditEvent::Deleted, $model, $model->auditableValues($model->getRawOriginal()));
        });
    }

    /**
     * Record a business event, such as an approval, against this model.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function audit(AuditEvent $event, null|string $description = null, array $old = [], array $new = []): void
    {
        AuditLog::record($event, $this, $old, $new, $description);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function auditableValues(array $values): array
    {
        $excluded = [...$this->getHidden(), 'created_at', 'updated_at', 'remember_token', ...$this->auditExclude()];

        return collect(Arr::except($values, $excluded))
            ->map(fn (mixed $value) => $value instanceof \BackedEnum ? $value->value : $value)
            ->map(fn (mixed $value) => $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : $value)
            ->all();
    }

    /**
     * Extra attributes this model keeps out of the trail.
     *
     * @return array<int, string>
     */
    protected function auditExclude(): array
    {
        return [];
    }
}
