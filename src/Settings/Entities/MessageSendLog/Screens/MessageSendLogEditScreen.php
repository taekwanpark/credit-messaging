<?php

namespace Techigh\CreditMessaging\Settings\Entities\MessageSendLog\Screens;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Color;
use Techigh\CreditMessaging\Settings\Entities\MessageSendLog\MessageSendLog;
use Techigh\CreditMessaging\Settings\Entities\MessageSendLog\Layouts\MessageSendLogEditLayout;
use App\Settings\Extends\OrbitLayout;

class MessageSendLogEditScreen extends Screen
{
    /**
     * @var MessageSendLog
     */
    public $messageSendLog;

    /**
     * Query data.
     *
     * @param MessageSendLog $messageSendLog
     *
     * @return array
     */
    public function query(MessageSendLog $messageSendLog): array
    {
        return [
            'messageSendLog' => $messageSendLog,
        ];
    }

    /**
     * The name is displayed on the user's screen.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->messageSendLog->exists ? __('Edit Message Send Log') . ' - ' . $this->messageSendLog->id : __('Create Message Send Log');
    }

    /**
     * The description is displayed on the user's screen under the heading
     *
     * @return string|null
     */
    public function description(): ?string
    {
        return __('Track message sending and settlement status');
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
                ->confirm(__('Are you sure you want to delete this send log?'))
                ->method('remove')
                ->canSee($this->messageSendLog->exists),

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
            OrbitLayout::block(MessageSendLogEditLayout::class)
                ->title(__('Message Send Log Information'))
                ->description(__('Track message delivery and settlement'))
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
            'settings.entities.message_send_logs.create',
            'settings.entities.message_send_logs.edit',
        ];
    }

    /**
     * @param MessageSendLog $messageSendLog
     * @param Request    $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(MessageSendLog $messageSendLog, Request $request): RedirectResponse
    {
        $request->validate([
            'messageSendLog.total_count' => 'required|integer|min:0',
            'messageSendLog.success_count' => 'required|integer|min:0',
            'messageSendLog.failed_count' => 'required|integer|min:0',
        ]);

        $messageSendLog->fill($request->get('messageSendLog'))->save();

        Toast::info(__('Message send log was saved successfully.'));

        return redirect()->route('settings.entities.message_send_logs');
    }

    /**
     * @param MessageSendLog $messageSendLog
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     */
    public function remove(MessageSendLog $messageSendLog): RedirectResponse
    {
        $messageSendLog->delete();

        Toast::info(__('Message send log was removed successfully.'));

        return redirect()->route('settings.entities.message_send_logs');
    }
}