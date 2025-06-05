<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCampaignMessage\Screens;

use App\Settings\Entities\User\User;
use App\Settings\Extends\OrbitLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Toast;
use Techigh\CreditMessaging\Settings\Entities\SiteCampaignMessage\Layouts\SiteCampaignMessageEditLayout;
use Techigh\CreditMessaging\Settings\Entities\SiteCampaignMessage\SiteCampaignMessage;

class SiteCampaignMessageEditScreen extends Screen
{
    /**
     * @var SiteCampaignMessage
     */
    public $siteCampaignMessage;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(SiteCampaignMessage $siteCampaignMessage): iterable
    {
        return [
            'siteCampaignMessage' => $siteCampaignMessage,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->siteCampaignMessage->exists ? 'Edit SiteCampaignMessage' : 'Create SiteCampaignMessage';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        $commands = [];
        if ($this->siteCampaignMessage->exists) {
            /** @var User $user */
            $user = Auth::user();

            if ($user->hasAccess('settings.entities.site_campaign_messages.delete')) {
                $commands[] = Button::make(__('Remove'))
                    ->icon('trash')
                    ->method('remove');
            }
        }

        $commands[] = Button::make(__('Save'))
            ->icon('check')
            ->method('save');

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
            OrbitLayout::block(SiteCampaignMessageEditLayout::class)
                ->title(__('SiteCampaignMessage Details'))
                ->description(__('Edit the details of the dummy entity name.'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::BASIC)
                        ->icon('bs.check-circle')
                        ->canSee($this->siteCampaignMessage->exists)
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
            'settings.entities.site_campaign_messages.create',
            'settings.entities.site_campaign_messages.edit',
        ];
    }

    /**
     * Save the SiteCampaignMessage.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function save(Request $request, SiteCampaignMessage $siteCampaignMessage)
    {
        $siteCampaignMessage->fill($request->input('siteCampaignMessage'));
        $siteCampaignMessage->save();

        Toast::info(__('SiteCampaignMessage was saved.'));

        return redirect()->route('settings.entities.site_campaign_messages');
    }

    /**
     * Remove the SiteCampaignMessage.
     *
     * @param \SiteCampaignMessage\SiteCampaignMessage $siteCampaignMessage
     */
    public function remove(SiteCampaignMessage $siteCampaignMessage)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasAccess('settings.entities.site_campaign_messages.delete')) {
            $siteCampaignMessage->delete();
            Toast::info(__('SiteCampaignMessage was removed.'));
        } else {
            Toast::error(__('You do not have permission to delete this SiteCampaignMessage.'));
        }

        return redirect()->route('settings.entities.site_campaign_messages');
    }
}
