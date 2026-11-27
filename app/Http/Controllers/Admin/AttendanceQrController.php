<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\QrCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * The office clock-in screen: a QR code that changes every few seconds. Staff scan it with their phone camera,
 * which opens a signed link that only works for a few minutes, so a photo sent home soon stops working.
 */
class AttendanceQrController extends Controller
{
    public function show(): View
    {
        return view('admin.attendance.qr', [
            'svg' => $this->svg(),
            'refreshSeconds' => config('hr.qr_refresh_seconds'),
        ]);
    }

    /**
     * A fresh code, fetched by the screen every few seconds.
     */
    public function code(): JsonResponse
    {
        return response()->json(['svg' => $this->svg()]);
    }

    protected function svg(): string
    {
        return QrCode::svg(URL::temporarySignedRoute('attendance.scan', now()->addMinutes(config('hr.qr_valid_minutes'))));
    }
}
