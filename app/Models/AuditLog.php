<?php

namespace App\Models;

use App\Enums\AuditEvent;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use LogicException;

/**
 * One entry in the audit trail. Entries are written once and never changed or removed.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * Turned off while seeding demo data, so the trail only shows real activity.
     */
    protected static bool $recording = true;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event' => AuditEvent::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit log entries cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Audit log entries cannot be deleted.'));
    }

    /**
     * Write an entry for something that happened to a model (or, with no model, to the system).
     * The actor is the signed-in user unless one is given; `$anonymous` records no actor (failed sign-ins).
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public static function record(AuditEvent $event, null|Model $subject = null, array $old = [], array $new = [], null|string $description = null, null|User $user = null, bool $anonymous = false): null|self
    {
        if (! static::$recording) {
            return null;
        }

        $user = $anonymous ? null : ($user ?? auth()->user());
        $request = app()->runningInConsole() ? null : request();

        return static::query()->create([
            'user_id' => $user?->getKey(),
            'user_name' => $user?->name,
            'event' => $event,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'subject_label' => $subject ? static::labelFor($subject) : null,
            'description' => $description,
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? str($request->userAgent())->limit(500)->toString() : null,
            'url' => $request?->fullUrl(),
        ]);
    }

    /**
     * Run a callback without writing audit entries.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function withoutRecording(callable $callback): mixed
    {
        $previous = static::$recording;
        static::$recording = false;

        try {
            return $callback();
        } finally {
            static::$recording = $previous;
        }
    }

    protected static function labelFor(Model $subject): string
    {
        if (method_exists($subject, 'auditLabel')) {
            return $subject->auditLabel();
        }

        return (string) ($subject->getAttribute('name') ?? $subject->getAttribute('number') ?? '#' . $subject->getKey());
    }

    /**
     * Human name for a morph alias, e.g. "leave_request" → "Leave request".
     */
    public static function typeLabel(null|string $type): string
    {
        return $type ? str($type)->replace('_', ' ')->ucfirst()->toString() : __('System');
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return collect(Relation::morphMap())
            ->keys()
            ->sort()
            ->mapWithKeys(fn (string $type) => [$type => static::typeLabel($type)])
            ->all();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Fields that changed, with their old and new values.
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function changes(): array
    {
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];

        return collect(array_keys($old + $new))
            ->mapWithKeys(fn (string $field) => [$field => ['old' => $old[$field] ?? null, 'new' => $new[$field] ?? null]])
            ->all();
    }

    #[Scope]
    protected function search(Builder $query, null|string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(fn (Builder $query) => $query
            ->where('subject_label', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%")
            ->orWhere('user_name', 'like', "%{$term}%"));
    }
}
