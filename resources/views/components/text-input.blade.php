@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-cagsu-maroon focus:ring-cagsu-maroon rounded-md shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-cagsu-yellow dark:focus:ring-cagsu-yellow']) }}>
