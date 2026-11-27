@props(['role'])

<span class="badge rounded-pill badge-soft badge-soft-{{ $role->context() }}">{{ $role->label() }}</span>
