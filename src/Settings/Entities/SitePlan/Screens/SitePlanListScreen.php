<?php

namespace Techigh\CreditMessaging\Settings\Entities\SitePlan\Screens;

use App\Settings\Entities\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;
use Techigh\CreditMessaging\Settings\Entities\SitePlan\Layouts\SitePlanListLayout;
use Techigh\CreditMessaging\Settings\Entities\SitePlan\SitePlan;

class SitePlanListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'sitePlans' => SitePlan::filters()->defaultSort('id')->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return __('SitePlanListScreen Management');
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return __('A comprehensive list of all SitePlanListScreen');
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
        if ($user->hasAccess('settings.entities.site_plans.create')) $commands[] = Link::make(__('Create'))->icon('bs.plus-circle')->route('settings.entities.site_plans.create');

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
            SitePlanListLayout::class,
        ];
    }

    public function remove(Request $request): void
    {
        SitePlan::findOrFail($request->get('id'))->delete();

        Toast::info(__('SitePlan was removed'));
    }
}
