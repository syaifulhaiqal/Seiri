<x-layouts.app :title="__('Edit :name', ['name' => $training->title])">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.trainings.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to training') }}
            </a>
            <h1 class="mt-2">{{ __('Edit training') }}</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    @include('admin.trainings.form')
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
