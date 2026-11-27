@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title . ' · ' : '' }}{{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|unbounded:600&display=swap" rel="stylesheet">
    @include('partials.head-icons')

    @include('partials.theme-script')

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div class="auth-preferences d-flex align-items-center gap-3">
        @include('partials.preferences')
    </div>

    <main class="auth-shell">
        <div class="auth-layout">
            {{-- Brand panel (wide screens): the full logo, like the printed brand sheet. --}}
            <aside class="auth-brand-panel d-none d-lg-flex">
                <a href="{{ route('home') }}" class="text-reset" title="{{ config('app.name') }}">
                    <x-logo class="auth-logo" />
                </a>
                {{-- Drawn on the logo's 331-unit width and letter-spaced to fill it exactly, so it lines up with the logo. --}}
                <svg class="brand-tagline" viewBox="0 0 331 15" role="img" aria-label="Everything in its place.">
                    <text x="0" y="14" textLength="331" lengthAdjust="spacing">EVERYTHING IN ITS PLACE.</text>
                </svg>
            </aside>

            <div class="auth-card">
                <div class="text-center mb-4 d-lg-none">
                    <a href="{{ route('home') }}" class="brand" title="{{ config('app.name') }}">
                        <x-logo variant="compact" class="brand-logo brand-logo-lg" aria-hidden="true" />
                        <span class="brand-wordmark brand-wordmark-lg">{{ config('app.name') }}</span>
                    </a>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-sm-5">
                        {{ $slot }}
                    </div>
                </div>

                @isset($footer)
                    <div class="text-center text-secondary small mt-4">
                        {{ $footer }}
                    </div>
                @endisset
            </div>
        </div>

        <footer class="auth-footer">
            @include('partials.footer-credit')
        </footer>
    </main>
</body>
</html>
