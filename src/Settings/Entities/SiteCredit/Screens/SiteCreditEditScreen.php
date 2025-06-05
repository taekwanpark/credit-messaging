<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCredit\Screens;

use App\Settings\Entities\User\User;
use App\Settings\Extends\OrbitLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Toast;
use Techigh\CreditMessaging\Settings\Entities\SiteCredit\Layouts\SiteCreditEditLayout;
use Techigh\CreditMessaging\Settings\Entities\SiteCredit\SiteCredit;

class SiteCreditEditScreen extends Screen
{
    /**
     * @var SiteCredit
     */
    public $siteCredit;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(SiteCredit $siteCredit): iterable
    {
        return [
            'siteCredit' => $siteCredit,
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
        if ($this->siteCredit->exists) {
            /** @var User $user */
            $user = Auth::user();

//            if ($user->hasAccess('settings.entities.site_credits.delete')) {
//                $commands[] = Button::make(__('Remove'))
//                    ->icon('trash')
//                    ->method('remove');
//            }
        }

        $commands[] = Button::make(__('Pay'))
            ->icon('credit-card')
            ->method('pay');

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
            OrbitLayout::block(SiteCreditEditLayout::class)
                ->title(__('SiteCredit Details'))
                ->description(__('Edit the details of the dummy entity name.'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::BASIC)
                        ->icon('bs.check-circle')
                        ->canSee($this->siteCredit->exists)
                        ->method('save')
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
     * Save the SiteCredit.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function pay(Request $request, SiteCredit $siteCredit)
    {

        $siteCredit->fill($request->input('siteCredit'));
        $siteCredit->save();

        Toast::info(__('SiteCredit was saved.'));

        return redirect()->route('settings.entities.site_credits');
    }


    /**
     * Save the SiteCredit.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function save(Request $request, SiteCredit $siteCredit)
    {

        $siteCredit->fill($request->input('siteCredit'));
        $siteCredit->save();

        Toast::info(__('SiteCredit was saved.'));

        return redirect()->route('settings.entities.site_credits');
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
}
