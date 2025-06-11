<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCredit\Screens;

use App\Settings\Entities\User\User;
use App\Settings\Extends\OrbitLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
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
        $availableSiteCreditsQuery = SiteCredit::query()
            ->where('status', 'SUCCESS')
            ->where('balance_credits', '>', 0);

        $availableAlimtalkCount = 0;
        $availableSiteCreditsQuery->each(function (SiteCredit $siteCredit) use (&$availableAlimtalkCount) {
            $balance = $siteCredit->balance_credits;
            $creditCost = $siteCredit->alimtalk_credits_cost ?? 0;

            if ($creditCost > 0) $sendableCount = $balance / $creditCost;
            else $sendableCount = 0;

            $availableAlimtalkCount += $sendableCount;
        });

        // 숫자 내림 처리
        $availableAlimtalkCount = floor($availableAlimtalkCount);
        $balanceCredits = floor($availableSiteCreditsQuery->sum('balance_credits'));
        $message = sprintf('%s: %s, %s: %s', __('Balance Credit'), number_format($balanceCredits), __('Available Count'), number_format($availableAlimtalkCount));
        return [
            OrbitLayout::view('crm::components.announcement', [
                'title' => __('Site Credit Stats'),
                'messages' => [
                    $message,
                ],
                'alert' => false
            ]),
            SiteCreditListLayout::class,
        ];
    }

    public function remove(Request $request): void
    {
        SiteCredit::findOrFail($request->get('id'))->delete();

        Toast::info(__('SiteCredit was removed'));
    }

}
