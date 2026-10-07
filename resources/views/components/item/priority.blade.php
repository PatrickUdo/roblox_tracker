@props(['priority'])
<flux:badge size="sm" variant="pill" :color="$priority->color()" {{ $attributes }}>{{ $priority->label() }}</flux:badge>
