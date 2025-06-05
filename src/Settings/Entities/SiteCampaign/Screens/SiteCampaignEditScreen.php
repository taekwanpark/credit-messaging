<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCampaign\Screens;

use App\Settings\Entities\User\User;
use App\Settings\Extends\OrbitLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Toast;
use Techigh\CreditMessaging\Settings\Entities\SiteCampaign\Layouts\SiteCampaignEditLayout;
use Techigh\CreditMessaging\Settings\Entities\SiteCampaign\SiteCampaign;

class SiteCampaignEditScreen extends Screen
{
    /**
     * @var SiteCampaign
     */
    public $siteCampaign;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(SiteCampaign $siteCampaign): iterable
    {
        return [
            'siteCampaign' => $siteCampaign,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->siteCampaign->exists ? 'Edit SiteCampaign' : 'Create SiteCampaign';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        $commands = [];
        if ($this->siteCampaign->exists) {
            /** @var User $user */
            $user = Auth::user();

            if ($user->hasAccess('settings.entities.site_campaigns.delete')) {
                $commands[] = Button::make(__('Remove'))
                    ->icon('trash')
                    ->method('remove');
            }
        }

//        $commands[] = Button::make(__('Save'))
//            ->icon('check')
//            ->method('save');

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
            OrbitLayout::block(SiteCampaignEditLayout::class)
                ->title(__('SiteCampaign Details'))
                ->description(__('Edit the details of the dummy entity name.'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::BASIC)
                        ->icon('bs.check-circle')
                        ->canSee($this->siteCampaign->exists)
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
            'settings.entities.site_campaigns.create',
            'settings.entities.site_campaigns.edit',
        ];
    }

    /**
     * Save the SiteCampaign.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function save(Request $request, SiteCampaign $siteCampaign)
    {
        $siteCampaign->fill($request->input('siteCampaign'));
        $siteCampaign->save();

        Toast::info(__('SiteCampaign was saved.'));

        return redirect()->route('settings.entities.site_campaigns');
    }

    /**
     * Remove the SiteCampaign.
     *
     * @param \SiteCampaign\SiteCampaign $siteCampaign
     */
    public function remove(SiteCampaign $siteCampaign)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasAccess('settings.entities.site_campaigns.delete')) {
            $siteCampaign->delete();
            Toast::info(__('SiteCampaign was removed.'));
        } else {
            Toast::error(__('You do not have permission to delete this SiteCampaign.'));
        }

        return redirect()->route('settings.entities.site_campaigns');
    }
}
