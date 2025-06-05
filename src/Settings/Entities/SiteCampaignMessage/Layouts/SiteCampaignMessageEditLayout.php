<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCampaignMessage\Layouts;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Layouts\Rows;

class SiteCampaignMessageEditLayout extends Rows
{
    /**
     * Get the fields elements to be displayed.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Input::make('siteCampaignMessage.id')
                ->type('hidden'),

            TextArea::make('siteCampaignMessage.title')
                ->title(__('Title'))
                ->placeholder(__('Enter title in JSON format for multi-language support'))
                ->help(__('Example: {"ko": "한국어 제목", "en": "English Title"}'))
                ->rows(3),

            Relation::make('siteCampaignMessage.site_campaign_id')
                ->title(__('Site Campaign'))
                ->fromModel(\Techigh\CreditMessaging\Settings\Entities\SiteCampaign\SiteCampaign::class, 'title')
                ->required()
                ->help(__('Select the related site campaign')),

            Input::make('siteCampaignMessage.phone_e164')
                ->title(__('Phone Number (E164)'))
                ->placeholder(__('Enter phone number in E164 format'))
                ->help(__('예: +821012345678'))
                ->maxlength(16)
                ->pattern('\+[1-9]\d{1,14}')
                ->required(),

            Input::make('siteCampaignMessage.name')
                ->title(__('Name'))
                ->placeholder(__('Enter recipient name'))
                ->help(__('수신자 이름 (선택사항)'))
                ->maxlength(50),

            Input::make('siteCampaignMessage.kakao_result_code')
                ->title(__('Kakao Result Code'))
                ->placeholder(__('Enter kakao result code'))
                ->help(__('카카오 알림톡 결과 코드'))
                ->readonly(),

            Input::make('siteCampaignMessage.sms_result_code')
                ->title(__('SMS Result Code'))
                ->placeholder(__('Enter SMS result code'))
                ->help(__('대체 SMS 결과 코드'))
                ->readonly(),

            Input::make('siteCampaignMessage.sort_order')
                ->title(__('Sort Order'))
                ->type('number')
                ->min('0')
                ->value('0')
                ->placeholder(__('Enter sort order')),
        ];
    }
}
