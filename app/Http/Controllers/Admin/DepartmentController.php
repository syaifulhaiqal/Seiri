<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DepartmentRequest;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        return view('admin.departments.index', [
            'departments' => Department::query()
                ->search($filters['search'] ?? null)
                ->withCount(['employees' => fn ($query) => $query->active()])
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.departments.form', [
            'department' => new Department,
        ]);
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        $department = Department::query()->create($request->validated());

        return to_route('admin.departments.index')->with('status', __(':name has been added.', ['name' => $department->name]));
    }

    public function edit(Department $department): View
    {
        return view('admin.departments.form', [
            'department' => $department,
        ]);
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        return to_route('admin.departments.index')->with('status', __('Department updated.'));
    }

    public function destroy(Department $department): RedirectResponse
    {
        $name = $department->name;
        $department->delete();

        return to_route('admin.departments.index')->with('status', __(':name has been deleted. Its employees no longer have a department.', ['name' => $name]));
    }
}
