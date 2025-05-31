<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\Screens;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Color;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\SiteCreditUsage;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\Layouts\SiteCreditUsageEditLayout;
use App\Settings\Extends\OrbitLayout;

class SiteCreditUsageEditScreen extends Screen
{
    /**
     * @var SiteCreditUsage
     */
    public $siteCreditUsage;

    /**
     * Query data.
     *
     * @param SiteCreditUsage $siteCreditUsage
     *
     * @return array
     */
    public function query(SiteCreditUsage $siteCreditUsage): array
    {
        return [
            'siteCreditUsage' => $siteCreditUsage,
        ];
    }

    /**
     * The name is displayed on the user's screen.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->siteCreditUsage->exists ? __('Edit Site Credit Usage') . ' - ' . $this->siteCreditUsage->id : __('Create Site Credit Usage');
    }

    /**
     * The description is displayed on the user's screen under the heading
     *
     * @return string|null
     */
    public function description(): ?string
    {
        return __('Manage site credit usage records');
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): array
    {
        return [
            Button::make(__('Remove'))
                ->icon('trash')
                ->confirm(__('Are you sure you want to delete this usage record?'))
                ->method('remove')
                ->canSee($this->siteCreditUsage->exists),

            Button::make(__('Save'))
                ->icon('check')
                ->method('save'),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): array
    {
        return [
            OrbitLayout::block(SiteCreditUsageEditLayout::class)
                ->title(__('Site Credit Usage Information'))
                ->description(__('Track credit usage and refunds'))
                ->commands([
                    Button::make(__('Save'))
                        ->type(Color::BASIC)
                        ->icon('check')
                        ->method('save')
                ]),
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
     * @param SiteCreditUsage $siteCreditUsage
     * @param Request    $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(SiteCreditUsage $siteCreditUsage, Request $request): RedirectResponse
    {
        $request->validate([
            'siteCreditUsage.site_id' => 'required|string|max:255',
            'siteCreditUsage.quantity' => 'required|integer|min:0',
            'siteCreditUsage.cost_per_unit' => 'required|numeric|min:0',
            'siteCreditUsage.total_cost' => 'required|numeric|min:0',
        ]);

        $siteCreditUsage->fill($request->get('siteCreditUsage'))->save();

        Toast::info(__('Site credit usage was saved successfully.'));

        return redirect()->route('settings.entities.site_credit_usages');
    }

    /**
     * @param SiteCreditUsage $siteCreditUsage
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     */
    public function remove(SiteCreditUsage $siteCreditUsage): RedirectResponse
    {
        $siteCreditUsage->delete();

        Toast::info(__('Site credit usage was removed successfully.'));

        return redirect()->route('settings.entities.site_credit_usages');
    }
}