<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PayItemRequest;
use App\Models\Employee;
use App\Models\PayItem;
use Illuminate\Http\RedirectResponse;

class PayItemController extends Controller
{
    public function store(PayItemRequest $request, Employee $employee): RedirectResponse
    {
        $item = $employee->payItems()->create($request->validated());

        return to_route('admin.employees.show', $employee)->with('status', __(':name added to :employee\'s pay.', ['name' => $item->name, 'employee' => $employee->name]));
    }

    public function destroy(Employee $employee, PayItem $payItem): RedirectResponse
    {
        $name = $payItem->name;
        $payItem->delete();

        return to_route('admin.employees.show', $employee)->with('status', __(':name removed. Draft payroll changes when you recalculate it.', ['name' => $name]));
    }
}
