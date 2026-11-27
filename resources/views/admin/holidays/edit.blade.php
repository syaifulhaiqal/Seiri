<x-layouts.app :title="__('Edit :name', ['name' => $holiday->name])">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.holidays.index', ['year' => $holiday->date->year]) }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to public holidays') }}
            </a>
            <h1 class="mt-2">{{ __('Edit holiday') }}</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    @include('admin.holidays.form')
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
