<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Credit Payment') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    {{ __('Credit Payment') }}
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    {{ __('Complete your credit purchase') }}
                </p>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-gray-600">{{ __('Purchase Amount') }}:</span>
                        <span class="font-semibold">{{ number_format($siteCredit->purchase_amount) }}{{ __('won') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">{{ __('Credits Amount') }}:</span>
                        <span class="font-semibold">{{ number_format($siteCredit->credits_amount) }}{{ __('credits') }}</span>
                    </div>

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
        </div>
    </div>

    <script>
        function processPayment() {
            // 여기에 실제 결제 처리 로직을 구현
            alert('{{ __("Payment processing will be implemented here") }}');

            // 결제 완료 후 부모 창에 메시지 전송하고 창 닫기
            if (window.opener) {
                window.opener.postMessage({
                    type: 'payment_completed',
                    success: true,
                    siteCredit: @json($siteCredit)
                }, '*');
            }
            window.close();
        }
    </script>
</body>

</html>