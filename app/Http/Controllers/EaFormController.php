<?php

namespace App\Http\Controllers;

use App\Services\EaStatement;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The signed-in employee's yearly EA statements, for filing their income tax.
 */
class EaFormController extends Controller
{
    public function __construct(protected EaStatement $statements) {}

    public function index(Request $request): View
    {
        $employee = $request->user()->employee;

        return view('ea-forms.index', [
            'employee' => $employee,
            'years' => $employee ? $this->statements->years($employee) : [],
        ]);
    }

    public function show(Request $request, int $year): View
    {
        $employee = $request->user()->employee;
        $statement = $employee ? $this->statements->for($employee, $year) : null;

        abort_if($statement === null, 404);

        return view('ea-forms.show', [
            'statement' => $statement,
        ]);
    }
}
