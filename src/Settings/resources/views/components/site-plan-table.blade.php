<div class="relative overflow-hidden rounded-lg shadow-sm p-6 mb-4 bg-white">
    @if(!empty($sitePlan))
        <table class="table-auto w-full text-sm border">
            <thead>
            <tr class="bg-gray-100">
                <th class="px-2 py-2 text-left">{{ __('SitePlan') }}</th>
                <th class="px-2 py-2 text-right">{{ __('Cost Per Credit') }}</th>
                <th class="px-2 py-2 text-right">{{ __('Alimtalk Credit Cost') }}</th>
                <th class="px-2 py-2 text-right">{{ __('SMS Credit Cost') }}</th>
                <th class="px-2 py-2 text-right">{{ __('LMS Credit Cost') }}</th>
                <th class="px-2 py-2 text-right">{{ __('MMS Credit Cost') }}</th>
            </tr>
            </thead>
            <tbody>
            <tr class="border-t">
                @foreach ($sitePlan as $key => $row)
                    @if($key==='title')
                        <td class="px-2 py-2 text-left">{{ $row }}</td>
                    @else
                        @if($key === 'cost_per_credit')
                            <td class="px-2 py-2 text-right">{{ $row }}원</td>
                        @else
                            <td class="px-2 py-2 text-right">©{{ $row }}</td>
                        @endif
                    @endif
                @endforeach
            </tr>
            </tbody>
        </table>
    @else
        현재 구매할 수 없습니다.
    @endif
</div>