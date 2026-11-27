<?php

namespace App\Http\Controllers;

use App\Enums\ClockMethod;
use App\Http\Requests\RemoteClockRequest;
use App\Models\ClockLink;
use App\Models\Employee;
use App\Services\Clock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Clock in or out with a link HR shared with everyone. Signing in tells us who it is, so one link works for all
 * active employees, wherever they are.
 */
class ClockLinkController extends Controller
{
    public function __construct(protected Clock $clock) {}

    public function show(Request $request, string $token): RedirectResponse|View
    {
        $link = $this->link($token);
        $employee = $request->user()->employee;

        if (! $link || ! $employee?->isActive()) {
            return $this->refused($link);
        }

        return view('attendance.link', [
            'link' => $link,
            'employee' => $employee,
            'today' => $this->clock->today($employee),
        ]);
    }

    /**
     * Clocks in, or out if already in.
     */
    public function store(RemoteClockRequest $request, string $token): RedirectResponse
    {
        $link = $this->link($token);
        $employee = $request->user()->employee;

        if (! $link || ! $employee?->isActive()) {
            return $this->refused($link);
        }

        $location = $request->location();

        if ($link->require_location && empty($location['latitude'])) {
            throw ValidationException::withMessages(['delete' => __('This link needs your location. Allow location access in your browser, then try again.')]);
        }

        if (! $this->clock->today($employee)?->clock_in_at) {
            $location['work_location'] ??= $link->name;
            $attendance = $this->clock->in($employee, ClockMethod::Link, $request, $location);

            return to_route('attendance.index')->with('status', __('Clocked in at :time.', ['time' => $attendance->clock_in_at->format('g:i A')]));
        }

        $attendance = $this->clock->out($employee, ClockMethod::Link, $request, $location);

        return to_route('attendance.index')->with('status', __('Clocked out at :time. You worked :hours hours today.', [
            'time' => $attendance->clock_out_at->format('g:i A'),
            'hours' => $attendance->workedHours(),
        ]));
    }

    protected function link(string $token): null|ClockLink
    {
        return ClockLink::query()->usable()->where('token', $token)->first();
    }

    /**
     * An unknown, renewed or expired link, or someone who is not a current employee.
     */
    protected function refused(null|ClockLink $link): RedirectResponse
    {
        $message = $link
            ? __('Your employment has ended, so clocking in is turned off.')
            : __('This clock-in link no longer works. Ask HR for the current link.');

        return to_route('attendance.index')->withErrors(['delete' => $message]);
    }
}
