<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Gateways\Plisio;

use Billing\Domain\Entities\OrderEntity;
use Billing\Infrastructure\Payments\CryptoPaymentGatewayInterface;
use Billing\Infrastructure\Payments\OffsitePaymentGatewayInterface;
use Billing\Infrastructure\Payments\Exceptions\PaymentException;
use Billing\Infrastructure\Payments\Helper;
use Billing\Infrastructure\Payments\WebhookHandlerInterface;
use Easy\Container\Attributes\Inject;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Shared\Infrastructure\Atributes\BuiltInAspect;

#[BuiltInAspect]
class Plisio implements OffsitePaymentGatewayInterface, CryptoPaymentGatewayInterface
{
    public const LOOKUP_KEY = 'plisio';

    public function __construct(
        private Client $client,
        private UriFactoryInterface $factory,
        private Helper $helper,

        #[Inject('option.plisio.is_enabled')]
        private bool $isEnabled = false,

        #[Inject('option.plisio.api_key')]
        private ?string $apiKey = null,

        #[Inject('option.site.name')]
        private ?string $brandName = null,
    ) {}

    public function isEnabled(): bool
    {
        return $this->isEnabled && !empty($this->apiKey);
    }

    public function getName(): string
    {
        return 'Plisio';
    }

    public function getLogo(): string
    {
        return file_get_contents(__DIR__ . '/logo.svg');
    }

    public function getButtonBackgroundColor(): string
    {
        return '#1a1a1a';
    }

    public function getButtonTextColor(): string
    {
        return '#ffffff';
    }

    public function purchase(OrderEntity $order): UriInterface
    {
        $ws = $order->getWorkspace();
        $user = $ws->getOwner();
        
        // 금액 변환 (Plisio는 USD를 기본으로 사용)
        list($amount, $currency) = $this->helper->convert(
            $order->getTotalPrice(),
            $order->getCurrencyCode(),
            null // Plisio가 자동으로 변환 처리
        );

        // Invoice 생성 데이터
        $data = [
            'order_name' => 'Order #' . $order->getId()->getValue(),
            'order_number' => (string) $order->getId()->getValue(),
            'description' => $order->getPlan()->getTitle()->value,
            'source_amount' => number_format($amount->value / 100, 8, '.', ''), // cents to decimal
            'source_currency' => $currency->value,
            'cancel_url' => (string) $this->factory->createUri('/app/billing/checkout/cancel'),
            'callback_url' => (string) $this->factory->createUri('/webhooks/plisio'),
            'success_url' => (string) $this->factory->createUri('/app/billing/checkout/plisio/success'),
            'email' => $user->getEmail()->value,
            'plugin' => 'aikeedo',
            'version' => '1.0.0'
        ];

        // Plisio API를 통해 invoice 생성
        $response = $this->client->createTransaction($data);

        if (!$response || isset($response['status']) && $response['status'] === 'error') {
            $errorMessage = $response['data']['message'] ?? 'Failed to create Plisio invoice';
            throw new PaymentException($errorMessage);
        }

        if (empty($response['data']['invoice_url'])) {
            throw new PaymentException('Invalid response from Plisio API');
        }

        // Plisio 결제 페이지로 리다이렉트
        return $this->factory->createUri($response['data']['invoice_url']);
    }

    public function completePurchase(
        OrderEntity $order,
        array $params = []
    ): string {
        // Plisio는 웹훅을 통해 자동으로 결제를 완료하므로
        // 여기서는 invoice ID만 반환
        if (!isset($params['invoice_id'])) {
            throw new PaymentException('Missing invoice ID');
        }

        return $params['invoice_id'];
    }

    public function cancelSubscription(string $id): void
    {
        // Plisio는 일회성 결제만 지원
        throw new PaymentException('Plisio does not support subscriptions');
    }

    public function getWebhookHandler(): string|WebhookHandlerInterface
    {
        return WebhookRequestHandler::class;
    }
}
