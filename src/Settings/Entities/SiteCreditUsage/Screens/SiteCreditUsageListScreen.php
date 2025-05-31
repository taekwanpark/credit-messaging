<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\Screens;

use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\SiteCreditUsage;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\Layouts\SiteCreditUsageListLayout;

class SiteCreditUsageListScreen extends Screen
{
    /**
     * Query data.
     *
     * @return array
     */
    public function query(): array
    {
        return [
            'siteCreditUsages' => SiteCreditUsage::filters()
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
        return __('Site Credit Usages');
    }

    /**
     * The description is displayed on the user's screen under the heading
     *
     * @return string|null
     */
    public function description(): ?string
    {
        return __('Track and manage site credit usage records');
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
                ->route('settings.entities.site_credit_usages.create'),
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
            SiteCreditUsageListLayout::class,
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
            'settings.entities.site_credit_usages.list',
        ];
    }

    /**
     * @param Request $request
     */
    public function remove(Request $request): void
    {
        SiteCreditUsage::findOrFail($request->get('id'))->delete();

        Toast::info(__('Site credit usage was removed successfully.'));
    }
}