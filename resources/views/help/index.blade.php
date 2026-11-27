<x-layouts.app :title="__('Tutorial')">
    <div class="page-header">
        <div>
            <h1>{{ __('Tutorial') }}</h1>
            <p class="text-secondary mb-0">{{ __('How to use :app, step by step. You only see the parts you have access to.', ['app' => config('app.name')]) }}</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-3 d-print-none">
            <nav class="card help-toc" aria-label="{{ __('Tutorial sections') }}">
                <div class="list-group list-group-flush">
                    @foreach ($sections as $section)
                        <a href="#{{ $section['key'] }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 small">
                            <i class="bi {{ $section['icon'] }} text-secondary"></i>{{ $section['title'] }}
                        </a>
                    @endforeach
                </div>
            </nav>
        </div>

        <div class="col-lg-9">
            @foreach ($sections as $section)
                <section class="card help-section mb-4" id="{{ $section['key'] }}" aria-labelledby="{{ $section['key'] }}-title">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="bi {{ $section['icon'] }} text-primary"></i>
                        <h2 id="{{ $section['key'] }}-title">{{ $section['title'] }}</h2>
                    </div>
                    <div class="card-body">
                        <p class="text-secondary">{{ $section['intro'] }}</p>
                        <ol class="help-steps mb-0">
                            @foreach ($section['steps'] as $step)
                                <li>{{ $step }}</li>
                            @endforeach
                        </ol>
                        @isset($section['tip'])
                            <div class="alert alert-info small d-flex gap-2 mt-3 mb-0">
                                <i class="bi bi-lightbulb"></i><span>{{ $section['tip'] }}</span>
                            </div>
                        @endisset
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-layouts.app>
