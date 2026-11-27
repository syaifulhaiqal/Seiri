<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApprovalType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproverRequest;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Who approves an employee's leave, claims and overtime, and in which order.
 */
class ApproverController extends Controller
{
    public function edit(Employee $employee): View
    {
        return view('admin.employees.approvers', [
            'employee' => $employee,
            'chains' => $employee->approvers()->get()->groupBy(fn ($approver) => $approver->type->value)
                ->map(fn ($approvers) => $approvers->pluck('approver_id', 'level')->all()),
            'choices' => ApproverRequest::choices($employee),
        ]);
    }

    /**
     * Requests already waiting keep their chain; the new one applies to the next requests.
     */
    public function update(ApproverRequest $request, Employee $employee): RedirectResponse
    {
        DB::transaction(function () use ($request, $employee): void {
            foreach ($request->chains() as $type => $levels) {
                $existing = $employee->approvers()->where('type', $type)->get()->keyBy('level');

                foreach (range(1, ApprovalType::LEVELS) as $level) {
                    $approver = $existing->get($level);

                    if (! isset($levels[$level])) {
                        $approver?->delete();

                        continue;
                    }

                    ($approver ?? $employee->approvers()->make(['type' => $type, 'level' => $level]))
                        ->fill(['approver_id' => $levels[$level]])
                        ->save();
                }
            }
        });

        return to_route('admin.employees.show', $employee)->with('status', __('Approvers for :name saved. They apply to new requests.', ['name' => $employee->name]));
    }
}
