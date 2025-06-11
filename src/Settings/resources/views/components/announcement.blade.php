@props([
    'title',
    'messages',
    'alert' => false
])
<div class="relative overflow-hidden rounded-lg shadow-sm p-6 mb-4
            {{ $alert ? 'bg-gradient-to-r  from-red-50 to-orange-50 border-red-400' : 'bg-blue-50'}}">
    <div class="text-muted">
        @isset($title)
            <h3 class="text-lg font-semibold mb-2 {{$alert ? 'text-red-800' : 'text-gray-800'}}">[{{$title}}]</h3>

        @endisset
        <ul>
            @isset($messages)
                @foreach ($messages as $message)
                    <li><p class="leading-relaxed {{ $alert ? 'text-red-700' : 'text-gray-700' }}">{!! $message  !!}</p></li>
                @endforeach
            @endisset
        </ul>
    </div>
</div>