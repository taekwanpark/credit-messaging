<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCredit\Screens;

use App\Settings\Entities\Tenant\Tenant;
use App\Settings\Entities\User\User;
use App\Settings\Extends\OrbitLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Support\Facades\Toast;
use Techigh\CreditMessaging\Settings\Entities\SiteCredit\Layouts\SiteCreditEditLayout;
use Techigh\CreditMessaging\Settings\Entities\SiteCredit\SiteCredit;

class SiteCreditEditScreen extends Screen
{
    /**
     * @var SiteCredit
     */
    public $siteCredit;

    public $sitePlan;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(SiteCredit $siteCredit): iterable
    {
        // 테넌트 초기화 되어있는 경우
        if (tenancy()->initialized) {
            /** @var Tenant $tenant */
            $tenant = tenancy()->tenant;
            tenancy()->central(function () use ($tenant, &$sitePlan) {
                // 테넌트 컨텍스트에서 실행
                if ($tenant) {
                    $sitePlan = $tenant->sitePlan;
                    $sitePlan = [
                        'title' => $sitePlan->title,
                        'cost_per_credit' => $sitePlan->cost_per_credit,
                        'alimtalk_credits_cost' => $sitePlan->alimtalk_credits_cost,
                        'sms_credits_cost' => $sitePlan->sms_credits_cost,
                        'lms_credits_cost' => $sitePlan->lms_credits_cost,
                        'mms_credits_cost' => $sitePlan->mms_credits_cost,
                    ];
                }
            });
        } else {
            $sitePlan = [
                'title' => __('Site Plan'),
                'cost_per_credit' => siteConfigs('site_cost_per_credit', config('credit-messaging.default_credit_costs.cost_per_credit')),
                'alimtalk_credits_cost' => siteConfigs('site_alimtalk_credits_cost', config('credit-messaging.default_credit_costs.alimtalk')),
                'sms_credits_cost' => siteConfigs('site_sms_credits_cost', config('credit-messaging.default_credit_costs.sms')),
                'lms_credits_cost' => siteConfigs('site_lms_credits_cost', config('credit-messaging.default_credit_costs.lms')),
                'mms_credits_cost' => siteConfigs('site_mms_credits_cost', config('credit-messaging.default_credit_costs.mms')),
            ];
        }

        $purchaseAmount = request()->input('purchaseAmount') ?? 0;
        if ($purchaseAmount !== 0) {
            $siteCredit->setAttribute('purchase_amount', (int)$purchaseAmount);
            $creditAmount = floor($purchaseAmount / $sitePlan['cost_per_credit']);
            $siteCredit->setAttribute('credits_amount', $creditAmount);
        }
        $this->sitePlan = $sitePlan;
        return [
            'siteCredit' => $siteCredit,
            'sitePlan' => $sitePlan,
            'paymentUrl' => request()->input('paymentUrl'),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->siteCredit->exists ? 'Edit SiteCredit' : 'Create SiteCredit';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        $commands = [];

        return $commands;
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            OrbitLayout::legend('sitePlan', [
                Sight::make('sitePlan', __('SitePlan'))->render(function ($sitePlan) {
                    return view('crm::components.site-plan-table', ['options' => $sitePlan]);
                }),
            ])
                ->canSee(!$this->siteCredit->exists),

            OrbitLayout::block(SiteCreditEditLayout::class)
                ->title(__('SiteCredit Details'))
                ->description(__('Edit the details of the dummy entity name.'))
                ->commands([
                    Button::make(__('Calculate'))
                        ->icon('credit-card')
                        ->method('calculate')
                        ->parameters([
                            'turbo' => true,
                            'async' => true
                        ])->canSee(!$this->siteCredit->exists),
                ]),

            OrbitLayout::view('crm::credit-payment')
                ->canSee(
                    !$this->siteCredit->exists
                    && $this->siteCredit->credits_amount > 0
                    && $this->siteCredit->purchase_amount > 0
                ),
        ];
    }


    /**
     * Define the permissions required to view this screen.
     *
     * @return iterable|null
     */
    public function permission(): ?iterable
    {
        return [
            'settings.entities.site_credits.create',
            'settings.entities.site_credits.edit',
        ];
    }


    /**
     * Save the SiteCredit and return payment URL for new window.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function pay(Request $request, SiteCredit $siteCredit)
    {

        $siteCredit->fill($request->input('siteCredit'));
        $siteCredit->save();


        Toast::info(__('SiteCredit was saved.'));


        return redirect()->route('settings.entities.site_credits.edit', [
            'siteCredit' => $siteCredit,
        ]);
    }

    /**
     * Remove the SiteCredit.
     *
     */
    public function remove(SiteCredit $siteCredit)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasAccess('settings.entities.site_credits.delete')) {
            $siteCredit->delete();
            Toast::info(__('SiteCredit was removed.'));
        } else {
            Toast::error(__('You do not have permission to delete this SiteCredit.'));
        }

        return redirect()->route('settings.entities.site_credits');
    }

    public function calculate(Request $request, SiteCredit $siteCredit)
    {
        $paymentUrl = route('site-credit.payment') . '?' . http_build_query([
                'purchaseAmount' => $request->input('siteCredit.purchase_amount'),
                'costPerCredit' => $this->sitePlan['cost_per_credit'],
                'alimtalkCreditsCost' => $this->sitePlan['alimtalk_credits_cost'],
                'smsCreditsCost' => $this->sitePlan['sms_credits_cost'],
                'lmsCreditsCost' => $this->sitePlan['lms_credits_cost'],
                'mmsCreditsCost' => $this->sitePlan['mms_credits_cost'],
            ]);
        return redirect()->route('settings.entities.site_credits.create', [
            'purchaseAmount' => $request->input('siteCredit.purchase_amount'),
            'paymentUrl' => $paymentUrl,
        ]);
    }
}
