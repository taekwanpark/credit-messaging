<?php

namespace Techigh\CreditMessaging\Http\Controllers;

use App\Services\TossPayments\Attributes\Payment as TossPayment;
use App\Services\TossPayments\TossPayments;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Techigh\CreditMessaging\Settings\Entities\SiteCredit\SiteCredit;

class PaymentController extends Controller
{
    public function create(Request $request)
    {
        if (is_null($request->input('redirectUrl'))) {
            session()->forget('site-credit-redirect-url');
            $purchaseAmount = $request->input('purchaseAmount');
            $costPerCredit = $request->input('costPerCredit');
            $creditsAmount = floor($purchaseAmount / $costPerCredit);
            $input = [
                'purchase_amount' => $purchaseAmount,
                'credits_amount' => $creditsAmount,
                'cost_per_credit' => $costPerCredit,
                'alimtalk_credits_cost' => $request->get('alimtalkCreditsCost'),
                'sms_credits_cost' => $request->get('smsCreditsCost'),
                'lms_credits_cost' => $request->get('lmsCreditsCost'),
                'mms_credits_cost' => $request->get('mmsCreditsCost'),
            ];
            session()->put('site-credit-data', $input);
        } else {
            session()->forget('site-credit-data');
            session()->put('site-credit-redirect-url', $request->input('redirectUrl', 'zzzz'));
        }
        return view('crm::payment');
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $input = session('site-credit-data');
        try {
            $siteCredit = SiteCredit::create($input);
            return response()->json([
                'status' => 1,
                'orderId' => $siteCredit->order_id,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => -1,
                'message' => __('Error creating order. Please try again.'),
            ], 500);
        }
    }

    public function destroy(Request $request, $orderId): \Illuminate\Http\JsonResponse
    {
        try {
            SiteCredit::where('order_id', $orderId)->delete();
            return response()->json([
                'status' => 1,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => -1,
                'message' => __('Error deleting order. Please try again.'),
            ], 500);
        }
    }

    public function success(Request $request)
    {
        $paymentKey = $request->input('paymentKey');
        $orderId = $request->input('orderId');
        $amount = $request->input('amount');

        $siteCredit = SiteCredit::query()->where('order_id', $orderId)->first();

        if (is_null($siteCredit)) {
            return back()->withErrors([
                'status' => -1,
                'message' => __('Not found order.'),
            ]);
        }

        $payment = TossPayments::for(TossPayment::class)
            ->paymentKey($paymentKey)
            ->orderId($orderId)
            ->amount($amount)
            ->confirm();
        // ------------------------------------------------------
        if (isset($payment['code']) && isset($payment['message'])) { // 실패 시 삭제
            $siteCredit->delete();
            return back()->withErrors([
                'status' => -1,
                'message' => $payment['message']
            ]);
        }
        // ------------------------------------------------------
        $payment = json_decode($payment, true);

        try {
            DB::transaction(function () use ($payment, $siteCredit) {
                $siteCredit->payment()->create([
                    'm_id' => $payment['mId'],
                    'last_transaction_key' => $payment['lastTransactionKey'],
                    'payment_key' => $payment['paymentKey'],
                    'order_id' => $payment['orderId'],
                    'order_name' => $payment['orderName'],
                    'tax_exemption_amount' => $payment['taxExemptionAmount'],
                    'status' => $payment['status'],
                    'requested_at' => $payment['requestedAt'],
                    'approved_at' => $payment['approvedAt'],
                    'use_escrow' => $payment['useEscrow'],
                    'culture_expense' => $payment['cultureExpense'],
                    'card' => $payment['card'],
                    'virtual_account' => $payment['virtualAccount'],
                    'transfer' => $payment['transfer'],
                    'mobile_phone' => $payment['mobilePhone'],
                    'gift_certificate' => $payment['giftCertificate'],
                    'cash_receipt' => $payment['cashReceipt'],
                    'cash_receipts' => $payment['cashReceipts'],
                    'discount' => $payment['discount'],
                    'cancels' => $payment['cancels'],
                    'secret' => $payment['secret'],
                    'type' => $payment['type'],
                    'easy_pay' => $payment['easyPay'],
                    'country' => $payment['country'],
                    'failure' => $payment['failure'],
                    'is_partial_cancelable' => $payment['isPartialCancelable'],
                    'receipt_url' => $payment['receipt']['url'] ?? null,
                    'checkout_url' => $payment['checkout']['url'] ?? null,
                    'currency' => $payment['currency'],
                    'total_amount' => $payment['totalAmount'],
                    'balance_amount' => $payment['balanceAmount'],
                    'supplied_amount' => $payment['suppliedAmount'],
                    'vat' => $payment['vat'],
                    'tax_free_amount' => $payment['taxFreeAmount'],
                    'method' => $payment['method'],
                    'version' => $payment['version'],
                ]);
            });
        } catch (\Exception) {
            $siteCredit->delete();
            return back()->withErrors([
                'status' => -1,
                'message' => __('Error creating payment'),
            ]);
        }

        return to_route('site-credit.payment', [
            'redirectUrl' => route('settings.entities.site_credits.edit', [
                'siteCredit' => $siteCredit,
            ]),
        ]);
    }


    public function fail(Request $request)
    {
//        $code = $request->input('code');
        $message = $request->input('message');
//        $orderId = $request->input('orderId');

        // failUrl로 전달되는 에러(3)

        // 1. PAY_PROCESS_CANCELED
        // 구매자에 의해 결제가 취소되면 PAY_PROCESS_CANCELED 에러가 발생합니다. 결제 과정이 중단된 것이라서 failUrl로 orderId가 전달되지 않아요.

        // 2. PAY_PROCESS_ABORTED
        // 결제가 실패하면 PAY_PROCESS_ABORTED 에러가 발생합니다.

        // 3. REJECTED_CARD_COMPANY
        // 구매자가 입력한 카드 정보에 문제가 있다면 REJECT_CARD_COMPANY 에러가 발생합니다.

        return back()->withErrors([
            'status' => -1,
            'message' => $message
        ]);
    }
}
