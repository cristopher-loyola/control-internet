@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-red-600 dark:focus:border-red-500 focus:ring-red-600 dark:focus:ring-red-500 rounded-md shadow-sm']) }}>
