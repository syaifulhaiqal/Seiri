<?php

namespace App\Http\Controllers;

use App\Http\Requests\MovementRequest;
use App\Models\Movement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Self-service movement log: where the employee went during working hours and when they came back.
 */
class MovementController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;

        return view('movements.index', [
            'employee' => $employee,
            'movements' => $employee?->movements()->latest('date')->latest('time_out')->paginate(15),
        ]);
    }

    public function store(MovementRequest $request): RedirectResponse
    {
        $movement = new Movement($request->validated());
        $movement->employee()->associate($request->user()->employee);
        $movement->save();

        return to_route('movements.index')->with('status', __('Movement to :destination logged.', ['destination' => $movement->destination]));
    }

    /**
     * Mark the movement as back now.
     */
    public function back(Request $request, Movement $movement): RedirectResponse
    {
        abort_unless($movement->employee_id === $request->user()->employee?->id, 403);

        if (! $movement->isOut()) {
            return back()->withErrors(['delete' => __('This movement already has a return time.')]);
        }

        $movement->time_back = now()->format('H:i');
        $movement->save();

        return to_route('movements.index')->with('status', __('Welcome back. Return time saved.'));
    }

    public function destroy(Request $request, Movement $movement): RedirectResponse
    {
        abort_unless($movement->employee_id === $request->user()->employee?->id, 403);

        if ($movement->date->lt(today())) {
            return back()->withErrors(['delete' => __('Only today\'s and future movements can be deleted.')]);
        }

        $movement->delete();

        return to_route('movements.index')->with('status', __('Movement deleted.'));
    }
}
