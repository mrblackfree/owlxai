<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Gateways\Payoneer;

use Billing\Domain\Entities\OrderEntity;
use Billing\Infrastructure\Payments\OffsitePaymentGatewayInterface;
use Billing\Infrastructure\Payments\Exceptions\PaymentException;
use Billing\Infrastructure\Payments\Helper;
use Billing\Infrastructure\Payments\WebhookHandlerInterface;
use Easy\Container\Attributes\Inject;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Shared\Infrastructure\Atributes\BuiltInAspect;

#[BuiltInAspect]
class Payoneer implements OffsitePaymentGatewayInterface
{
    public const LOOKUP_KEY = 'payoneer';

    public function __construct(
        private Client $client,
        private UriFactoryInterface $factory,
        private Helper $helper,

        #[Inject('option.payoneer.is_enabled')]
        private bool $isEnabled = false,

        #[Inject('option.payoneer.store_id')]
        private ?string $storeId = null,

        #[Inject('option.payoneer.client_id')]
        private ?string $clientId = null,

        #[Inject('option.payoneer.client_secret')]
        private ?string $clientSecret = null,

        #[Inject('option.payoneer.is_live')]
        private bool $isLive = false,

        #[Inject('option.site.name')]
        private ?string $brandName = null,
    ) {}

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function getName(): string
    {
        return 'Payoneer Checkout';
    }

    public function getLogo(): string
    {
        return file_get_contents(__DIR__ . '/logo.svg');
    }

    public function getButtonBackgroundColor(): string
    {
        return '#FF6900';
    }

    public function getButtonTextColor(): string
    {
        return '#ffffff';
    }

    public function purchase(OrderEntity $order): UriInterface
    {
        // Payoneer Checkout 세션 생성
        return $this->createCheckoutSession($order);
    }

    public function completePurchase(
        OrderEntity $order,
        array $params = []
    ): string {
        if (!isset($params['charge_id'])) {
            throw new PaymentException('Missing charge ID');
        }

        // Payoneer에서 결제 정보 확인
        $charge = $this->client->getCharge($params['charge_id']);

        if ($charge['status'] !== 'CHARGED') {
            throw new PaymentException('Payment not completed');
        }

        // 주문 ID 확인
        if ($charge['metadata']['order_id'] !== (string) $order->getId()->getValue()) {
            throw new PaymentException('Order ID mismatch');
        }

        return $charge['id'];
    }

    public function cancelSubscription(string $id): void
    {
        // Payoneer는 구독 관리를 직접 지원하지 않으므로
        // 일회성 결제만 지원
        throw new PaymentException('Payoneer does not support subscription cancellation');
    }

    public function getWebhookHandler(): string|WebhookHandlerInterface
    {
        return WebhookRequestHandler::class;
    }

    private function createCheckoutSession(OrderEntity $order): UriInterface
    {
        $ws = $order->getWorkspace();
        $user = $ws->getOwner();
        
        // 금액 변환
        list($amount, $currency) = $this->helper->convert(
            $order->getTotalPrice(),
            $order->getCurrencyCode(),
            null // Payoneer는 다양한 통화 지원
        );

        // 체크아웃 세션 생성
        $session = $this->client->createCheckoutSession([
            'amount' => [
                'value' => (string) ($amount->value / 100), // Payoneer는 decimal 형식 사용
                'currency' => $currency->value,
            ],
            'customer' => [
                'email' => $user->getEmail()->value,
                'firstName' => $user->getFirstName()->value,
                'lastName' => $user->getLastName()->value,
            ],
            'metadata' => [
                'order_id' => (string) $order->getId()->getValue(),
            ],
            'successUrl' => (string) $this->factory->createUri('/app/billing/checkout/payoneer/success'),
            'cancelUrl' => (string) $this->factory->createUri('/app/billing/checkout/cancel'),
            'notificationUrl' => (string) $this->factory->createUri('/webhooks/payoneer'),
            'products' => [
                [
                    'name' => $order->getPlan()->getTitle()->value,
                    'amount' => [
                        'value' => (string) ($amount->value / 100),
                        'currency' => $currency->value,
                    ],
                    'quantity' => 1,
                ]
            ],
            'locale' => 'ko_KR', // 한국어 지원
        ]);

        // Payoneer 체크아웃 페이지로 리다이렉트
        return $this->factory->createUri($session['checkoutUrl']);
    }
}
