<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\Screens;

use App\Settings\Entities\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\Layouts\SiteCreditUsageListLayout;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\SiteCreditUsage;

class SiteCreditUsageListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'siteCreditUsages' => SiteCreditUsage::filters()->defaultSort('id')->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return __('SiteCreditUsageListScreen Management');
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return __('A comprehensive list of all SiteCreditUsageListScreen');
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        $commands = [];
        /** @var User $user */
        $user = Auth::user();
//        if ($user->hasAccess('settings.entities.site_credit_usages.create')) $commands[] = Link::make(__('Create'))->icon('bs.plus-circle')->route('settings.entities.site_credit_usages.create');

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
            SiteCreditUsageListLayout::class,
        ];
    }

    public function remove(Request $request): void
    {
        SiteCreditUsage::findOrFail($request->get('id'))->delete();

        Toast::info(__('SiteCreditUsage was removed'));
    }
}
