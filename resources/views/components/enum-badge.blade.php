@props(['value'])

{{-- Any enum with label() and context(), e.g. EmployeeStatus or LeaveStatus. --}}
<span {{ $attributes->class(['badge rounded-pill badge-soft badge-soft-' . $value->context()]) }}>{{ $value->label() }}</span>
