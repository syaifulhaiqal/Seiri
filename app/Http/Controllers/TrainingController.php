<?php

namespace App\Http\Controllers;

use App\Enums\TrainingStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The signed-in employee's training record, kept by HR.
 */
class TrainingController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;
        $trainings = $employee?->trainings()->latest('start_date')->get() ?? collect();

        return view('trainings.index', [
            'employee' => $employee,
            'trainings' => $trainings,
            'hoursThisYear' => (float) $trainings
                ->filter(fn ($training) => $training->status === TrainingStatus::Completed && $training->start_date->year === now()->year)
                ->sum('hours'),
        ]);
    }
}
