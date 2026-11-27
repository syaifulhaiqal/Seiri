<x-layouts.app :title="$link->name">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.attendance.links.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to clock-in links') }}
            </a>
            <h1 class="mt-2">{{ $link->name }}</h1>
            <p class="text-secondary mb-0">
                @if ($link->isExpired())
                    {{ __('Ended :date. Set a new last day to use it again.', ['date' => $link->expires_on->translatedFormat('j M Y')]) }}
                @elseif ($link->expires_on)
                    {{ __('Works until :date.', ['date' => $link->expires_on->translatedFormat('j M Y')]) }}
                @else
                    {{ __('Works until you turn it off.') }}
                @endif
            </p>
        </div>
    </div>

    <div class="row g-4" x-data="confirmDelete">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h2>{{ __('Share with your staff') }}</h2>
                </div>
                <div class="card-body">
                    <div x-data="copyText">
                        <label for="link-url" class="form-label fw-medium">{{ __('Link') }}</label>
                        <div class="input-group">
                            <input type="text" id="link-url" class="form-control" value="{{ $link->url() }}" x-ref="text" readonly x-on:focus="$el.select()">
                            <button type="button" class="btn btn-primary" x-on:click="copy()">
                                <i class="bi me-1" x-bind:class="copied ? 'bi-check2' : 'bi-clipboard'"></i>
                                <span x-text="copied ? @js(__('Copied')) : @js(__('Copy'))">{{ __('Copy') }}</span>
                            </button>
                        </div>
                        <div class="form-text">{{ __('Send it in your staff group chat or by email. Everyone uses the same link; signing in tells us who it is.') }}</div>
                    </div>

                    <div class="text-center mt-4">
                        <div class="d-inline-block p-3 rounded-3 bg-white border">{!! $qr !!}</div>
                        <p class="small text-secondary mt-2 mb-0">{{ __('Or scan this code with a phone camera.') }}</p>
                    </div>
                </div>
                <div class="card-footer d-flex flex-wrap gap-2 align-items-center">
                    <form method="POST" action="{{ route('admin.attendance.links.renew', $link) }}" x-data="formSubmit" x-on:submit="submit()">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-outline-secondary" x-bind:disabled="submitting">
                            <i class="bi bi-arrow-repeat me-1"></i>{{ __('Get a new link') }}
                        </button>
                    </form>
                    <span class="small text-secondary">{{ __('If the link reached someone it should not, get a new one. The old one stops working.') }}</span>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h2>{{ __('Settings') }}</h2>
                </div>
                <div class="card-body">
                    @include('admin.clock-links.form')
                </div>
                <div class="card-footer">
                    <button type="button" class="btn btn-outline-danger" x-on:click="open(@js($link->name), @js(route('admin.attendance.links.destroy', $link)))">
                        <i class="bi bi-trash me-1"></i>{{ __('Turn off link') }}
                    </button>
                </div>
            </div>
        </div>

        <x-delete-modal :title="__('Turn off link?')" :button="__('Turn off link')">
            <strong x-text="name"></strong> {{ __('stops working at once. Attendance already recorded with it is kept.') }}
        </x-delete-modal>
    </div>
</x-layouts.app>
