{{-- $item: ['route' => route-name prefix, 'index' => optional route name to open, 'label', 'icon'] --}}
<li class="nav-item">
    <a @class(['nav-link', 'active' => request()->routeIs($item['route'] . '.*')]) href="{{ route($item['index'] ?? $item['route'] . '.index') }}" title="{{ $item['label'] }}">
        <i class="bi {{ $item['icon'] }}"></i><span class="nav-text">{{ $item['label'] }}</span>
    </a>
</li>
