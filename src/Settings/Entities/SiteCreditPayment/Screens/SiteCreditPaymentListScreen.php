<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCreditPayment\Screens;

use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditPayment\SiteCreditPayment;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditPayment\Layouts\SiteCreditPaymentListLayout;

class SiteCreditPaymentListScreen extends Screen
{
    /**
     * Query data.
     *
     * @return array
     */
    public function query(): array
    {
        return [
            'siteCreditPayments' => SiteCreditPayment::filters()
                ->defaultSort('id', 'desc')
                ->paginate(),
        ];
    }

    /**
     * The name is displayed on the user's screen.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return __('Site Credit Payments');
    }

    /**
     * The description is displayed on the user's screen under the heading
     *
     * @return string|null
     */
    public function description(): ?string
    {
        return __('Track and manage site credit payment records');
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): array
    {
        return [
            Link::make(__('Add'))
                ->icon('plus')
                ->route('settings.entities.site_credit_payments.create'),
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
            SiteCreditPaymentListLayout::class,
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
            'settings.entities.site_credit_payments',
        ];
    }

    /**
     * @param Request $request
     */
    public function remove(Request $request): void
    {
        SiteCreditPayment::findOrFail($request->get('id'))->delete();

        Toast::info(__('Site credit payment was removed successfully.'));
    }
}