<?php

namespace Techigh\CreditMessaging\Settings\Entities\SitePlan\Screens;

use App\Settings\Entities\Tenant\Layouts\TenantListLayout;
use App\Settings\Entities\Tenant\Tenant;
use App\Settings\Entities\User\User;
use App\Settings\Extends\OrbitLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Toast;
use Techigh\CreditMessaging\Settings\Entities\SitePlan\Layouts\SitePlanEditLayout;
use Techigh\CreditMessaging\Settings\Entities\SitePlan\SitePlan;

class SitePlanEditScreen extends Screen
{
    /**
     * @var SitePlan
     */
    public $sitePlan;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(SitePlan $sitePlan): iterable
    {
        return [
            'sitePlan' => $sitePlan,
            'tenants' => $sitePlan->tenants()->paginate(20)
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->sitePlan->exists ? 'Edit SitePlan' : 'Create SitePlan';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        $commands = [];
        if ($this->sitePlan->exists) {
            /** @var User $user */
            $user = Auth::user();

            if ($user->hasAccess('settings.entities.site_plans.delete')) {
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
            OrbitLayout::block(SitePlanEditLayout::class)
                ->title(__('SitePlan Details'))
                ->description(__('Edit the details of the dummy entity name.'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::BASIC)
                        ->icon('bs.check-circle')
                        ->canSee($this->sitePlan->exists)
                        ->method('save')
                ),
            (new TenantListLayout())
                ->title(__('Tenants'))
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
            'settings.entities.site_plans.create',
            'settings.entities.site_plans.edit',
        ];
    }

    /**
     * Save the SitePlan.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function save(Request $request, SitePlan $sitePlan)
    {

        $sitePlan->fill($request->input('sitePlan'));
        $sitePlan->save();

        $tenants = $request->input('sitePlanTenants');
        if (!empty($tenants)) {
            collect($tenants)->each(function ($tenant) use ($sitePlan) {
                $tenant = Tenant::find($tenant);
                $tenant->sitePlan()->associate($sitePlan);
            });
        }
        Toast::info(__('SitePlan was saved.'));

        return redirect()->route('settings.entities.site_plans');
    }

    /**
     * Remove the SitePlan.
     *
     */
    public function remove(SitePlan $sitePlan)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasAccess('settings.entities.site_plans.delete')) {
            $sitePlan->delete();
            Toast::info(__('SitePlan was removed.'));
        } else {
            Toast::error(__('You do not have permission to delete this SitePlan.'));
        }

        return redirect()->route('settings.entities.site_plans');
    }
}
