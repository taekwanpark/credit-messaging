<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Credit Payment') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://js.tosspayments.com/v2/standard"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>

<body class="bg-gray-100">

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                {{ __('Site Credit Payment') }}
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                @if(session()->has('site-credit-redirect-url'))
                    {{__('Completed Payment')}}
                @else
                    {{ __('Complete your credit purchase') }}
                @endif
            </p>
        </div>

        @if(session()->has('site-credit-redirect-url'))
            {{__('Close window in 3 seconds...')}}
        @else
            @if(session()->has('site-credit-data'))
                @php
                    $data = session('site-credit-data');
                    session()->put('site-credit-data', $data);
                @endphp
                @if(isset($data['purchase_amount']) && isset($data['credits_amount']))
                    <div class="bg-white rounded-lg shadow-md p-6">

                        <div class="space-y-4">
                            <div class="flex justify-between">
                                <span class="text-gray-600">{{ __('Purchase Amount') }}:</span>
                                <span class="font-semibold">{{ number_format($data['purchase_amount']) }}{{ __('won') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">{{ __('Credits Amount') }}:</span>
                                <span class="font-semibold">{{ number_format($data['credits_amount']) }}{{ __('credits') }}</span>
                            </div>

                            <hr class="my-4">
                            <div id="payment-widget"></div>

                            <hr class="my-4">
                            <div class="space-y-4">
                                <button type="button"
                                        onclick="processPayment()"
                                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('Proceed to Payment') }}
                                </button>
                                <button type="button"
                                        onclick="window.close()"
                                        class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('Cancel') }}
                                </button>
                            </div>
                        </div>

                    </div>
                @else
                    크레딧 정보 없음ㅋㅋㅋ
                    취소 후 다시 결제를 시도해주세요.
                    <button type="button"
                            onclick="window.close()"
                            class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('Cancel') }}
                    </button>
                @endif
            @else
                크레딧 정보 없음
                취소 후 다시 결제를 시도해주세요.
                <button type="button"
                        onclick="window.close()"
                        class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    {{ __('Cancel') }}
                </button>
            @endif
        @endif
    </div>
</div>

<script>

    const done = '{{session('site-credit-redirect-url')}}';
    if (done) {
        // 결제 완료 후 부모 창에 메시지 전송하고 창 닫기
        if (window.opener) {
            window.opener.postMessage({
                type: 'payment_completed',
                success: true,
                redirectUrl: '{{session('site-credit-redirect-url')}}',
            }, '*');
        }
        setTimeout(() => {
            window.close();
        }, 3000);
    } else {
        const data = @json(session('site-credit-data'));
        if (data) {
            if (Object.keys(data).includes('purchase_amount') && Object.keys(data).includes('credits_amount')) {
                const customerKey = '{{ auth()->user()?->email }}'; // 고유한 고객 식별자
                const tossPayments = window.TossPayments('{{config('toss-payments.client_key')}}'); // 결제위젯 연동 키

                const widgets = tossPayments.widgets({customerKey});
                widgets.setAmount({
                    currency: 'KRW',
                    value: Number(data['purchase_amount']),
                });
                // 결제 위젯 렌더링
                widgets.renderPaymentMethods({
                    selector: '#payment-widget',
                    variantKey: 'DEFAULT_KEY',
                });

                function processPayment() {
                    // 사이트 크레딧 생성
                    axios.post('{{ route('site-credit.store') }}', {}).then(response => {
                        // 사이트 크레딧 생성 성공
                        const orderId = response?.data?.orderId;
                        const destroyReplaceUrl = '{{ route('site-credit.destroy',['orderId'=>'__ORDER_ID__']) }}';
                        const destroyUrl = destroyReplaceUrl.replace('__ORDER_ID__', orderId);

                        if (orderId) {
                            widgets.requestPayment({
                                orderId: orderId,
                                orderName: '사이트 크레딧',
                                successUrl: '{{ route('site-credit.payments.success') }}',
                                failUrl: '{{ route('site-credit.payments.fail') }}',
                            }).then((response) => {

                                // 결제 실패 - 리턴
                                if (response?.data?.status !== 1) {
                                    // order 삭제
                                    axios.delete(destroyUrl, {}).then(response => {
                                        alert(response?.data?.message ?? '결제 중 에러가 발생하였습니다. 창을 닫고 다시 시도해주세요.');
                                    });
                                }
                            }).catch((error) => {
                                // 결제 실패 - 서버 에러
                                // order 삭제
                                axios.delete(destroyUrl);
                            });
                        } else {
                            // orderId 없음
                            console.log(1);
                            alert('결제 중 에러가 발생하였습니다. 창을 닫고 다시 시도해주세요.');
                        }
                    }).catch(error => {
                        // 크레딧 생성 실패
                        console.log(0);
                        alert(error?.response?.data?.message ?? '결제 중 에러가 발생하였습니다. 창을 닫고 다시 시도해주세요.');
                    });
                }
            }
        }
    }

</script>
</body>

</html>