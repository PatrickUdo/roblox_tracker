@props(['label'])
<span {{ $attributes->class('inline-flex items-center gap-1.5 rounded-md border border-zinc-200 px-1.5 py-0.5 text-xs text-zinc-600 dark:border-zinc-700 dark:text-zinc-300') }}>
    <span class="size-2 rounded-full" style="background-color: {{ $label->color }}"></span>
    {{ $label->name }}
</span>
