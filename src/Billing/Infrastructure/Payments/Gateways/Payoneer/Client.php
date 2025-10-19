<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Gateways\Payoneer;

use Easy\Container\Attributes\Inject;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Billing\Infrastructure\Payments\Exceptions\PaymentException;

class Client
{
    private GuzzleClient $httpClient;
    private string $baseUrl;
    private ?string $accessToken = null;
    private ?int $tokenExpiry = null;

    public function __construct(
        #[Inject('option.payoneer.store_id')]
        private ?string $storeId = null,

        #[Inject('option.payoneer.client_id')]
        private ?string $clientId = null,

        #[Inject('option.payoneer.client_secret')]
        private ?string $clientSecret = null,

        #[Inject('option.payoneer.is_live')]
        private bool $isLive = false,
    ) {
        $this->baseUrl = $this->isLive 
            ? 'https://api.payoneer.com' 
            : 'https://api.sandbox.payoneer.com';

        $this->httpClient = new GuzzleClient([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
        ]);
    }

    /**
     * OAuth2 액세스 토큰 획득
     */
    private function getAccessToken(): string
    {
        // 토큰이 유효한 경우 재사용
        if ($this->accessToken && $this->tokenExpiry && $this->tokenExpiry > time()) {
            return $this->accessToken;
        }

        try {
            $response = $this->httpClient->request('POST', '/v4/oauth2/token', [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            $this->accessToken = $data['access_token'];
            $this->tokenExpiry = time() + ($data['expires_in'] - 60); // 60초 여유
            
            return $this->accessToken;
        } catch (GuzzleException $e) {
            throw new PaymentException(
                'Failed to obtain Payoneer access token: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * 체크아웃 세션 생성
     */
    public function createCheckoutSession(array $data): array
    {
        try {
            $response = $this->httpClient->request('POST', '/v4/checkouts', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'Content-Type' => 'application/json',
                    'X-Store-Id' => $this->storeId,
                ],
                'json' => $data,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            if ($e->hasResponse()) {
                $body = json_decode($e->getResponse()->getBody()->getContents(), true);
                throw new PaymentException(
                    'Payoneer API error: ' . ($body['message'] ?? 'Unknown error'),
                    $e->getCode(),
                    $e
                );
            }
            
            throw new PaymentException(
                'Payoneer API connection failed: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * 결제 정보 조회
     */
    public function getCharge(string $chargeId): array
    {
        try {
            $response = $this->httpClient->request('GET', '/v4/charges/' . $chargeId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'X-Store-Id' => $this->storeId,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            if ($e->hasResponse()) {
                $body = json_decode($e->getResponse()->getBody()->getContents(), true);
                throw new PaymentException(
                    'Payoneer API error: ' . ($body['message'] ?? 'Unknown error'),
                    $e->getCode(),
                    $e
                );
            }
            
            throw new PaymentException(
                'Payoneer API connection failed: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * 웹훅 시그니처 검증
     */
    public function verifyWebhookSignature(string $payload, string $signature, string $timestamp): bool
    {
        $message = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $message, $this->clientSecret);
        
        return hash_equals($expectedSignature, $signature);
    }
}
