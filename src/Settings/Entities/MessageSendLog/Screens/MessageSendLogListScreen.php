<?php

namespace Techigh\CreditMessaging\Settings\Entities\MessageSendLog\Screens;

use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;
use Techigh\CreditMessaging\Settings\Entities\MessageSendLog\MessageSendLog;
use Techigh\CreditMessaging\Settings\Entities\MessageSendLog\Layouts\MessageSendLogListLayout;

class MessageSendLogListScreen extends Screen
{
    /**
     * Query data.
     *
     * @return array
     */
    public function query(): array
    {
        return [
            'messageSendLogs' => MessageSendLog::filters()
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
        return __('Message Send Logs');
    }

    /**
     * The description is displayed on the user's screen under the heading
     *
     * @return string|null
     */
    public function description(): ?string
    {
        return __('Track message sending and settlement logs');
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
                ->route('settings.entities.message_send_logs.create'),
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
            MessageSendLogListLayout::class,
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
            'settings.entities.message_send_logs',
        ];
    }

    /**
     * @param Request $request
     */
    public function remove(Request $request): void
    {
        MessageSendLog::findOrFail($request->get('id'))->delete();

        Toast::info(__('Message send log was removed successfully.'));
    }
}