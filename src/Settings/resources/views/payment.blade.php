<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Credit Payment') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://js.tosspayments.com/v2/standard"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        .loading-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <div class="min-h-screen flex items-center justify-center py-6 px-4 sm:px-6">
        <div class="max-w-lg w-full space-y-6 fade-in">

            <!-- 헤더 섹션 -->
            <div class="text-center">
                <div class="mx-auto h-12 w-12 bg-gray-900 rounded flex items-center justify-center mb-4">
                    <i class="fas fa-coins text-white text-lg"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">
                    {{ __('Site Credit Payment') }}
                </h2>
                <p class="text-sm text-gray-600">
                    @if(session()->has('site-credit-redirect-url'))
                    <span class="inline-flex items-center text-green-700">
                        <i class="fas fa-check-circle mr-1 text-xs"></i>
                        {{ __('Payment has been completed successfully') }}
                    </span>
                    @else
                    {{ __('Purchase credits to use various services on the site') }}
                    @endif
                </p>
            </div>

            @if(session()->has('site-credit-redirect-url'))
            <!-- 결제 완료 상태 -->
            <div class="bg-white rounded border border-green-200 p-6 text-center">
                <div class="mx-auto h-16 w-16 bg-green-50 rounded flex items-center justify-center mb-4">
                    <i class="fas fa-check text-green-700 text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">{{ __('Payment Completed') }}</h3>
                <p class="text-sm text-gray-600 mb-4">{{ __('The window will close automatically in 3 seconds') }}</p>
                <div class="flex justify-center">
                    <div class="loading-spinner h-4 w-4 border-2 border-gray-200 border-t-gray-900 rounded-full"></div>
                </div>
            </div>
            @else
            @if(session()->has('site-credit-data'))
            @php
            $data = session('site-credit-data');
            session()->put('site-credit-data', $data);
            @endphp
            @if(isset($data['purchase_amount']) && isset($data['credits_amount']))
            <!-- 결제 정보 및 위젯 -->
            <div class="bg-white rounded border border-gray-200 overflow-hidden">

                <!-- 결제 정보 -->
                <div class="bg-gray-900 p-4 text-white">
                    <h3 class="text-base font-bold mb-3 flex items-center">
                        <i class="fas fa-receipt mr-2 text-sm"></i>
                        {{ __('Payment Details') }}
                    </h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm opacity-90">{{ __('Purchase Amount') }}</span>
                            <span class="text-lg font-bold">{{ number_format($data['purchase_amount']) }}{{ __('won') }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm opacity-90">{{ __('Credits to Receive') }}</span>
                            <span class="text-base font-semibold">{{ number_format($data['credits_amount']) }}{{ __('credits') }}</span>
                        </div>
                    </div>
                </div>

                <!-- 결제 위젯 -->
                <div class="p-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                        <i class="fas fa-credit-card mr-2 text-gray-700 text-xs"></i>
                        {{ __('Select Payment Method') }}
                    </h4>
                    <div id="payment-widget" class="mb-4"></div>

                    <!-- 로딩 상태 -->
                    <div id="loading-state" class="hidden text-center py-3">
                        <div class="loading-spinner h-6 w-6 border-2 border-gray-200 border-t-gray-900 rounded-full mx-auto mb-2"></div>
                        <p class="text-sm text-gray-600">{{ __('Processing payment...') }}</p>
                    </div>

                    <!-- 버튼 그룹 -->
                    <div class="space-y-2" id="button-group">
                        <button type="button"
                            onclick="processPayment()"
                            class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors duration-150">
                            <i class="fas fa-credit-card mr-2 text-xs"></i>
                            {{ __('Proceed to Payment') }}
                        </button>
                        <button type="button"
                            onclick="cancelPayment()"
                            class="w-full flex justify-center items-center py-2 px-4 border border-gray-300 rounded text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-150">
                            <i class="fas fa-times mr-1 text-xs"></i>
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </div>

            </div>
            @else
            <!-- 에러 상태 - 크레딧 정보 없음 -->
            <div class="bg-white rounded border border-red-200 p-6 text-center">
                <div class="mx-auto h-16 w-16 bg-red-50 rounded flex items-center justify-center mb-4">
                    <i class="fas fa-exclamation-triangle text-red-700 text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">{{ __('Credit Information Not Found') }}</h3>
                <p class="text-sm text-gray-600 mb-4">{{ __('Please close this window and try the payment again') }}</p>
                <button type="button"
                    onclick="cancelPayment()"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-150">
                    <i class="fas fa-times mr-1 text-xs"></i>
                    {{ __('Close Window') }}
                </button>
            </div>
            @endif
            @else
            <!-- 에러 상태 - 세션 데이터 없음 -->
            <div class="bg-white rounded border border-red-200 p-6 text-center">
                <div class="mx-auto h-16 w-16 bg-red-50 rounded flex items-center justify-center mb-4">
                    <i class="fas fa-exclamation-triangle text-red-700 text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">{{ __('Session Expired') }}</h3>
                <p class="text-sm text-gray-600 mb-4">{{ __('Please close this window and try the payment again') }}</p>
                <button type="button"
                    onclick="cancelPayment()"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-150">
                    <i class="fas fa-times mr-1 text-xs"></i>
                    {{ __('Close Window') }}
                </button>
            </div>
            @endif
            @endif
        </div>
    </div>
    @php
    $config = [
    'isDone' => session()->has('site-credit-redirect-url'),
    'redirectUrl' => session('site-credit-redirect-url'),
    'creditData' => session('site-credit-data'),
    'customerKey' => auth()->user()?->email ?? '',
    'clientKey' => config('toss-payments.client_key'),
    'routes' => [
    'store' => route('site-credit.store'),
    'success' => route('site-credit.payments.success'),
    'fail' => route('site-credit.payments.fail'),
    'destroy' => route('site-credit.destroy', ['orderId' => '__ORDER_ID__']),
    ],
    'messages' => [
    'paymentWidgetInitFailed' => __('Payment widget initialization failed. Please try again.'),
    'paymentWidgetNotReady' => __('Payment widget is not ready. Please try again.'),
    'failedToCreateOrderId' => __('Failed to create order ID'),
    'siteCredit' => __('Site Credit'),
    'paymentFailed' => __('Payment failed'),
    'paymentError' => __('An error occurred during payment. Please close the window and try again.'),
    ],
    ];
    @endphp
    <script>
        // 전역 변수 및 설정
        const CONFIG = @json($config);

        // 유틸리티 함수들
        const Utils = {
            showLoading() {
                document.getElementById('loading-state')?.classList.remove('hidden');
                document.getElementById('button-group')?.classList.add('hidden');
            },

            hideLoading() {
                document.getElementById('loading-state')?.classList.add('hidden');
                document.getElementById('button-group')?.classList.remove('hidden');
            },

            showAlert(message, type = 'error') {
                alert(message);
            },

            closeWindow(delay = 0) {
                setTimeout(() => {
                    window.close();
                }, delay);
            },

            postMessageToOpener(data) {
                if (window.opener) {
                    window.opener.postMessage(data, '*');
                }
            },
        };

        // 결제 완료 처리
        if (CONFIG.isDone) {
            Utils.postMessageToOpener({
                type: 'payment_completed',
                success: true,
                redirectUrl: CONFIG.redirectUrl,
            });
            Utils.closeWindow(3000);
        } else if (CONFIG.creditData && CONFIG.creditData.purchase_amount && CONFIG.creditData.credits_amount) {
            // 결제 위젯 초기화
            initializePaymentWidget();
        }

        // 결제 위젯 초기화
        function initializePaymentWidget() {
            try {
                const tossPayments = window.TossPayments(CONFIG.clientKey);
                const widgets = tossPayments.widgets({
                    customerKey: CONFIG.customerKey,
                });

                widgets.setAmount({
                    currency: 'KRW',
                    value: Number(CONFIG.creditData.purchase_amount),
                });

                widgets.renderPaymentMethods({
                    selector: '#payment-widget',
                    variantKey: 'DEFAULT_KEY',
                });

                // 전역으로 widgets 저장
                window.paymentWidgets = widgets;
            } catch (error) {
                console.error('결제 위젯 초기화 실패:', error);
                Utils.showAlert(CONFIG.messages.paymentWidgetInitFailed);
            }
        }

        // 결제 처리
        async function processPayment() {
            if (!window.paymentWidgets) {
                Utils.showAlert(CONFIG.messages.paymentWidgetNotReady);
                return;
            }

            Utils.showLoading();

            try {
                // 사이트 크레딧 생성
                const response = await axios.post(CONFIG.routes.store, {});
                const orderId = response?.data?.orderId;

                if (!orderId) {
                    throw new Error(CONFIG.messages.failedToCreateOrderId);
                }

                // 결제 요청
                const paymentResponse = await window.paymentWidgets.requestPayment({
                    orderId: orderId,
                    orderName: CONFIG.messages.siteCredit,
                    successUrl: CONFIG.routes.success,
                    failUrl: CONFIG.routes.fail,
                });

                // 결제 실패 시 주문 삭제
                if (paymentResponse?.data?.status !== 1) {
                    await deleteOrder(orderId);
                    throw new Error(CONFIG.messages.paymentFailed);
                }

            } catch (error) {
                console.error('결제 처리 실패:', error);
                const errorMessage = error?.response?.data?.message || error?.message || CONFIG.messages.paymentError;
                Utils.showAlert(errorMessage);
            } finally {
                Utils.hideLoading();
            }
        }

        // 주문 삭제
        async function deleteOrder(orderId) {
            try {
                const destroyUrl = CONFIG.routes.destroy.replace('__ORDER_ID__', orderId);
                await axios.delete(destroyUrl);
            } catch (error) {
                console.error('주문 삭제 실패:', error);
            }
        }

        // 결제 취소
        function cancelPayment() {
            Utils.closeWindow();
        }

        // 에러 핸들링
        window.addEventListener('error', function(event) {
            console.error('전역 에러:', event.error);
        });

        // 페이지 언로드 시 정리
        window.addEventListener('beforeunload', function() {
            // 필요한 정리 작업
        });
    </script>

</body>

</html>