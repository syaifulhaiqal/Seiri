<x-layouts.app :title="__('Attendance')">
    @if ($date->isToday())
        {{-- Today's board refreshes itself, so HR can keep it open to see who is in. --}}
        <div x-data x-init="setTimeout(() => window.location.reload(), 60000)" hidden></div>
    @endif

    <div class="page-header">
        <div>
            <h1>{{ __('Attendance') }}</h1>
            <p class="text-secondary mb-0">
                {{ $date->isToday() ? __('Today, :date', ['date' => $date->translatedFormat('l j M Y')]) : $date->translatedFormat('l, j M Y') }}
                · {{ __('Late after :time', ['time' => \Illuminate\Support\Carbon::parse(config('hr.work_start'))->format('g:i A')]) }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.attendance.qr') }}" class="btn btn-primary">
                <i class="bi bi-qr-code me-1"></i>{{ __('Office QR screen') }}
            </a>
            @can('employees.manage')
                <a href="{{ route('admin.attendance.links.index') }}" class="btn btn-outline-primary">
                    <i class="bi bi-link-45deg me-1"></i>{{ __('Clock-in links') }}
                </a>
            @endcan
            <a href="{{ route('admin.attendance.index', [...$filters, 'date' => $date->copy()->subDay()->toDateString()]) }}" class="btn btn-outline-secondary" title="{{ __('Previous day') }}">
                <i class="bi bi-chevron-left"></i><span class="visually-hidden">{{ __('Previous day') }}</span>
            </a>
            @unless ($date->isToday())
                <a href="{{ route('admin.attendance.index', [...$filters, 'date' => null]) }}" class="btn btn-outline-secondary">{{ __('Today') }}</a>
            @endunless
            <a href="{{ route('admin.attendance.index', [...$filters, 'date' => $date->copy()->addDay()->toDateString()]) }}" class="btn btn-outline-secondary" title="{{ __('Next day') }}">
                <i class="bi bi-chevron-right"></i><span class="visually-hidden">{{ __('Next day') }}</span>
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ($counts as $value => $count)
            @php
                $status = \App\Enums\AttendanceStatus::from($value);
            @endphp
            <div class="col-6 col-xl-3">
                <a href="{{ route('admin.attendance.index', [...$filters, 'date' => $date->toDateString(), 'status' => $status->value]) }}" class="card stat-card h-100 text-decoration-none text-body">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="stat-icon badge-soft-{{ $status->context() }}">
                            <i @class(['bi', match ($status) {
                                \App\Enums\AttendanceStatus::Present => 'bi-check-circle',
                                \App\Enums\AttendanceStatus::Late => 'bi-alarm',
                                \App\Enums\AttendanceStatus::OnLeave => 'bi-airplane',
                                \App\Enums\AttendanceStatus::Holiday => 'bi-calendar-event',
                                \App\Enums\AttendanceStatus::Absent => 'bi-x-circle',
                            }])></i>
                        </span>
                        <div>
                            <div class="text-secondary small">{{ $status->label() }}</div>
                            <div class="stat-value">{{ number_format($count) }}</div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.attendance.index') }}" class="row g-2 align-items-center" x-data="autoSubmit">
                <div class="col-6 col-md-3">
                    <input type="date" name="date" class="form-control" value="{{ $date->toDateString() }}" aria-label="{{ __('Date') }}" x-on:change="submit()">
                </div>
                <div class="col-6 col-md-3">
                    <select name="department" class="form-select" aria-label="{{ __('Department') }}" x-on:change="submit()">
                        <option value="">{{ __('All departments') }}</option>
                        @foreach ($departments as $id => $name)
                            <option value="{{ $id }}" @selected((string) ($filters['department'] ?? '') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select name="status" class="form-select" aria-label="{{ __('Status') }}" x-on:change="submit()">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach (\App\Enums\AttendanceStatus::options() as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="method" class="form-select" aria-label="{{ __('How') }}" x-on:change="submit()">
                        <option value="">{{ __('Office and remote') }}</option>
                        @foreach (\App\Enums\ClockMethod::cases() as $method)
                            <option value="{{ $method->value }}" @selected(($filters['method'] ?? '') === $method->value)>{{ $method->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-1 d-flex gap-2 justify-content-md-end">
                    @if (array_filter($filters))
                        <a href="{{ route('admin.attendance.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">{{ __('Employee') }}</th>
                        <th>{{ __('Department') }}</th>
                        <th>{{ __('Clock in') }}</th>
                        <th>{{ __('Clock out') }}</th>
                        <th>{{ __('Hours') }}</th>
                        <th>{{ __('How & where') }}</th>
                        <th class="pe-3">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="ps-3">
                                <a href="{{ route('admin.employees.show', $row['employee']) }}" class="d-flex align-items-center gap-2 text-decoration-none text-body">
                                    <span class="avatar">{{ str($row['employee']->name)->substr(0, 1) }}</span>
                                    <div class="min-w-0">
                                        <div class="fw-medium">{{ $row['employee']->name }}</div>
                                        <div class="small text-secondary">{{ $row['employee']->employee_no }}</div>
                                    </div>
                                </a>
                            </td>
                            <td>{{ $row['employee']->department?->name ?? '—' }}</td>
                            <td>{{ $row['attendance']?->clock_in_at?->format('g:i A') ?? '—' }}</td>
                            <td>{{ $row['attendance']?->clock_out_at?->format('g:i A') ?? '—' }}</td>
                            <td>{{ $row['attendance']?->workedHours() ?? '—' }}</td>
                            <td class="small">
                                @include('partials.clock-where', ['attendance' => $row['attendance']])
                            </td>
                            <td class="pe-3">
                                @if ($row['status'])
                                    <x-enum-badge :value="$row['status']" />
                                @else
                                    <span class="text-secondary">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                {{ __('No employees match your filters.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
