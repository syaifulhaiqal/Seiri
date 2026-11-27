<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\EaStatement;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Every employee's EA statement for a year, from approved payroll.
 */
class EaFormController extends Controller
{
    public function __construct(protected EaStatement $statements) {}

    public function index(Request $request): View
    {
        $year = (int) ($request->validate(['year' => ['nullable', 'integer', 'between:2000,2100']])['year'] ?? now()->subYear()->year);

        return view('admin.ea-forms.index', [
            'year' => $year,
            'rows' => $this->statements->summary($year),
        ]);
    }

    public function show(int $year, Employee $employee): View
    {
        $statement = $this->statements->for($employee, $year);

        abort_if($statement === null, 404);

        return view('admin.ea-forms.show', [
            'statement' => $statement,
        ]);
    }
}
