@props(['title' => null])

@php
    $authUser = auth()->user();
    $area = request()->area();
    $unreadCount = $authUser->unreadNotifications()->count();
    $latestNotifications = $authUser->notifications()->limit(5)->get();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title . ' · ' : '' }}{{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @include('partials.head-icons')

    @include('partials.theme-script')

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body x-data="{ collapsed: localStorage.getItem('sidebar-collapsed') === '1' }"
      x-init="$watch('collapsed', v => localStorage.setItem('sidebar-collapsed', v ? '1' : '0'))"
      :class="{ 'sidebar-collapsed': collapsed }">
<div class="d-flex">
    <aside class="offcanvas-lg offcanvas-start app-sidebar d-print-none" tabindex="-1" id="sidebar" aria-label="{{ __('Main navigation') }}">
        <div class="d-flex align-items-center justify-content-between p-3">
            <a class="brand" href="{{ route($area . 'dashboard') }}" title="{{ config('app.name') }}">
                <x-logo variant="compact" class="brand-logo" aria-hidden="true" />
                <span class="brand-wordmark nav-text">{{ config('app.name') }}</span>
            </a>
            <button type="button" class="btn-close d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="{{ __('Close') }}"></button>
        </div>

        <ul class="nav flex-column gap-1 px-3 app-sidebar-nav">
            <li class="nav-item">
                <a @class(['nav-link', 'active' => request()->routeIs($area . 'dashboard')]) href="{{ route($area . 'dashboard') }}" title="{{ __('Dashboard') }}">
                    <i class="bi bi-grid"></i><span class="nav-text">{{ __('Dashboard') }}</span>
                </a>
            </li>
            @if ($authUser->isAdmin())
                @php
                    // Admin menu: each link shows only when the admin's role has its permission.
                    $adminMenu = collect([
                        '' => ['label' => '', 'items' => [
                            ['route' => 'admin.users', 'label' => __('Users'), 'icon' => 'bi-people', 'can' => 'users.manage'],
                            ['route' => 'admin.roles', 'label' => __('Roles & permissions'), 'icon' => 'bi-key', 'can' => 'roles.manage'],
                        ]],
                        'hr' => ['label' => __('Human resources'), 'items' => [
                            ['route' => 'admin.employees', 'label' => __('Employees'), 'icon' => 'bi-person-badge', 'can' => 'employees.view'],
                            ['route' => 'admin.departments', 'label' => __('Departments'), 'icon' => 'bi-diagram-3', 'can' => 'employees.manage'],
                            ['route' => 'admin.leave_requests', 'label' => __('Leave requests'), 'icon' => 'bi-calendar2-check', 'can' => 'leave.review'],
                            ['route' => 'admin.attendance', 'label' => __('Attendance'), 'icon' => 'bi-clock-history', 'can' => 'attendance.view'],
                            ['route' => 'admin.movements', 'label' => __('Movements'), 'icon' => 'bi-signpost-split', 'can' => 'attendance.view'],
                            ['route' => 'admin.overtime', 'label' => __('Overtime'), 'icon' => 'bi-hourglass-split', 'can' => 'overtime.review'],
                            ['route' => 'admin.trainings', 'label' => __('Training'), 'icon' => 'bi-mortarboard', 'can' => 'training.manage'],
                            ['route' => 'admin.leave_types', 'label' => __('Leave types'), 'icon' => 'bi-sliders', 'can' => 'employees.manage'],
                            ['route' => 'admin.holidays', 'label' => __('Public holidays'), 'icon' => 'bi-calendar-event', 'can' => 'holidays.manage'],
                        ]],
                        'payroll' => ['label' => __('Payroll'), 'items' => [
                            ['route' => 'admin.payroll', 'label' => __('Payroll runs'), 'icon' => 'bi-wallet2', 'can' => 'payroll.view'],
                            ['route' => 'admin.ea_forms', 'label' => __('EA forms'), 'icon' => 'bi-file-earmark-text', 'can' => 'payroll.view'],
                            ['route' => 'admin.claims', 'label' => __('Expense claims'), 'icon' => 'bi-receipt-cutoff', 'can' => 'claims.view'],
                        ]],
                        'finance' => ['label' => __('Finance'), 'items' => [
                            ['route' => 'admin.invoices', 'label' => __('Invoices'), 'icon' => 'bi-file-earmark-text', 'can' => 'invoices.manage'],
                            ['route' => 'admin.bills', 'label' => __('Bills'), 'icon' => 'bi-file-earmark-minus', 'can' => 'invoices.manage'],
                            ['route' => 'admin.contacts', 'label' => __('Customers & suppliers'), 'icon' => 'bi-person-vcard', 'can' => 'invoices.manage'],
                            ['route' => 'admin.journal_entries', 'label' => __('Journal'), 'icon' => 'bi-journal-text', 'can' => 'ledger.manage'],
                            ['route' => 'admin.accounts', 'label' => __('Chart of accounts'), 'icon' => 'bi-list-columns', 'can' => 'ledger.manage'],
                            ['route' => 'admin.reports', 'index' => 'admin.reports.profit_and_loss', 'label' => __('Reports'), 'icon' => 'bi-bar-chart-line', 'can' => 'reports.view'],
                        ]],
                        'sales' => ['label' => __('Sales & CRM'), 'items' => [
                            ['route' => 'admin.leads', 'label' => __('Leads'), 'icon' => 'bi-funnel', 'can' => 'crm.manage'],
                            ['route' => 'admin.quotations', 'label' => __('Quotations'), 'icon' => 'bi-file-earmark-richtext', 'can' => 'crm.manage'],
                        ]],
                        'purchasing' => ['label' => __('Purchasing & stock'), 'items' => [
                            ['route' => 'admin.purchase_requests', 'label' => __('Purchase requests'), 'icon' => 'bi-cart-plus', 'can' => 'procurement.view'],
                            ['route' => 'admin.purchase_orders', 'label' => __('Purchase orders'), 'icon' => 'bi-truck', 'can' => 'procurement.manage'],
                            ['route' => 'admin.products', 'label' => __('Products & stock'), 'icon' => 'bi-box-seam', 'can' => 'inventory.manage'],
                        ]],
                        'audit' => ['label' => __('Audit'), 'items' => [
                            ['route' => 'admin.audit', 'label' => __('Audit trail'), 'icon' => 'bi-shield-check', 'can' => 'audit.view'],
                        ]],
                    ])->map(fn (array $group) => [...$group, 'items' => array_filter($group['items'], fn (array $item) => $authUser->can($item['can']))])
                        ->filter(fn (array $group) => $group['items'] !== []);
                @endphp

                @foreach ($adminMenu as $key => $group)
                    @if ($group['label'] === '')
                        @foreach ($group['items'] as $item)
                            @include('partials.nav-link', ['item' => $item])
                        @endforeach
                    @else
                        {{-- Sections fold away; the one holding the current page starts open. --}}
                        @php
                            $isCurrentGroup = collect($group['items'])->contains(fn (array $item) => request()->routeIs($item['route'] . '.*'));
                        @endphp
                        <li class="nav-group" x-data="navGroup(@js($key), @js($isCurrentGroup))">
                            <button type="button" class="nav-heading nav-group-toggle" x-on:click="toggle()" aria-expanded="{{ $isCurrentGroup ? 'true' : 'false' }}" x-bind:aria-expanded="open" aria-controls="nav-group-{{ $key }}">
                                <span class="nav-text">{{ $group['label'] }}</span>
                                <i class="bi bi-chevron-down nav-text nav-group-chevron" aria-hidden="true"></i>
                            </button>
                            <ul class="nav flex-column flex-nowrap gap-1 nav-group-items" id="nav-group-{{ $key }}" x-show="open" @style(['display: none' => ! $isCurrentGroup])>
                                @foreach ($group['items'] as $item)
                                    @include('partials.nav-link', ['item' => $item])
                                @endforeach
                            </ul>
                        </li>
                    @endif
                @endforeach
            @else
                <li class="nav-heading"><span class="nav-text">{{ __('My work') }}</span></li>
                @foreach ([
                    ['route' => 'leave', 'label' => __('My leave'), 'icon' => 'bi-calendar2-check'],
                    ['route' => 'attendance', 'label' => __('My attendance'), 'icon' => 'bi-clock-history'],
                    ['route' => 'movements', 'label' => __('My movements'), 'icon' => 'bi-signpost-split'],
                    ['route' => 'overtime', 'label' => __('My overtime'), 'icon' => 'bi-hourglass-split'],
                    ['route' => 'trainings', 'label' => __('My training'), 'icon' => 'bi-mortarboard'],
                    ['route' => 'payslips', 'label' => __('My payslips'), 'icon' => 'bi-wallet2'],
                    ['route' => 'claims', 'label' => __('My expense claims'), 'icon' => 'bi-receipt-cutoff'],
                ] as $item)
                    @include('partials.nav-link', ['item' => $item])
                @endforeach

                @if ($authUser->isApprover())
                    <li class="nav-heading"><span class="nav-text">{{ __('My team') }}</span></li>
                    <li class="nav-item">
                        <a @class(['nav-link', 'active' => request()->routeIs('approvals.*')]) href="{{ route('approvals.index') }}" title="{{ __('Approvals') }}">
                            <i class="bi bi-check2-square"></i><span class="nav-text">{{ __('Approvals') }}</span>
                        </a>
                    </li>
                @endif
            @endif

            <li class="nav-item mt-2 pt-2 border-top">
                <a @class(['nav-link', 'active' => request()->routeIs($area . 'help')]) href="{{ route($area . 'help') }}" title="{{ __('Tutorial') }}">
                    <i class="bi bi-question-circle"></i><span class="nav-text">{{ __('Tutorial') }}</span>
                </a>
            </li>
        </ul>
    </aside>

    <div class="flex-grow-1 d-flex flex-column min-vh-100" style="min-width: 0">
        <header class="app-topbar sticky-top d-print-none">
            <button class="btn btn-link text-secondary p-0 fs-4 d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="{{ __('Open navigation') }}">
                <i class="bi bi-list"></i>
            </button>
            <button class="btn btn-link text-secondary p-0 fs-4 d-none d-lg-inline" type="button" @click="collapsed = !collapsed" :aria-expanded="!collapsed" aria-controls="sidebar" aria-label="{{ __('Toggle sidebar') }}">
                <i class="bi bi-layout-sidebar"></i>
            </button>

            <div class="ms-auto d-flex align-items-center gap-3">
                @include('partials.preferences')

                <div class="dropdown">
                    <button class="btn btn-link text-secondary p-0 fs-5 position-relative" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="{{ trans_choice('Notifications, :count unread|Notifications, :count unread', $unreadCount, ['count' => $unreadCount]) }}">
                        <i class="bi bi-bell"></i>
                        @if ($unreadCount > 0)
                            <span class="notification-badge badge rounded-pill bg-danger">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-sm border p-0 notification-menu">
                        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                            <span class="fw-semibold small">{{ __('Notifications') }}</span>
                            @if ($unreadCount > 0)
                                <form method="POST" action="{{ route($area . 'notifications.read_all') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none">{{ __('Mark all as read') }}</button>
                                </form>
                            @endif
                        </div>
                        @forelse ($latestNotifications as $notification)
                            <div @class(['border-top' => ! $loop->first])>
                                @include('notifications.item')
                            </div>
                        @empty
                            <p class="text-secondary small text-center py-4 mb-0">{{ __('No notifications yet.') }}</p>
                        @endforelse
                        <a class="d-block text-center small py-2 border-top text-decoration-none" href="{{ route($area . 'notifications.index') }}">{{ __('See all notifications') }}</a>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="btn btn-link text-decoration-none text-body d-flex align-items-center gap-2 p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar">{{ str($authUser->name)->substr(0, 1) }}</span>
                        <span class="text-start lh-sm">
                            <span class="d-block fw-semibold small">{{ $authUser->name }}</span>
                            <span class="d-block text-secondary" style="font-size: .75rem">{{ $authUser->adminRole?->name ?? $authUser->role->label() }}</span>
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
            </div>
        </header>

        <main class="container py-4 py-md-5 flex-grow-1">
            <x-flash />

            {{ $slot }}
        </main>

        <footer class="app-footer d-print-none">
            <div class="container d-flex flex-wrap justify-content-between gap-2">
                @include('partials.footer-credit')
            </div>
        </footer>
    </div>
</div>
</body>
</html>
