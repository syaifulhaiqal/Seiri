@props(['title' => null])

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
<body>
    <main class="auth-shell">
        <div class="auth-card">
            <div class="text-center mb-4">
                <a href="{{ route('home') }}" class="auth-brand">
                    <span class="brand-mark"><i class="bi bi-buildings"></i></span>
                    {{ config('app.name') }}
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
    </main>
</body>
</html>
