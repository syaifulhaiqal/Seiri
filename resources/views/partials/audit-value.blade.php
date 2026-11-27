{{-- One old or new value in the audit trail. --}}
@if ($value === null || $value === '')
    <span class="text-secondary">—</span>
@elseif (is_bool($value))
    {{ $value ? __('Yes') : __('No') }}
@elseif (is_array($value))
    <code class="small text-body">{{ json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code>
@else
    <span class="text-break">{{ $value }}</span>
@endif
