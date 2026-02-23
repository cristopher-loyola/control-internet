@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 text-sm font-medium leading-5 text-white no-underline hover:no-underline focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 text-sm font-medium leading-5 text-white/80 hover:text-white no-underline hover:no-underline focus:outline-none focus:text-white transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} style="text-decoration: none;">
    {{ $slot }}
</a>
