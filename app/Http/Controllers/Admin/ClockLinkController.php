<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClockLinkRequest;
use App\Models\ClockLink;
use App\Support\QrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Shared clock-in links: HR makes one, sends it to all staff (or prints its QR code), and everyone clocks in
 * with it after signing in. Renewing gives a new address and stops the old one; deleting turns it off.
 */
class ClockLinkController extends Controller
{
    public function index(): View
    {
        return view('admin.clock-links.index', [
            'links' => ClockLink::query()->with('creator')->latest()->get(),
            'link' => new ClockLink,
        ]);
    }

    public function store(ClockLinkRequest $request): RedirectResponse
    {
        $link = new ClockLink($request->validated());
        $link->created_by = $request->user()->id;
        $link->save();

        return to_route('admin.attendance.links.show', $link)->with('status', __('Link created. Copy it and send it to your staff.'));
    }

    public function show(ClockLink $link): View
    {
        return view('admin.clock-links.show', [
            'link' => $link,
            'qr' => QrCode::svg($link->url(), 280),
        ]);
    }

    public function update(ClockLinkRequest $request, ClockLink $link): RedirectResponse
    {
        $link->update($request->validated());

        return to_route('admin.attendance.links.show', $link)->with('status', __('Link updated.'));
    }

    /**
     * A new address for the same link. The old one stops working at once.
     */
    public function renew(ClockLink $link): RedirectResponse
    {
        $link->token = ClockLink::newToken();
        $link->save();

        return to_route('admin.attendance.links.show', $link)->with('status', __('The link has a new address. The old one no longer works, so send this one to your staff.'));
    }

    public function destroy(ClockLink $link): RedirectResponse
    {
        $link->delete();

        return to_route('admin.attendance.links.index')->with('status', __(':name has been turned off.', ['name' => $link->name]));
    }
}
