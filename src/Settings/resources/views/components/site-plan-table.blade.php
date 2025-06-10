<table class="table-auto w-full text-sm text-left">
    <thead>
    <tr class="bg-gray-100">
        <th class="px-2 py-1 text-left">{{ __('Title') }}</th>
        <th class="px-2 py-1 text-right">{{ __('Cost Per Credit') }}</th>
        <th class="px-2 py-1 text-right">{{ __('Alimtalk Credit Cost') }}</th>
        <th class="px-2 py-1 text-right">{{ __('SMS Credit Cost') }}</th>
        <th class="px-2 py-1 text-right">{{ __('LMS Credit Cost') }}</th>
        <th class="px-2 py-1 text-right">{{ __('MMS Credit Cost') }}</th>
    </tr>
    </thead>
    <tbody>
    <tr class="border-t">
        @foreach ($options as $key => $row)
            @if($key==='title')
                <td class="px-2 py-1 text-left">{{ $row }}</td>
            @else
                @if($key === 'cost_per_credit')
                    <td class="px-2 py-1 text-right">{{ $row }}원</td>
                @else
                    <td class="px-2 py-1 text-right">©{{ $row }}</td>
                @endif
            @endif
        @endforeach
    </tr>
    </tbody>
</table>
