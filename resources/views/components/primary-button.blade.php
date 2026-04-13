<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-cagsu-maroon border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-cagsu-orange focus:bg-cagsu-orange active:bg-cagsu-maroon focus:outline-none focus:ring-2 focus:ring-cagsu-yellow focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
