<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One approver's step on one request. Steps are decided in level order (see App\Services\ApprovalFlow);
 * the request itself is audited when it is finally approved or rejected.
 */
#[Fillable(['level', 'approver_id', 'status'])]
class Approval extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'status' => ApprovalStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    /**
     * The leave request, expense claim or overtime request.
     *
     * @return MorphTo<Model, $this>
     */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function isPending(): bool
    {
        return $this->status === ApprovalStatus::Pending;
    }

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', ApprovalStatus::Pending);
    }
}
