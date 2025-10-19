<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App\Billing;

use Billing\Application\Commands\ReadOrderCommand;
use Billing\Domain\Entities\OrderEntity;
use Billing\Infrastructure\Payments\PaymentGatewayFactoryInterface;
use Billing\Infrastructure\Payments\Gateways\TossPayments\TossPayments;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Easy\Container\Attributes\Inject;

#[Route(path: '/billing/checkout/process', method: RequestMethod::GET)]
class ProcessTossPaymentsRequestHandler extends BillingView implements RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private PaymentGatewayFactoryInterface $factory,
        
        #[Inject('option.tosspayments.client_key')]
        private ?string $clientKey = null,
        
        #[Inject('option.tosspayments.is_live')]
        private bool $isLive = false,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        
        if (!isset($params['order_id']) || !isset($params['gateway']) || $params['gateway'] !== 'tosspayments') {
            return new RedirectResponse('/app/billing');
        }

        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);
        
        // 주문 정보 조회
        $cmd = new ReadOrderCommand($params['order_id']);
        $order = $this->dispatcher->dispatch($cmd);
        
        if (!$order instanceof OrderEntity) {
            return new RedirectResponse('/app/billing');
        }
        
        // 현재 사용자의 주문인지 확인
        if ($order->getWorkspace()->getOwner()->getId()->getValue() !== $user->getId()->getValue()) {
            return new RedirectResponse('/app/billing');
        }
        
        // 토스페이먼츠에 필요한 정보 준비
        $ws = $order->getWorkspace();
        $orderUser = $ws->getOwner();
        
        // 주문 ID 생성 (토스페이먼츠는 특정 형식을 요구)
        $orderId = 'ORDER_' . $order->getId()->getValue();
        
        $data = [
            'order' => $order,
            'orderId' => $orderId,
            'amount' => $order->getTotalPrice()->value,
            'orderName' => $order->getPlan()->getTitle()->value,
            'customerName' => $orderUser->getFirstName()->value . ' ' . $orderUser->getLastName()->value,
            'customerEmail' => $orderUser->getEmail()->value,
            'clientKey' => $this->clientKey,
            'isLive' => $this->isLive,
            'successUrl' => (string) $request->getUri()->withPath('/app/billing/checkout/tosspayments/complete'),
            'failUrl' => (string) $request->getUri()->withPath('/app/billing/checkout/cancel'),
        ];
        
        return new ViewResponse('@tosspayments/process', $data);
    }
}
