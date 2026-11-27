<x-layouts.app :title="__('Audit trail')">
    <div class="page-header">
        <div>
            <h1>{{ __('Audit trail') }}</h1>
            <p class="text-secondary mb-0">{{ __('Who did what, and when. Entries cannot be edited or deleted.') }}</p>
        </div>
        <a href="{{ route('admin.audit.export', request()->query()) }}" class="btn btn-outline-secondary">
            <i class="bi bi-download me-1"></i>{{ __('Export CSV') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.audit.index') }}" class="row g-2 align-items-center" x-data="autoSubmit">
                <div class="col-md-6 col-xl-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-secondary"></i></span>
                        <input type="search" name="search" class="form-control border-start-0 ps-0" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search record, user or description') }}" aria-label="{{ __('Search') }}">
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <select name="event" class="form-select" aria-label="{{ __('Event') }}" x-on:change="submit()">
                        <option value="">{{ __('All events') }}</option>
                        @foreach (\App\Enums\AuditEvent::options() as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['event'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <select name="type" class="form-select" aria-label="{{ __('Record type') }}" x-on:change="submit()">
                        <option value="">{{ __('All records') }}</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <select name="user" class="form-select" aria-label="{{ __('User') }}" x-on:change="submit()">
                        <option value="">{{ __('Everyone') }}</option>
                        @foreach ($users as $id => $name)
                            <option value="{{ $id }}" @selected((string) ($filters['user'] ?? '') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-3 col-md-4 col-xl-1">
                    <input type="date" name="from" class="form-control px-2" value="{{ $filters['from'] ?? '' }}" aria-label="{{ __('From') }}" title="{{ __('From') }}" x-on:change="submit()">
                </div>
                <div class="col-3 col-md-4 col-xl-1">
                    <input type="date" name="to" class="form-control px-2" value="{{ $filters['to'] ?? '' }}" aria-label="{{ __('To') }}" title="{{ __('To') }}" x-on:change="submit()">
                </div>
                @if (! empty($filters['subject']))
                    <input type="hidden" name="subject" value="{{ $filters['subject'] }}">
                @endif
                <div class="col-12 col-xl-1 d-flex gap-2 justify-content-xl-end">
                    @if (array_filter($filters))
                        <a href="{{ route('admin.audit.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    @else
                        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i><span class="visually-hidden">{{ __('Filter') }}</span></button>
                    @endif
                </div>
            </form>
            @if ($errors->any())
                <div class="text-danger small mt-2">{{ $errors->first() }}</div>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">{{ __('When') }}</th>
                        <th>{{ __('User') }}</th>
                        <th>{{ __('Event') }}</th>
                        <th>{{ __('Record') }}</th>
                        <th class="pe-3">{{ __('Details') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="ps-3 text-nowrap small">
                                <a href="{{ route('admin.audit.show', $log) }}" class="text-decoration-none" title="{{ $log->created_at->toDayDateTimeString() }}">
                                    {{ $log->created_at->format('j M Y, g:i:s A') }}
                                </a>
                            </td>
                            <td class="small">
                                {{ $log->user_name ?? __('System') }}
                                @if ($log->ip_address)
                                    <div class="text-secondary">{{ $log->ip_address }}</div>
                                @endif
                            </td>
                            <td><x-enum-badge :value="$log->event" /></td>
                            <td class="small">
                                <span class="text-secondary">{{ \App\Models\AuditLog::typeLabel($log->subject_type) }}</span>
                                @if ($log->subject_label)
                                    <div class="fw-medium">{{ $log->subject_label }}</div>
                                @endif
                            </td>
                            <td class="small pe-3">
                                @if ($log->description)
                                    <div>{{ $log->description }}</div>
                                @endif
                                @if ($fields = array_keys($log->changes()))
                                    <div class="text-secondary">{{ __('Changed: :fields', ['fields' => implode(', ', array_slice($fields, 0, 6)) . (count($fields) > 6 ? '…' : '')]) }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-5">
                                <i class="bi bi-shield-check fs-3 d-block mb-2"></i>
                                {{ array_filter($filters) ? __('Nothing matches your filters.') : __('Nothing recorded yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="card-body border-top pb-0">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
