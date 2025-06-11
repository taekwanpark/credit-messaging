<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCredit\Screens;

use App\Settings\Entities\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;
use Techigh\CreditMessaging\Settings\Entities\SiteCredit\Layouts\SiteCreditListLayout;
use Techigh\CreditMessaging\Settings\Entities\SiteCredit\SiteCredit;

class SiteCreditListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'siteCredits' => SiteCredit::filters()->defaultSort('created_at', 'desc')->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return __('SiteCreditListScreen Management');
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return __('A comprehensive list of all SiteCreditListScreen');
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
        if ($user->hasAccess('settings.entities.site_credits.create')) $commands[] = Link::make(__('Pay'))->icon('bs.credit-card')->route('settings.entities.site_credits.create');

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
            SiteCreditListLayout::class,
        ];
    }

    public function remove(Request $request): void
    {
        SiteCredit::findOrFail($request->get('id'))->delete();

        Toast::info(__('SiteCredit was removed'));
    }

}
