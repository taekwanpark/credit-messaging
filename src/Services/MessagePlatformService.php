<?php

declare(strict_types=1);

namespace Techigh\CreditMessaging\Services;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Techigh\CreditMessaging\Settings\Entities\SiteCampaign\SiteCampaign;
use Techigh\CreditMessaging\Settings\Entities\SiteCampaignMessage\SiteCampaignMessage;

class MessagePlatformService
{
    protected PendingRequest $client;
    protected string $baseUrl;
    protected string $apiUrl;
    protected array $headers;
    protected string $accessKey;
    protected string $secretKey;
    protected string|null $senderKey;
    protected string|null $kakaoSenderKey;
    protected string $token;
    protected string $version;

    public function __construct()
    {
        $this->initializeConfig()
            ->initializeHeaders()
            ->initializeClient()
            ->authenticate()
            ->updateClientWithToken();
    }

    /**
     * 설정 초기화
     */
    private function initializeConfig(): static
    {
        $config = config('credit-messaging.message_platform');

        $this->baseUrl = $config['url'];
        $this->version = $config['version'];
        $this->accessKey = $config['access_key'];
        $this->secretKey = $config['secret_key'];
        $this->senderKey = $config['sms_sender_key'];
        $this->kakaoSenderKey = $config['kakao_sender_key'];

        $this->apiUrl = "{$this->baseUrl}/api/{$this->version}";

        return $this;
    }

    /**
     * 헤더 초기화
     */
    private function initializeHeaders(): static
    {
        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        return $this;
    }

    /**
     * HTTP 클라이언트 초기화
     */
    private function initializeClient(): static
    {
        //            ->timeout(config('credit-messaging.webhook.timeout', 30))
        //            ->retry(
        //                config('credit-messaging.webhook.retry_attempts', 3),
        //                config('credit-messaging.webhook.retry_delay', 300)
        //            );
        $this->client = Http::withHeaders($this->headers);
        return $this;
    }

    /**
     * 인증 정보 유효성 검사
     */
    private function validateCredentials(): bool
    {
        return !empty($this->accessKey) && !empty($this->secretKey);
    }


    /**
     * create basic Auth
     */
    private function makeBasicAuthorization(): string
    {
        return 'Basic ' . base64_encode(sprintf('%s:%s', $this->accessKey, $this->secretKey));
    }


    /**
     * 인증 토큰 요청
     */
    private function authenticate(): static
    {
        if (!$this->validateCredentials()) {
            throw new Exception(__('메시지 플랫폼 인증 정보가 유효하지 않습니다.'));
        }

        try {
            $response = $this->client
                ->replaceHeaders(
                    $this->headers + [
                        'Authorization' => $this->makeBasicAuthorization()
                    ]
                )
                ->post("{$this->apiUrl}/token");

            if ($response->failed()) {
                $error = $response->json('message') ?? __('토큰 발급에 실패했습니다.');
                throw new Exception($error);
            }

            $this->token = $response->json('data.token') ?? throw new Exception(__('토큰을 받지 못했습니다.'));
        } catch (Exception $e) {
            throw new Exception(__('메시지 플랫폼 인증에 실패했습니다: :error', ['error' => $e->getMessage()]));
        }

        return $this;
    }

    /**
     * 토큰을 포함한 클라이언트 헤더 업데이트
     */
    private function updateClientWithToken(): static
    {
        $this->client = $this->client->withToken(base64_encode($this->token));
        return $this;
    }

    // --------------------------------------------------------------------------------------------------------------------

    /**
     * 토큰 유효성 검사
     */
    private function validateToken(): bool
    {
        return !empty($this->token);
    }

    private function makeBearerAuthorization(): string
    {
        return 'Bearer ' . base64_encode($this->token);
    }

    /**
     * 메시지 플랫폼에 발송 요청
     */
    public function requestToMessagePlatform(SiteCampaign $campaign, \Illuminate\Database\Eloquent\Collection $messages, array $webhookConfig = []): array
    {
        if (!$this->validateToken()) {
            throw new Exception(__('유효하지 않은 토큰입니다.'));
        }

        try {
            $payload = $this->buildPayload($campaign, $messages);

            // 웹훅 정보 추가
            $payload['webhooks'] = $webhookConfig;

            $response = $this->client->replaceHeaders(
                $this->headers + [
                    'Authorization' => $this->makeBearerAuthorization(),
                ]
            )->post("{$this->apiUrl}/notices/send", $payload);

            if ($response->failed()) {
                $errorData = $response->json() ?? [];
                $errorMessage = $errorData['message'] ?? __('발송 요청에 실패했습니다.');

                throw new Exception($errorMessage);
            }

            return $response->json();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 발신 프로필 검사
     */
    private function validateSenderKeys(): bool
    {
        return !empty($this->kakaoSenderKey) && !empty($this->senderKey);
    }

    /**
     * 발송 페이로드 구성
     */
    private function buildPayload(SiteCampaign $campaign, \Illuminate\Database\Eloquent\Collection $messages): array
    {

        if (!$this->validateSenderKeys()) {
            throw new Exception(__('발신 프로필 정보가 유효하지 않습니다.'));
        }
        // temp
        return [
            'kakaoSenderKey' => $this->kakaoSenderKey,
            'senderKey' => $this->senderKey,
            'replaceSms' => $campaign->replace_sms ? 'Y' : 'N',
            'templateCode' => $campaign->template_code,
            'contacts' => $this->formatContacts($messages),
            'at' => $campaign->send_at->toISOString(),
            'smsSubject' => $campaign->sms_title,
            'smsContent' => $campaign->sms_content,
            'scheduleType' => Carbon::parse($campaign->send_at)->isFuture() ? 'DIRECTLY' : 'RESERVED'
        ];
    }

    /**
     * 연락처 정보 포맷팅
     */
    private function formatContacts(\Illuminate\Database\Eloquent\Collection $messages): array
    {
        $formatted = [];
        $messages->map(function ($message) use (&$formatted) {
            /** @var SiteCampaignMessage $message */
            $formattedContact = [
                'contact' => $message->phone_e164,
                'name' => $message->name,
            ];
            $formatted[] = $formattedContact;
        });

        return $formatted;
    }
}
