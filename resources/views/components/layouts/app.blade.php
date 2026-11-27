@props(['title' => null])

@php
    $authUser = auth()->user();
    $area = request()->area();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title . ' · ' : '' }}{{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body x-data="{ collapsed: localStorage.getItem('sidebar-collapsed') === '1' }"
      x-init="$watch('collapsed', v => localStorage.setItem('sidebar-collapsed', v ? '1' : '0'))"
      :class="{ 'sidebar-collapsed': collapsed }">
<div class="d-flex">
    <aside class="offcanvas-lg offcanvas-start app-sidebar d-print-none" tabindex="-1" id="sidebar" aria-label="{{ __('Main navigation') }}">
        <div class="d-flex align-items-center justify-content-between p-3">
            <a class="auth-brand fs-6" href="{{ route($area . 'dashboard') }}">
                <span class="brand-mark"><i class="bi bi-buildings"></i></span>
                <span class="nav-text">{{ config('app.name') }}</span>
            </a>
            <button type="button" class="btn-close d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="{{ __('Close') }}"></button>
        </div>

        <ul class="nav flex-column gap-1 px-3">
            <li class="nav-item">
                <a @class(['nav-link', 'active' => request()->routeIs($area . 'dashboard')]) href="{{ route($area . 'dashboard') }}" title="{{ __('Dashboard') }}">
                    <i class="bi bi-grid"></i><span class="nav-text">{{ __('Dashboard') }}</span>
                </a>
            </li>
            @if ($authUser->isAdmin())
                <li class="nav-item">
                    <a @class(['nav-link', 'active' => request()->routeIs('admin.users.*')]) href="{{ route('admin.users.index') }}" title="{{ __('Users') }}">
                        <i class="bi bi-people"></i><span class="nav-text">{{ __('Users') }}</span>
                    </a>
                </li>

                <li class="nav-heading"><span class="nav-text">{{ __('Human resources') }}</span></li>
                @foreach ([
                    ['route' => 'admin.employees', 'label' => __('Employees'), 'icon' => 'bi-person-badge'],
                    ['route' => 'admin.departments', 'label' => __('Departments'), 'icon' => 'bi-diagram-3'],
                    ['route' => 'admin.leave_requests', 'label' => __('Leave requests'), 'icon' => 'bi-calendar2-check'],
                    ['route' => 'admin.attendance', 'label' => __('Attendance'), 'icon' => 'bi-clock-history'],
                    ['route' => 'admin.leave_types', 'label' => __('Leave types'), 'icon' => 'bi-sliders'],
                ] as $item)
                    <li class="nav-item">
                        <a @class(['nav-link', 'active' => request()->routeIs($item['active'] ?? $item['route'] . '.*')]) href="{{ route($item['route'] . '.index') }}" title="{{ $item['label'] }}">
                            <i class="bi {{ $item['icon'] }}"></i><span class="nav-text">{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach

                <li class="nav-heading"><span class="nav-text">{{ __('Payroll') }}</span></li>
                @foreach ([
                    ['route' => 'admin.payroll', 'label' => __('Payroll runs'), 'icon' => 'bi-wallet2'],
                    ['route' => 'admin.claims', 'label' => __('Expense claims'), 'icon' => 'bi-receipt-cutoff'],
                ] as $item)
                    <li class="nav-item">
                        <a @class(['nav-link', 'active' => request()->routeIs($item['active'] ?? $item['route'] . '.*')]) href="{{ route($item['route'] . '.index') }}" title="{{ $item['label'] }}">
                            <i class="bi {{ $item['icon'] }}"></i><span class="nav-text">{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach

                <li class="nav-heading"><span class="nav-text">{{ __('Finance') }}</span></li>
                @foreach ([
                    ['route' => 'admin.invoices', 'label' => __('Invoices'), 'icon' => 'bi-file-earmark-text'],
                    ['route' => 'admin.bills', 'label' => __('Bills'), 'icon' => 'bi-file-earmark-minus'],
                    ['route' => 'admin.contacts', 'label' => __('Customers & suppliers'), 'icon' => 'bi-person-vcard'],
                    ['route' => 'admin.journal_entries', 'label' => __('Journal'), 'icon' => 'bi-journal-text'],
                    ['route' => 'admin.accounts', 'label' => __('Chart of accounts'), 'icon' => 'bi-list-columns'],
                ] as $item)
                    <li class="nav-item">
                        <a @class(['nav-link', 'active' => request()->routeIs($item['active'] ?? $item['route'] . '.*')]) href="{{ route($item['route'] . '.index') }}" title="{{ $item['label'] }}">
                            <i class="bi {{ $item['icon'] }}"></i><span class="nav-text">{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
                <li class="nav-item">
                    <a @class(['nav-link', 'active' => request()->routeIs('admin.reports.*')]) href="{{ route('admin.reports.profit_and_loss') }}" title="{{ __('Reports') }}">
                        <i class="bi bi-bar-chart-line"></i><span class="nav-text">{{ __('Reports') }}</span>
                    </a>
                </li>

                <li class="nav-heading"><span class="nav-text">{{ __('Audit') }}</span></li>
                <li class="nav-item">
                    <a @class(['nav-link', 'active' => request()->routeIs('admin.audit.*')]) href="{{ route('admin.audit.index') }}" title="{{ __('Audit trail') }}">
                        <i class="bi bi-shield-check"></i><span class="nav-text">{{ __('Audit trail') }}</span>
                    </a>
                </li>
            @else
                <li class="nav-heading"><span class="nav-text">{{ __('My work') }}</span></li>
                @foreach ([
                    ['route' => 'leave', 'label' => __('My leave'), 'icon' => 'bi-calendar2-check'],
                    ['route' => 'attendance', 'label' => __('My attendance'), 'icon' => 'bi-clock-history'],
                    ['route' => 'payslips', 'label' => __('My payslips'), 'icon' => 'bi-wallet2'],
                    ['route' => 'claims', 'label' => __('My expense claims'), 'icon' => 'bi-receipt-cutoff'],
                ] as $item)
                    <li class="nav-item">
                        <a @class(['nav-link', 'active' => request()->routeIs($item['route'] . '.*')]) href="{{ route($item['route'] . '.index') }}" title="{{ $item['label'] }}">
                            <i class="bi {{ $item['icon'] }}"></i><span class="nav-text">{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            @endif
        </ul>
    </aside>

    <div class="flex-grow-1" style="min-width: 0">
        <header class="app-topbar sticky-top d-print-none">
            <button class="btn btn-link text-secondary p-0 fs-4 d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="{{ __('Open navigation') }}">
                <i class="bi bi-list"></i>
            </button>
            <button class="btn btn-link text-secondary p-0 fs-4 d-none d-lg-inline" type="button" @click="collapsed = !collapsed" :aria-expanded="!collapsed" aria-controls="sidebar" aria-label="{{ __('Toggle sidebar') }}">
                <i class="bi bi-layout-sidebar"></i>
            </button>

            <div class="dropdown ms-auto">
                <button class="btn btn-link text-decoration-none text-body d-flex align-items-center gap-2 p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="avatar">{{ str($authUser->name)->substr(0, 1) }}</span>
                    <span class="text-start lh-sm">
                        <span class="d-block fw-semibold small">{{ $authUser->name }}</span>
                        <span class="d-block text-secondary" style="font-size: .75rem">{{ $authUser->role->label() }}</span>
                    </span>
                    <i class="bi bi-chevron-down small text-secondary"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border">
                    <li class="px-3 py-2 small text-secondary text-truncate" style="max-width: 16rem">{{ $authUser->email }}</li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="{{ route($area . 'profile.edit') }}"><i class="bi bi-person me-2"></i>{{ __('Profile') }}</a>
                    </li>
                    <li>
                        <form method="POST" action="{{ route($area . 'logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>{{ __('Log out') }}</button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>

        <main class="container py-4 py-md-5">
            <x-flash />

            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
