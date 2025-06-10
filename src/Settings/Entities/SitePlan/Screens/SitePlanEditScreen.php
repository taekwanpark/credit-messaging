<?php

namespace Techigh\CreditMessaging\Settings\Entities\SitePlan\Screens;

use App\Settings\Entities\Tenant\Tenant;
use App\Settings\Entities\User\User;
use App\Settings\Extends\OrbitLayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Components\Cells\DateTimeSplit;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
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
        $sitePlan->load('tenants'); // eager loading

        return [
            'sitePlan' => $sitePlan,
            'tenants' => $sitePlan->tenants()->paginate(20),
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

            Layout::block(
                Layout::table('tenants', [
                    TD::make('title', __('Title')),
                    TD::make('created_at', __('Created'))
                        ->usingComponent(DateTimeSplit::class)
                        ->align(TD::ALIGN_RIGHT)
                        ->width(100),

                    TD::make(__('Actions'))
                        ->align(TD::ALIGN_CENTER)
                        ->width('100px')
                        ->render(function (Tenant $tenant) {
                            return Button::make(__('Dissociate'))
                                ->icon('bs.x')
                                ->method('dissociate', ['tenant' => $tenant]);
                        }),
                ])
            )->title(__('Tenants'))
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

        $tenantIds = $request->input('tenantIds');

        if (!empty($tenantIds)) {
            collect($tenantIds)->filter()->each(function ($tenantId) use ($sitePlan) {
                /** @var Tenant $tenant */
                $tenant = Tenant::find($tenantId);
                if ($tenant) {
                    $tenant->sitePlan()->associate($sitePlan);
                    $tenant->save(); // 저장 필수
                }
            });
        }
        Toast::info(__('SitePlan was saved.'));

        return redirect()->route('settings.entities.site_plans.edit', $sitePlan);
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

    public function dissociate(Request $request, SitePlan $sitePlan, Tenant $tenant): RedirectResponse
    {
        $tenant->sitePlan()->dissociate();
        $tenant->save();

        Toast::info(__('Tenant was dissociated.'));

        return redirect()->route('settings.entities.site_plans.edit', $sitePlan);
    }
}
