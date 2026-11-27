{{-- $training: a new or existing Training; $employees: id => label; $employeeId: the preselected employee --}}
<form
    method="POST"
    action="{{ $training->exists ? route('admin.trainings.update', $training) : route('admin.trainings.store') }}"
    x-data="formSubmit"
    x-on:submit="submit()"
    novalidate
>
    @csrf
    @if ($training->exists)
        @method('PUT')
    @endif

    <x-form.select name="employee_id" :label="__('Employee')" :options="$employees" :value="$employeeId" :placeholder="__('Choose an employee')" required />
    <x-form.input name="title" :label="__('Course')" :value="$training->title" required :placeholder="__('e.g. Occupational first aid')" />
    <x-form.input name="provider" :label="__('Provider')" :value="$training->provider" />
    <div class="row">
        <div class="col-sm-6">
            <x-form.input name="start_date" type="date" :label="__('From')" :value="$training->start_date?->format('Y-m-d')" required />
        </div>
        <div class="col-sm-6">
            <x-form.input name="end_date" type="date" :label="__('To')" :value="$training->end_date?->format('Y-m-d')" required />
        </div>
    </div>
    <div class="row">
        <div class="col-sm-4">
            <x-form.input name="hours" type="number" step="0.5" min="0" :label="__('Hours')" :value="$training->hours" />
        </div>
        <div class="col-sm-4">
            <x-form.input name="cost" type="number" step="0.01" min="0" :label="__('Cost (RM)')" :value="$training->cost" />
        </div>
        <div class="col-sm-4">
            <x-form.select name="status" :label="__('Status')" :options="\App\Enums\TrainingStatus::options()" :value="$training->status" required />
        </div>
    </div>
    <x-form.textarea name="notes" :label="__('Notes')" rows="2" :value="$training->notes" :help="__('e.g. HRDF claim number, certificate expiry.')" />

    <x-submit-button>{{ $training->exists ? __('Save changes') : __('Add training') }}</x-submit-button>
</form>
