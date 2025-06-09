<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\Screens;

use App\Settings\Entities\User\User;
use App\Settings\Extends\OrbitLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Toast;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\Layouts\SiteCreditUsageEditLayout;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\SiteCreditUsage;

class SiteCreditUsageEditScreen extends Screen
{
    /**
     * @var SiteCreditUsage
     */
    public $siteCreditUsage;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(SiteCreditUsage $siteCreditUsage): iterable
    {
        return [
            'siteCreditUsage' => $siteCreditUsage,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->siteCreditUsage->exists ? 'Edit SiteCreditUsage' : 'Create SiteCreditUsage';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        $commands = [];
        if ($this->siteCreditUsage->exists) {
            /** @var User $user */
            $user = Auth::user();

            if ($user->hasAccess('settings.entities.site_credit_usages.delete')) {
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
            OrbitLayout::block(SiteCreditUsageEditLayout::class)
                ->title(__('SiteCreditUsage Details'))
                ->description(__('Edit the details of the dummy entity name.'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::BASIC)
                        ->icon('bs.check-circle')
                        ->canSee($this->siteCreditUsage->exists)
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
            'settings.entities.site_credit_usages.create',
            'settings.entities.site_credit_usages.edit',
        ];
    }

    /**
     * Save the SiteCreditUsage.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function save(Request $request, SiteCreditUsage $siteCreditUsage)
    {
        $siteCreditUsage->fill($request->input('siteCreditUsage'));
        $siteCreditUsage->save();

        Toast::info(__('SiteCreditUsage was saved.'));

        return redirect()->route('settings.entities.site_credit_usages');
    }

    /**
     * Remove the SiteCreditUsage.
     *
     */
    public function remove(SiteCreditUsage $siteCreditUsage)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasAccess('settings.entities.site_credit_usages.delete')) {
            $siteCreditUsage->delete();
            Toast::info(__('SiteCreditUsage was removed.'));
        } else {
            Toast::error(__('You do not have permission to delete this SiteCreditUsage.'));
        }

        return redirect()->route('settings.entities.site_credit_usages');
    }
}
