<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Gateways\TossPayments;

use Easy\Container\Attributes\Inject;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Billing\Infrastructure\Payments\Exceptions\PaymentException;

class Client
{
    private GuzzleClient $httpClient;
    private string $baseUrl;

    public function __construct(
        #[Inject('option.tosspayments.secret_key')]
        private ?string $secretKey = null,

        #[Inject('option.tosspayments.is_live')]
        private bool $isLive = false,
    ) {
        $this->baseUrl = $this->isLive 
            ? 'https://api.tosspayments.com/v1' 
            : 'https://api.tosspayments.com/v1';

        $this->httpClient = new GuzzleClient([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->secretKey . ':'),
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    /**
     * 결제 승인 요청
     */
    public function confirmPayment(string $paymentKey, string $orderId, int $amount): array
    {
        try {
            $response = $this->httpClient->request('POST', '/payments/confirm', [
                'json' => [
                    'paymentKey' => $paymentKey,
                    'orderId' => $orderId,
                    'amount' => $amount,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            if ($e->hasResponse()) {
                $body = json_decode($e->getResponse()->getBody()->getContents(), true);
                throw new PaymentException(
                    '토스페이먼츠 API 오류: ' . ($body['message'] ?? '알 수 없는 오류'),
                    $e->getCode(),
                    $e
                );
            }
            
            throw new PaymentException(
                '토스페이먼츠 API 연결 실패: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * 결제 조회
     */
    public function getPayment(string $paymentKey): array
    {
        try {
            $response = $this->httpClient->request('GET', '/payments/' . $paymentKey);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            if ($e->hasResponse()) {
                $body = json_decode($e->getResponse()->getBody()->getContents(), true);
                throw new PaymentException(
                    '토스페이먼츠 API 오류: ' . ($body['message'] ?? '알 수 없는 오류'),
                    $e->getCode(),
                    $e
                );
            }
            
            throw new PaymentException(
                '토스페이먼츠 API 연결 실패: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * 결제 취소
     */
    public function cancelPayment(string $paymentKey, string $cancelReason, int $cancelAmount = null): array
    {
        try {
            $data = [
                'cancelReason' => $cancelReason,
            ];

            if ($cancelAmount !== null) {
                $data['cancelAmount'] = $cancelAmount;
            }

            $response = $this->httpClient->request('POST', '/payments/' . $paymentKey . '/cancel', [
                'json' => $data,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            if ($e->hasResponse()) {
                $body = json_decode($e->getResponse()->getBody()->getContents(), true);
                throw new PaymentException(
                    '토스페이먼츠 API 오류: ' . ($body['message'] ?? '알 수 없는 오류'),
                    $e->getCode(),
                    $e
                );
            }
            
            throw new PaymentException(
                '토스페이먼츠 API 연결 실패: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * 웹훅 시그니처 검증
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $this->secretKey);
        return hash_equals($expectedSignature, $signature);
    }
}
