{{-- EN / BM switch and the dark mode toggle, shown in the top bar and on the sign-in pages. --}}
<form method="POST" class="btn-group btn-group-sm" role="group" aria-label="{{ __('Language') }}">
    @csrf
    @foreach (\App\Http\Middleware\SetLocale::LOCALES as $locale => $language)
        <button
            type="submit"
            formaction="{{ route('locale.update', $locale) }}"
            @class(['btn', 'btn-primary' => app()->isLocale($locale), 'btn-outline-secondary' => ! app()->isLocale($locale)])
            @if (app()->isLocale($locale)) aria-pressed="true" @endif
            title="{{ $language }}"
            lang="{{ $locale }}"
        >{{ $locale === 'ms' ? 'BM' : 'EN' }}</button>
    @endforeach
</form>

<button
    type="button"
    class="btn btn-link text-secondary p-0 fs-5"
    x-data="themeToggle"
    x-on:click="toggle()"
    x-bind:aria-pressed="dark.toString()"
    x-bind:title="dark ? @js(__('Switch to light mode')) : @js(__('Switch to dark mode'))"
    aria-label="{{ __('Dark mode') }}"
>
    <i class="bi" x-bind:class="dark ? 'bi-sun' : 'bi-moon-stars'"></i>
</button>
