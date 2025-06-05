<?php

namespace Techigh\CreditMessaging\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateWebhookSecretCommand extends Command
{
    protected $signature = 'credit-messaging:generate-webhook-secret';
    protected $description = '웹훅 시크릿을 생성합니다';

    public function handle(): int
    {
        $secret = Str::random(64);

        $this->info('웹훅 시크릿이 생성되었습니다!');
        $this->line('');
        $this->line('다음 내용을 .env 파일에 추가하세요:');
        $this->line("CREDIT_MESSAGING_WEBHOOK_SECRET={$secret}");
        $this->line('');
        $this->warn('이 시크릿을 smpp-provider와 공유해야 합니다.');

        return 0;
    }
}
