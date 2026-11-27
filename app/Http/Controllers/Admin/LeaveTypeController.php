<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LeaveTypeRequest;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LeaveTypeController extends Controller
{
    public function index(): View
    {
        return view('admin.leave-types.index', [
            'leaveTypes' => LeaveType::query()->withCount('leaveRequests')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.leave-types.form', [
            'leaveType' => new LeaveType(['days_per_year' => 0, 'is_paid' => true]),
        ]);
    }

    public function store(LeaveTypeRequest $request): RedirectResponse
    {
        $leaveType = LeaveType::query()->create($request->validated());

        return to_route('admin.leave_types.index')->with('status', __(':name has been added.', ['name' => $leaveType->name]));
    }

    public function edit(LeaveType $leaveType): View
    {
        return view('admin.leave-types.form', [
            'leaveType' => $leaveType,
        ]);
    }

    public function update(LeaveTypeRequest $request, LeaveType $leaveType): RedirectResponse
    {
        $leaveType->update($request->validated());

        return to_route('admin.leave_types.index')->with('status', __('Leave type updated.'));
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        if ($leaveType->leaveRequests()->exists()) {
            return back()->withErrors(['delete' => __(':name has leave requests, so it cannot be deleted.', ['name' => $leaveType->name])]);
        }

        $name = $leaveType->name;
        $leaveType->delete();

        return to_route('admin.leave_types.index')->with('status', __(':name has been deleted.', ['name' => $name]));
    }
}
