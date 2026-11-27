<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Movement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Who is out of the office on a given day, and where.
 */
class MovementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date'],
            'department' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $date = isset($filters['date']) ? Carbon::parse($filters['date'])->startOfDay() : today();

        $movements = Movement::query()
            ->with('employee.department')
            ->whereDate('date', $date)
            ->whereHas('employee', fn (Builder $query) => $query
                ->search($filters['search'] ?? null)
                ->when($filters['department'] ?? null, fn ($query, int $id) => $query->where('department_id', $id)))
            ->orderBy('time_out')
            ->get();

        return view('admin.movements.index', [
            'date' => $date,
            'filters' => $filters,
            'movements' => $movements,
            'outCount' => $movements->filter->isOut()->count(),
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
