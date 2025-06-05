<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCampaign\Screens;

use App\Settings\Entities\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;
use Techigh\CreditMessaging\Settings\Entities\SiteCampaign\Layouts\SiteCampaignListLayout;
use Techigh\CreditMessaging\Settings\Entities\SiteCampaign\SiteCampaign;

class SiteCampaignListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'siteCampaigns' => SiteCampaign::filters()->defaultSort('id')->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return __('SiteCampaignListScreen Management');
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return __('A comprehensive list of all SiteCampaignListScreen');
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
//        if ($user->hasAccess('settings.entities.site_campaigns.create')) $commands[] = Link::make(__('Create'))->icon('bs.plus-circle')->route('settings.entities.site_campaigns.create');

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
            SiteCampaignListLayout::class,
        ];
    }

    public function remove(Request $request): void
    {
        SiteCampaign::findOrFail($request->get('id'))->delete();

        Toast::info(__('SiteCampaign was removed'));
    }
}
