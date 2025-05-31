<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCredit\Screens;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Color;
use Techigh\CreditMessaging\Settings\Entities\SiteCredit\SiteCredit;
use Techigh\CreditMessaging\Settings\Entities\SiteCredit\Layouts\SiteCreditEditLayout;
use App\Settings\Extends\OrbitLayout;

class SiteCreditEditScreen extends Screen
{
    /**
     * @var SiteCredit
     */
    public $siteCredit;

    /**
     * Query data.
     *
     * @param SiteCredit $siteCredit
     *
     * @return array
     */
    public function query(SiteCredit $siteCredit): array
    {
        return [
            'siteCredit' => $siteCredit,
        ];
    }

    /**
     * The name is displayed on the user's screen.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->siteCredit->exists ? __('Edit Site Credit') . ' - ' . $this->siteCredit->site_id : __('Create Site Credit');
    }

    /**
     * The description is displayed on the user's screen under the heading
     *
     * @return string|null
     */
    public function description(): ?string
    {
        return __('Manage site credit configuration and pricing');
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): array
    {
        return [
            Button::make(__('Remove'))
                ->icon('trash')
                ->confirm(__('Are you sure you want to delete this site credit?'))
                ->method('remove')
                ->canSee($this->siteCredit->exists),

            Button::make(__('Save'))
                ->icon('check')
                ->method('save'),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): array
    {
        return [
            OrbitLayout::block(SiteCreditEditLayout::class)
                ->title(__('Site Credit Information'))
                ->description(__('Configure credit balance and pricing for the site'))
                ->commands([
                    Button::make(__('Save'))
                        ->type(Color::BASIC)
                        ->icon('check')
                        ->method('save')
                ]),
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
     * @param SiteCredit $siteCredit
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(SiteCredit $siteCredit, Request $request): RedirectResponse
    {
        // todo
        // 0. site_grade 생성해서 cost_per_credit, *_credit_cost 추가해주자 - hidden_emails만 사용 가능하도록 하자
        // 1.
        // 2. 토스페이먼츠 붙이거나 계좌이체 등 결제 완료하는 시점(SiteCreditPayment.Status = SUCCESS)에 이벤트로 생성하자
        // 3. 모든 모델에 대하여 수정 삭제 불가능하도록 하자(hidden_emails만 가능하도록)
        $request->validate([
            'siteCredit.site_id' => 'required|string|max:255',
            'siteCredit.balance' => 'required|numeric|min:0',
            'siteCredit.alimtalk_cost' => 'required|numeric|min:0',
            'siteCredit.sms_cost' => 'required|numeric|min:0',
            'siteCredit.lms_cost' => 'required|numeric|min:0',
            'siteCredit.mms_cost' => 'required|numeric|min:0',
            'siteCredit.auto_charge_enabled' => 'boolean',
            'siteCredit.auto_charge_threshold' => 'nullable|numeric|min:0',
            'siteCredit.auto_charge_amount' => 'nullable|numeric|min:0',
            'siteCredit.sort_order' => 'nullable|integer|min:0',
        ]);

        $siteCredit->fill($request->get('siteCredit'))->save();

        Toast::info(__('Site credit was saved successfully.'));

        return redirect()->route('settings.entities.site_credits');
    }

    /**
     * @param SiteCredit $siteCredit
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     */
    public function remove(SiteCredit $siteCredit): RedirectResponse
    {
        $siteCredit->delete();

        Toast::info(__('Site credit was removed successfully.'));

        return redirect()->route('settings.entities.site_credits');
    }
}