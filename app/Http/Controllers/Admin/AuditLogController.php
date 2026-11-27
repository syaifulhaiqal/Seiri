<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The audit trail: read and export only. Nothing here changes or removes an entry.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        return view('admin.audit.index', [
            'logs' => $this->query($filters)->with('user')->paginate(30)->withQueryString(),
            'filters' => $filters,
            'users' => User::query()->whereIn('id', AuditLog::query()->select('user_id')->whereNotNull('user_id'))->orderBy('name')->pluck('name', 'id'),
            'types' => AuditLog::typeOptions(),
        ]);
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('user');

        return view('admin.audit.show', [
            'log' => $auditLog,
            'history' => $auditLog->subject_type
                ? AuditLog::query()
                    ->where('subject_type', $auditLog->subject_type)
                    ->where('subject_id', $auditLog->subject_id)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->take(20)
                    ->get()
                : collect(),
        ]);
    }

    /**
     * Download the filtered trail as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->query($this->filters($request));

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Time', 'User', 'Event', 'Record type', 'Record', 'Description', 'Changes', 'IP address']);

            $query->lazyById(500, 'id')->each(function (AuditLog $log) use ($out): void {
                fputcsv($out, [
                    $log->created_at->toDateTimeString(),
                    $log->user_name ?? 'System',
                    $log->event->label(),
                    AuditLog::typeLabel($log->subject_type),
                    $log->subject_label,
                    $log->description,
                    collect($log->changes())
                        ->map(fn (array $change, string $field) => $field . ': ' . json_encode($change['old']) . ' → ' . json_encode($change['new']))
                        ->implode('; '),
                    $log->ip_address,
                ]);
            });

            fclose($out);
        }, 'audit-trail-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function filters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'event' => ['nullable', Rule::enum(AuditEvent::class)],
            'type' => ['nullable', 'string', 'max:50'],
            'subject' => ['nullable', 'integer'],
            'user' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<AuditLog>
     */
    protected function query(array $filters): Builder
    {
        return AuditLog::query()
            ->search($filters['search'] ?? null)
            ->when($filters['event'] ?? null, fn ($query, string $event) => $query->where('event', $event))
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('subject_type', $type))
            ->when($filters['subject'] ?? null, fn ($query, int $id) => $query->where('subject_id', $id))
            ->when($filters['user'] ?? null, fn ($query, int $id) => $query->where('user_id', $id))
            ->when($filters['from'] ?? null, fn ($query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
