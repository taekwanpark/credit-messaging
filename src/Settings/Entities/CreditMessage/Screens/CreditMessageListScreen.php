<?php

namespace Techigh\CreditMessaging\Settings\Entities\CreditMessage\Screens;

use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;
use Techigh\CreditMessaging\Settings\Entities\CreditMessage\CreditMessage;
use Techigh\CreditMessaging\Settings\Entities\CreditMessage\Layouts\CreditMessageListLayout;

class CreditMessageListScreen extends Screen
{
    /**
     * Query data.
     *
     * @return array
     */
    public function query(): array
    {
        return [
            'creditMessages' => CreditMessage::filters()
                ->defaultSort('id', 'desc')
                ->paginate(),
        ];
    }

    /**
     * The name is displayed on the user's screen.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return __('Credit Messages');
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
            Link::make(__('Add'))
                ->icon('plus')
                ->route('settings.entities.credit_messages.create'),
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
            CreditMessageListLayout::class,
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
            'settings.entities.credit_messages',
        ];
    }

    /**
     * @param Request $request
     */
    public function remove(Request $request): void
    {
        CreditMessage::findOrFail($request->get('id'))->delete();

        Toast::info(__('Credit message was removed successfully.'));
    }
}