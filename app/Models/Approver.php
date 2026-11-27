<?php

namespace App\Models;

use App\Enums\ApprovalType;
use App\Models\Concerns\Auditable;
use Database\Factories\ApproverFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One step of an employee's approval chain: who approves their leave, claims or overtime at this level.
 * The approver is a staff login (e.g. the team lead), and decides under Approvals in the user area.
 */
#[Fillable(['type', 'level', 'approver_id'])]
class Approver extends Model
{
    /** @use HasFactory<ApproverFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ApprovalType::class,
            'level' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function auditLabel(): string
    {
        return __(':type approver :level for :name', [
            'type' => $this->type->label(),
            'level' => $this->level,
            'name' => $this->employee?->name ?? '#' . $this->employee_id,
        ]);
    }
}
