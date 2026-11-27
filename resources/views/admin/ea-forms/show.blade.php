<x-layouts.app :title="__('EA form :year', ['year' => $statement['year']])">
    <div class="page-header d-print-none">
        <div>
            <a href="{{ route('admin.ea_forms.index', ['year' => $statement['year']]) }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to EA forms') }}
            </a>
            <h1 class="mt-2">{{ __('EA form :year', ['year' => $statement['year']]) }} · {{ $statement['employee']->name }}</h1>
        </div>
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>{{ __('Print') }}
        </button>
    </div>

    <div class="row">
        <div class="col-xl-9">
            @include('partials.ea-sheet')
        </div>
    </div>
</x-layouts.app>
