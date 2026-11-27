<x-layouts.app :title="__('Public holidays')">
    <div class="page-header">
        <div>
            <h1>{{ __('Public holidays :year', ['year' => $year]) }}</h1>
            <p class="text-secondary mb-0">{{ __('Holidays are not working days: leave does not deduct them, payroll does not count them, and nobody is marked absent.') }}</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.holidays.index', ['year' => $year - 1]) }}" class="btn btn-outline-secondary" title="{{ __('Previous year') }}"><i class="bi bi-chevron-left"></i><span class="visually-hidden">{{ __('Previous year') }}</span></a>
            <a href="{{ route('admin.holidays.index', ['year' => $year + 1]) }}" class="btn btn-outline-secondary" title="{{ __('Next year') }}"><i class="bi bi-chevron-right"></i><span class="visually-hidden">{{ __('Next year') }}</span></a>
        </div>
    </div>

    <div class="row g-4" x-data="confirmDelete">
        <div class="col-lg-8">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">{{ __('Date') }}</th>
                                <th>{{ __('Holiday') }}</th>
                                <th class="text-end pe-3">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($holidays as $item)
                                <tr>
                                    <td class="ps-3 text-nowrap">
                                        {{ $item->date->translatedFormat('D, j M Y') }}
                                        @if ($item->date->isWeekend())
                                            <span class="badge rounded-pill badge-soft badge-soft-secondary ms-1">{{ __('Weekend') }}</span>
                                        @endif
                                    </td>
                                    <td class="fw-medium">{{ $item->name }}</td>
                                    <td class="text-end pe-3 text-nowrap">
                                        <a href="{{ route('admin.holidays.edit', $item) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                                            <i class="bi bi-pencil"></i><span class="visually-hidden">{{ __('Edit') }}</span>
                                        </a>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            title="{{ __('Delete') }}"
                                            x-on:click="open(@js($item->name), @js(route('admin.holidays.destroy', $item)))"
                                        >
                                            <i class="bi bi-trash"></i><span class="visually-hidden">{{ __('Delete') }}</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-secondary py-5">
                                        <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
                                        {{ __('No public holidays for :year yet.', ['year' => $year]) }}
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
                    <h2>{{ __('Add holiday') }}</h2>
                </div>
                <div class="card-body">
                    @include('admin.holidays.form')
                    <p class="small text-secondary mt-3 mb-0">{{ __('Dates of Hari Raya, Chinese New Year, Deepavali and other moving holidays change every year. Add them from the official gazette, including your state holidays.') }}</p>
                </div>
            </div>
        </div>

        <x-delete-modal :title="__('Delete holiday?')" :button="__('Delete holiday')">
            {{ __('This permanently deletes') }} <strong x-text="name"></strong>. {{ __('Leave already booked keeps its day count.') }}
        </x-delete-modal>
    </div>
</x-layouts.app>
