<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCreditPayment\Screens;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Color;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditPayment\SiteCreditPayment;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditPayment\Layouts\SiteCreditPaymentEditLayout;
use App\Settings\Extends\OrbitLayout;

class SiteCreditPaymentEditScreen extends Screen
{
    /**
     * @var SiteCreditPayment
     */
    public $siteCreditPayment;

    /**
     * Query data.
     *
     * @param SiteCreditPayment $siteCreditPayment
     *
     * @return array
     */
    public function query(SiteCreditPayment $siteCreditPayment): array
    {
        return [
            'siteCreditPayment' => $siteCreditPayment,
        ];
    }

    /**
     * The name is displayed on the user's screen.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->siteCreditPayment->exists ? __('Edit Site Credit Payment') . ' - ' . $this->siteCreditPayment->transaction_id : __('Create Site Credit Payment');
    }

    /**
     * The description is displayed on the user's screen under the heading
     *
     * @return string|null
     */
    public function description(): ?string
    {
        return __('Manage site credit payment records');
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
                ->confirm(__('Are you sure you want to delete this payment record?'))
                ->method('remove')
                ->canSee($this->siteCreditPayment->exists),

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
            OrbitLayout::block(SiteCreditPaymentEditLayout::class)
                ->title(__('Site Credit Payment Information'))
                ->description(__('Track payment transactions and status'))
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
            'settings.entities.site_credit_payments.create',
            'settings.entities.site_credit_payments.edit',
        ];
    }

    /**
     * @param SiteCreditPayment $siteCreditPayment
     * @param Request    $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(SiteCreditPayment $siteCreditPayment, Request $request): RedirectResponse
    {
        $request->validate([
            'siteCreditPayment.site_id' => 'required|string|max:255',
            'siteCreditPayment.amount' => 'required|numeric|min:0',
            'siteCreditPayment.payment_method' => 'required|string',
            'siteCreditPayment.status' => 'required|string',
        ]);

        $siteCreditPayment->fill($request->get('siteCreditPayment'))->save();

        Toast::info(__('Site credit payment was saved successfully.'));

        return redirect()->route('settings.entities.site_credit_payments');
    }

    /**
     * @param SiteCreditPayment $siteCreditPayment
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     */
    public function remove(SiteCreditPayment $siteCreditPayment): RedirectResponse
    {
        $siteCreditPayment->delete();

        Toast::info(__('Site credit payment was removed successfully.'));

        return redirect()->route('settings.entities.site_credit_payments');
    }
}