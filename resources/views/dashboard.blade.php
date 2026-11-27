<x-layouts.app :title="__('Dashboard')">
    <div class="page-header">
        <div>
            <h1>{{ __('Hi, :name', ['name' => str($user->name)->before(' ')]) }}</h1>
            <p class="text-secondary mb-0">
                {{ $user->isAdmin() ? __('Here\'s an overview of your people and accounts.') : __('Welcome to your dashboard.') }}
            </p>
        </div>
        @if ($user->isAdmin())
            <a href="{{ route('admin.users.index') }}" class="btn btn-primary">
                <i class="bi bi-people me-1"></i>{{ __('Manage users') }}
            </a>
        @endif
    </div>

    @if ($hrStats)
        <div class="row g-3 mb-3">
            @foreach ([
                ['label' => __('Active employees'), 'value' => $hrStats['employees'], 'icon' => 'bi-person-badge', 'color' => 'primary', 'url' => route('admin.employees.index', ['status' => 'active'])],
                ['label' => __('Clocked in today'), 'value' => $hrStats['present'], 'icon' => 'bi-clock-history', 'color' => 'success', 'url' => route('admin.attendance.index')],
                ['label' => __('On leave today'), 'value' => $hrStats['on_leave'], 'icon' => 'bi-airplane', 'color' => 'info', 'url' => route('admin.attendance.index', ['status' => 'on_leave'])],
                ['label' => __('Leave to review'), 'value' => $hrStats['pending_leave'], 'icon' => 'bi-calendar2-check', 'color' => 'warning', 'url' => route('admin.leave_requests.index', ['status' => 'pending'])],
            ] as $card)
                <div class="col-sm-6 col-xl-3">
                    <a href="{{ $card['url'] }}" class="card stat-card h-100 text-decoration-none text-body">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="stat-icon badge-soft-{{ $card['color'] }}"><i class="bi {{ $card['icon'] }}"></i></span>
                            <div>
                                <div class="text-secondary small">{{ $card['label'] }}</div>
                                <div class="stat-value">{{ number_format($card['value']) }}</div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    @if ($stats)
        <div class="row g-3 mb-4">
            @foreach ([
                ['label' => __('Total users'), 'value' => $stats['total'], 'icon' => 'bi-people', 'color' => 'primary', 'query' => []],
                ['label' => __('Active'), 'value' => $stats['active'], 'icon' => 'bi-check-circle', 'color' => 'success', 'query' => ['status' => 'active']],
                ['label' => __('Pending email'), 'value' => $stats['inactive'], 'icon' => 'bi-hourglass-split', 'color' => 'warning', 'query' => ['status' => 'inactive']],
                ['label' => __('Admins'), 'value' => $stats['admins'], 'icon' => 'bi-shield-check', 'color' => 'secondary', 'query' => ['role' => 'admin']],
            ] as $card)
                <div class="col-sm-6 col-xl-3">
                    <a href="{{ route('admin.users.index', $card['query']) }}" class="card stat-card h-100 text-decoration-none text-body">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="stat-icon badge-soft-{{ $card['color'] }}"><i class="bi {{ $card['icon'] }}"></i></span>
                            <div>
                                <div class="text-secondary small">{{ $card['label'] }}</div>
                                <div class="stat-value">{{ number_format($card['value']) }}</div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h2>{{ __('Your account') }}</h2>
                    <a href="{{ route(request()->area() . 'profile.edit') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-pencil me-1"></i>{{ __('Edit') }}
                    </a>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <span class="avatar avatar-lg">{{ str($user->name)->substr(0, 1) }}</span>
                        <div class="min-w-0">
                            <div class="fw-semibold fs-5 text-truncate">{{ $user->name }}</div>
                            <x-role-badge :role="$user->role" />
                        </div>
                    </div>
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-secondary fw-normal">{{ __('Email') }}</dt>
                        <dd class="col-7 text-break">{{ $user->email }}</dd>

                        <dt class="col-5 text-secondary fw-normal">{{ __('Phone') }}</dt>
                        <dd class="col-7">{{ $user->phone_number ?: '—' }}</dd>

                        <dt class="col-5 text-secondary fw-normal">{{ __('Status') }}</dt>
                        <dd class="col-7"><x-status-badge :user="$user" /></dd>

                        <dt class="col-5 text-secondary fw-normal">{{ __('Member since') }}</dt>
                        <dd class="col-7 mb-0">{{ $user->created_at->format('j M Y') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            @if ($user->isAdmin())
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h2>{{ __('Leave waiting for review') }}</h2>
                        <a href="{{ route('admin.leave_requests.index', ['status' => 'pending']) }}" class="small">{{ __('View all') }}</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <tbody>
                                @forelse ($pendingLeave as $leave)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="avatar">{{ str($leave->employee->name)->substr(0, 1) }}</span>
                                                <div class="min-w-0">
                                                    <div class="fw-medium">{{ $leave->employee->name }}</div>
                                                    <div class="small text-secondary">{{ $leave->leaveType->name }} · @include('partials.leave-dates', ['leave' => $leave])</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="{{ route('admin.leave_requests.show', $leave) }}" class="btn btn-sm btn-primary">{{ __('Review') }}</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="text-center text-secondary py-4">{{ __('Nothing to review.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="card h-100">
                    <div class="card-header">
                        <h2>{{ __('Getting started') }}</h2>
                    </div>
                    <div class="list-group list-group-flush">
                        @if ($employee)
                            <a href="{{ route('attendance.index') }}" class="list-group-item list-group-item-action d-flex gap-3 py-3">
                                <i class="bi bi-clock-history fs-4 text-primary"></i>
                                <div>
                                    <div class="fw-medium">
                                        {{ match (true) {
                                            ! $todayAttendance?->clock_in_at => __('Clock in for today'),
                                            ! $todayAttendance->clock_out_at => __('Clock out when you finish'),
                                            default => __('You are done for today'),
                                        } }}
                                    </div>
                                    <div class="small text-secondary">
                                        {{ $todayAttendance?->clock_in_at
                                            ? __('Clocked in at :time.', ['time' => $todayAttendance->clock_in_at->format('g:i A')])
                                            : __('You have not clocked in yet.') }}
                                    </div>
                                </div>
                            </a>
                            <a href="{{ route('leave.index') }}" class="list-group-item list-group-item-action d-flex gap-3 py-3">
                                <i class="bi bi-calendar2-check fs-4 text-primary"></i>
                                <div>
                                    <div class="fw-medium">{{ __('Apply for leave') }}</div>
                                    <div class="small text-secondary">{{ __('See your balance and the status of your requests.') }}</div>
                                </div>
                            </a>
                        @endif
                        <a href="{{ route(request()->area() . 'profile.edit') }}" class="list-group-item list-group-item-action d-flex gap-3 py-3">
                            <i class="bi bi-person-gear fs-4 text-primary"></i>
                            <div>
                                <div class="fw-medium">{{ __('Complete your profile') }}</div>
                                <div class="small text-secondary">{{ __('Keep your name and phone number up to date.') }}</div>
                            </div>
                        </a>
                        <a href="{{ route(request()->area() . 'profile.edit') }}#change-password" class="list-group-item list-group-item-action d-flex gap-3 py-3">
                            <i class="bi bi-key fs-4 text-primary"></i>
                            <div>
                                <div class="fw-medium">{{ __('Change your password') }}</div>
                                <div class="small text-secondary">{{ __('Use a strong password you don\'t use anywhere else.') }}</div>
                            </div>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
