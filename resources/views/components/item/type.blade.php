@props(['type'])
<flux:badge size="sm" :icon="$type->icon()" :color="$type->color()" {{ $attributes }}>{{ $type->label() }}</flux:badge>
