<x-layouts.app :title="__('Clock-in links')">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.attendance.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to attendance') }}
            </a>
            <h1 class="mt-2">{{ __('Clock-in links') }}</h1>
            <p class="text-secondary mb-0">{{ __('One link for all staff, for work from home or work outside the office. They open it, sign in and clock in. You see how and where on the attendance board.') }}</p>
        </div>
    </div>

    <div class="row g-4" x-data="confirmDelete">
        <div class="col-lg-8">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">{{ __('Name') }}</th>
                                <th>{{ __('Last day') }}</th>
                                <th>{{ __('Location') }}</th>
                                <th class="text-end pe-3">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($links as $item)
                                <tr>
                                    <td class="ps-3">
                                        <a href="{{ route('admin.attendance.links.show', $item) }}" class="fw-medium text-decoration-none">{{ $item->name }}</a>
                                        <div class="small text-secondary">{{ __('Created :date by :name', ['date' => $item->created_at->translatedFormat('j M Y'), 'name' => $item->creator?->name ?? '—']) }}</div>
                                    </td>
                                    <td class="text-nowrap">
                                        @if ($item->isExpired())
                                            <span class="badge rounded-pill badge-soft badge-soft-secondary">{{ __('Ended :date', ['date' => $item->expires_on->translatedFormat('j M Y')]) }}</span>
                                        @else
                                            {{ $item->expires_on?->translatedFormat('j M Y') ?? __('No end') }}
                                        @endif
                                    </td>
                                    <td>{{ $item->require_location ? __('Required') : __('Optional') }}</td>
                                    <td class="text-end pe-3 text-nowrap">
                                        <a href="{{ route('admin.attendance.links.show', $item) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-qr-code me-1"></i>{{ __('Open') }}
                                        </a>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            title="{{ __('Turn off') }}"
                                            x-on:click="open(@js($item->name), @js(route('admin.attendance.links.destroy', $item)))"
                                        >
                                            <i class="bi bi-trash"></i><span class="visually-hidden">{{ __('Turn off') }}</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-secondary py-5">
                                        <i class="bi bi-link-45deg fs-3 d-block mb-2"></i>
                                        {{ __('No clock-in links yet. Create one to let staff clock in from home.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h2>{{ __('New link') }}</h2>
                </div>
                <div class="card-body">
                    @include('admin.clock-links.form')
                </div>
            </div>
        </div>

        <x-delete-modal :title="__('Turn off link?')" :button="__('Turn off link')">
            <strong x-text="name"></strong> {{ __('stops working at once. Attendance already recorded with it is kept.') }}
        </x-delete-modal>
    </div>
</x-layouts.app>
