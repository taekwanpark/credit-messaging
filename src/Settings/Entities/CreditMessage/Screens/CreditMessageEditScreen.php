<?php

namespace Techigh\CreditMessaging\Settings\Entities\CreditMessage\Screens;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Color;
use Techigh\CreditMessaging\Settings\Entities\CreditMessage\CreditMessage;
use Techigh\CreditMessaging\Settings\Entities\CreditMessage\Layouts\CreditMessageEditLayout;
use App\Settings\Extends\OrbitLayout;

class CreditMessageEditScreen extends Screen
{
    /**
     * @var CreditMessage
     */
    public $creditMessage;

    /**
     * Query data.
     *
     * @param CreditMessage $creditMessage
     *
     * @return array
     */
    public function query(CreditMessage $creditMessage): array
    {
        return [
            'creditMessage' => $creditMessage,
        ];
    }

    /**
     * The name is displayed on the user's screen.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->creditMessage->exists ? __('Edit Credit Message') . ' - ' . $this->creditMessage->getTitleForLocale() : __('Create Credit Message');
    }

    /**
     * The description is displayed on the user's screen under the heading
     *
     * @return string|null
     */
    public function description(): ?string
    {
        return __('Manage credit messages for sending');
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
                ->confirm(__('Are you sure you want to delete this credit message?'))
                ->method('remove')
                ->canSee($this->creditMessage->exists),

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
            OrbitLayout::block(CreditMessageEditLayout::class)
                ->title(__('Credit Message Information'))
                ->description(__('Configure message settings and content'))
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
            'settings.entities.credit_messages.create',
            'settings.entities.credit_messages.edit',
        ];
    }

    /**
     * @param CreditMessage $creditMessage
     * @param Request    $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(CreditMessage $creditMessage, Request $request): RedirectResponse
    {
        $request->validate([
            'creditMessage.site_id' => 'required|string|max:255',
            'creditMessage.title' => 'required|string|max:255',
            'creditMessage.message_content' => 'required|string',
            'creditMessage.message_type' => 'required|string|in:alimtalk,sms,lms,mms',
            'creditMessage.routing_strategy' => 'required|string|in:alimtalk_first,sms_only,cost_optimized',
        ]);

        $creditMessage->fill($request->get('creditMessage'))->save();

        Toast::info(__('Credit message was saved successfully.'));

        return redirect()->route('settings.entities.credit_messages');
    }

    /**
     * @param CreditMessage $creditMessage
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     */
    public function remove(CreditMessage $creditMessage): RedirectResponse
    {
        $creditMessage->delete();

        Toast::info(__('Credit message was removed successfully.'));

        return redirect()->route('settings.entities.credit_messages');
    }
}