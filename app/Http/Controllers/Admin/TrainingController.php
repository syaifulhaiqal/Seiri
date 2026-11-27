<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TrainingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TrainingRequest;
use App\Models\Employee;
use App\Models\Training;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Training and course records kept by HR, with hours and cost per year.
 */
class TrainingController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(TrainingStatus::class)],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
        ]);
        $year = (int) ($filters['year'] ?? now()->year);

        $query = Training::query()
            ->whereYear('start_date', $year)
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(fn (Builder $query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhereHas('employee', fn (Builder $query) => $query->search($search))));

        $completed = (clone $query)->where('status', TrainingStatus::Completed);

        return view('admin.trainings.index', [
            'trainings' => (clone $query)->with('employee.department')->latest('start_date')->paginate(15)->withQueryString(),
            'filters' => $filters,
            'year' => $year,
            'totals' => [
                'hours' => (float) (clone $completed)->sum('hours'),
                'cost' => (string) (clone $completed)->sum('cost'),
                'people' => (clone $completed)->distinct()->count('employee_id'),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.trainings.create', [
            'training' => new Training(['status' => TrainingStatus::Planned]),
            'employees' => $this->employeeOptions(),
            'employeeId' => $request->integer('employee') ?: null,
        ]);
    }

    public function store(TrainingRequest $request): RedirectResponse
    {
        $training = new Training($request->training());
        $training->employee()->associate($request->validated('employee_id'));
        $training->save();

        return to_route('admin.trainings.index', ['year' => $training->start_date->year])->with('status', __(':title added for :name.', ['title' => $training->title, 'name' => $training->employee->name]));
    }

    public function edit(Training $training): View
    {
        return view('admin.trainings.edit', [
            'training' => $training,
            'employees' => $this->employeeOptions(),
            'employeeId' => $training->employee_id,
        ]);
    }

    public function update(TrainingRequest $request, Training $training): RedirectResponse
    {
        $training->fill($request->training());
        $training->employee()->associate($request->validated('employee_id'));
        $training->save();

        return to_route('admin.trainings.index', ['year' => $training->start_date->year])->with('status', __('Training record updated.'));
    }

    public function destroy(Training $training): RedirectResponse
    {
        $year = $training->start_date->year;
        $title = $training->title;
        $training->delete();

        return to_route('admin.trainings.index', ['year' => $year])->with('status', __(':name has been deleted.', ['name' => $title]));
    }

    /**
     * @return array<int, string>
     */
    protected function employeeOptions(): array
    {
        return Employee::query()->orderBy('name')->get(['id', 'name', 'employee_no'])
            ->mapWithKeys(fn (Employee $employee) => [$employee->id => "{$employee->name} ({$employee->employee_no})"])
            ->all();
    }
}
