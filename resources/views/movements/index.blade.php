<x-layouts.app :title="__('My movements')">
    <div class="page-header">
        <div>
            <h1>{{ __('My movements') }}</h1>
            <p class="text-secondary mb-0">{{ __('Log when you leave the office during work hours (client visit, bank, site), so HR knows where you are.') }}</p>
        </div>
    </div>

    @if (! $employee)
        @include('partials.no-employee-record')
    @else
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h2>{{ __('Going out') }}</h2>
                    </div>
                    <div class="card-body">
                        @if ($employee->isActive())
                            <form method="POST" action="{{ route('movements.store') }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                                @csrf

                                <x-form.input name="date" type="date" bag="movement" :label="__('Date')" :value="today()->toDateString()" required />
                                <div class="row">
                                    <div class="col-6">
                                        <x-form.input name="time_out" type="time" bag="movement" :label="__('Time out')" :value="now()->format('H:i')" required />
                                    </div>
                                    <div class="col-6">
                                        <x-form.input name="time_back" type="time" bag="movement" :label="__('Expected back')" :help="__('Leave empty and tap Back when you return.')" />
                                    </div>
                                </div>
                                <x-form.input name="destination" bag="movement" :label="__('Where')" :placeholder="__('e.g. Client office, Maybank Jalan Ampang')" required />
                                <x-form.textarea name="purpose" bag="movement" :label="__('Purpose')" rows="2" required />

                                <x-submit-button>{{ __('Log movement') }}</x-submit-button>
                            </form>
                        @else
                            <p class="text-secondary mb-0">{{ __('Your employment has ended, so you can no longer log movements.') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card" x-data="confirmDelete">
                    <div class="card-header">
                        <h2>{{ __('History') }}</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3">{{ __('Date') }}</th>
                                    <th>{{ __('Where') }}</th>
                                    <th>{{ __('Out – back') }}</th>
                                    <th class="text-end pe-3"><span class="visually-hidden">{{ __('Actions') }}</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($movements as $movement)
                                    <tr>
                                        <td class="ps-3 text-nowrap">{{ $movement->date->translatedFormat('D, j M Y') }}</td>
                                        <td>
                                            <div class="fw-medium">{{ $movement->destination }}</div>
                                            <div class="small text-secondary">{{ $movement->purpose }}</div>
                                        </td>
                                        <td class="text-nowrap">
                                            {{ $movement->formatTime($movement->time_out) }} – {{ $movement->formatTime($movement->time_back) }}
                                            @if ($movement->isOut() && $movement->date->isToday())
                                                <span class="badge rounded-pill badge-soft badge-soft-warning ms-1">{{ __('Out') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-3 text-nowrap">
                                            @if ($movement->isOut() && $movement->date->isToday())
                                                <form method="POST" action="{{ route('movements.back', $movement) }}" class="d-inline" x-data="formSubmit" x-on:submit="submit()">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-sm btn-primary" x-bind:disabled="submitting">{{ __('Back') }}</button>
                                                </form>
                                            @endif
                                            @unless ($movement->date->lt(today()))
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="{{ __('Delete') }}"
                                                    x-on:click="open(@js($movement->destination), @js(route('movements.destroy', $movement)))"
                                                >
                                                    <i class="bi bi-trash"></i><span class="visually-hidden">{{ __('Delete') }}</span>
                                                </button>
                                            @endunless
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-secondary py-5">
                                            <i class="bi bi-signpost fs-3 d-block mb-2"></i>
                                            {{ __('No movements logged yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($movements->hasPages())
                        <div class="card-body border-top pb-0">
                            {{ $movements->links() }}
                        </div>
                    @endif

                    <x-delete-modal :title="__('Delete movement?')" :button="__('Delete movement')">
                        {{ __('This permanently deletes your movement to') }} <strong x-text="name"></strong>.
                    </x-delete-modal>
                </div>
            </div>
        </div>
    @endif
</x-layouts.app>
