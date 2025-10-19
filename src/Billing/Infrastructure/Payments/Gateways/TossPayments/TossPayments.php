<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Gateways\TossPayments;

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
class TossPayments implements OffsitePaymentGatewayInterface
{
    public const LOOKUP_KEY = 'tosspayments';

    public function __construct(
        private Client $client,
        private UriFactoryInterface $factory,
        private Helper $helper,

        #[Inject('option.tosspayments.is_enabled')]
        private bool $isEnabled = false,

        #[Inject('option.tosspayments.client_key')]
        private ?string $clientKey = null,

        #[Inject('option.tosspayments.secret_key')]
        private ?string $secretKey = null,

        #[Inject('option.tosspayments.is_live')]
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
        return '토스페이먼츠 (Toss Payments)';
    }

    public function getLogo(): string
    {
        return file_get_contents(__DIR__ . '/logo.svg');
    }

    public function getButtonBackgroundColor(): string
    {
        return '#0064FF';
    }

    public function getButtonTextColor(): string
    {
        return '#ffffff';
    }

    public function purchase(OrderEntity $order): UriInterface
    {
        // 토스페이먼츠는 구독 결제를 제한적으로 지원하므로 일회성 결제만 구현
        if ($order->getPlan()->getBillingCycle()->isRecurring()) {
            throw new PaymentException('토스페이먼츠는 현재 구독 결제를 지원하지 않습니다.');
        }

        return $this->createOrder($order);
    }

    public function completePurchase(
        OrderEntity $order,
        array $params = []
    ): string {
        if (!isset($params['payment_key'])) {
            throw new PaymentException('결제 키가 필요합니다.');
        }

        if (!isset($params['order_id'])) {
            throw new PaymentException('주문 ID가 필요합니다.');
        }

        if (!isset($params['amount'])) {
            throw new PaymentException('결제 금액이 필요합니다.');
        }

        // 결제 승인 요청
        $response = $this->client->confirmPayment(
            $params['payment_key'],
            $params['order_id'],
            (int) $params['amount']
        );

        if ($response['status'] !== 'DONE') {
            throw new PaymentException('결제 승인에 실패했습니다: ' . ($response['message'] ?? '알 수 없는 오류'));
        }

        return $response['paymentKey'];
    }

    public function cancelSubscription(string $id): void
    {
        // 토스페이먼츠는 구독 취소를 지원하지 않음
        throw new PaymentException('토스페이먼츠는 구독 취소를 지원하지 않습니다.');
    }

    public function getWebhookHandler(): string|WebhookHandlerInterface
    {
        return WebhookRequestHandler::class;
    }

    private function createOrder(OrderEntity $order): UriInterface
    {
        $ws = $order->getWorkspace();
        $user = $ws->getOwner();
        
        // 금액을 원화로 변환 (토스페이먼츠는 KRW만 지원)
        list($amount, $currency) = $this->helper->convert(
            $order->getTotalPrice(),
            $order->getCurrencyCode(),
            'KRW'
        );

        if ($currency->value !== 'KRW') {
            throw new PaymentException('토스페이먼츠는 한국 원화(KRW)만 지원합니다.');
        }

        // 토스페이먼츠 결제 페이지로 리다이렉트
        // 이 페이지에서 토스페이먼츠 SDK를 사용하여 결제를 진행
        $params = [
            'order_id' => (string) $order->getId()->getValue(),
            'gateway' => 'tosspayments'
        ];
        
        return $this->factory->createUri('/app/billing/checkout/process?' . http_build_query($params));
    }
}
