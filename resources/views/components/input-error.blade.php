@props(['messages'])

@if ($messages)
    @php
        $normalizedMessages = (array) $messages;
        $flattenedMessages = collect($normalizedMessages)
            ->flatten()
            ->filter(fn ($message) => is_string($message) && $message !== '')
            ->values()
            ->all();
    @endphp
    <ul {{ $attributes->merge(['class' => 'text-sm text-red-600 space-y-1']) }}>
        @foreach ($flattenedMessages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
