<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HolidayRequest;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The public holiday calendar, one year at a time.
 */
class HolidayController extends Controller
{
    public function index(Request $request): View
    {
        $year = (int) ($request->validate(['year' => ['nullable', 'integer', 'between:2000,2100']])['year'] ?? now()->year);

        return view('admin.holidays.index', [
            'year' => $year,
            'holidays' => Holiday::query()->whereYear('date', $year)->orderBy('date')->get(),
            'holiday' => new Holiday(['date' => null]),
        ]);
    }

    public function store(HolidayRequest $request): RedirectResponse
    {
        $holiday = Holiday::query()->create($request->validated());

        return to_route('admin.holidays.index', ['year' => $holiday->date->year])->with('status', __(':name has been added.', ['name' => $holiday->name]));
    }

    public function edit(Holiday $holiday): View
    {
        return view('admin.holidays.edit', [
            'holiday' => $holiday,
        ]);
    }

    public function update(HolidayRequest $request, Holiday $holiday): RedirectResponse
    {
        $holiday->update($request->validated());

        return to_route('admin.holidays.index', ['year' => $holiday->date->year])->with('status', __('Holiday updated.'));
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $year = $holiday->date->year;
        $name = $holiday->name;
        $holiday->delete();

        return to_route('admin.holidays.index', ['year' => $year])->with('status', __(':name has been deleted.', ['name' => $name]));
    }
}
