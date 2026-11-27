@php
    $changes = $log->changes(auth()->user()->can('payroll.view'));
@endphp

<x-layouts.app :title="__('Audit entry')">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.audit.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to audit trail') }}
            </a>
            <h1 class="mt-2">
                {{ $log->event->label() }}
                @if ($log->subject_label)
                    · {{ $log->subject_label }}
                @endif
            </h1>
            <p class="text-secondary mb-0">{{ $log->created_at->translatedFormat('l, j F Y, g:i:s A') }}</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-4 text-secondary fw-normal">{{ __('Event') }}</dt>
                        <dd class="col-8"><x-enum-badge :value="$log->event" /></dd>

                        <dt class="col-4 text-secondary fw-normal">{{ __('User') }}</dt>
                        <dd class="col-8">
                            {{ $log->user_name ?? __('System') }}
                            @if ($log->user_id && ! $log->user)
                                <span class="text-secondary">({{ __('since deleted') }})</span>
                            @endif
                        </dd>

                        <dt class="col-4 text-secondary fw-normal">{{ __('Record') }}</dt>
                        <dd class="col-8">
                            {{ \App\Models\AuditLog::typeLabel($log->subject_type) }}
                            @if ($log->subject_id)
                                #{{ $log->subject_id }}
                            @endif
                        </dd>

                        <dt class="col-4 text-secondary fw-normal">{{ __('Description') }}</dt>
                        <dd class="col-8">{{ $log->description ?: '—' }}</dd>

                        <dt class="col-4 text-secondary fw-normal">{{ __('IP address') }}</dt>
                        <dd class="col-8">{{ $log->ip_address ?: '—' }}</dd>

                        <dt class="col-4 text-secondary fw-normal">{{ __('Page') }}</dt>
                        <dd class="col-8 text-break">{{ $log->url ?: '—' }}</dd>

                        <dt class="col-4 text-secondary fw-normal">{{ __('Browser') }}</dt>
                        <dd class="col-8 mb-0 text-break text-secondary">{{ $log->user_agent ?: '—' }}</dd>
                    </dl>
                </div>
            </div>

            @if ($history->count() > 1)
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h2>{{ __('History of this record') }}</h2>
                        <a href="{{ route('admin.audit.index', ['type' => $log->subject_type, 'subject' => $log->subject_id]) }}" class="small">{{ __('View all') }}</a>
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach ($history as $entry)
                            <li @class(['list-group-item d-flex justify-content-between gap-2 small', 'bg-body-tertiary' => $entry->is($log)])>
                                <span>
                                    <a href="{{ route('admin.audit.show', $entry) }}" class="text-decoration-none">{{ $entry->event->label() }}</a>
                                    <span class="text-secondary">· {{ $entry->user_name ?? __('System') }}</span>
                                </span>
                                <span class="text-secondary text-nowrap">{{ $entry->created_at->translatedFormat('j M Y, g:i A') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h2>{{ __('Changes') }}</h2>
                </div>
                @if ($changes)
                    <div class="table-responsive">
                        <table class="table mb-0 small">
                            <thead>
                                <tr>
                                    <th class="ps-3">{{ __('Field') }}</th>
                                    <th>{{ __('Before') }}</th>
                                    <th class="pe-3">{{ __('After') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($changes as $field => $change)
                                    <tr>
                                        <td class="ps-3 font-monospace">{{ $field }}</td>
                                        <td class="text-danger-emphasis">@include('partials.audit-value', ['value' => $change['old']])</td>
                                        <td class="pe-3 text-success-emphasis">@include('partials.audit-value', ['value' => $change['new']])</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="card-body text-secondary small">{{ __('No field values were recorded for this event.') }}</div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
