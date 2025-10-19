<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App\Billing;

use Billing\Application\Commands\CompleteOrderCommand;
use Billing\Application\Commands\ReadOrderCommand;
use Billing\Domain\Entities\OrderEntity;
use Billing\Infrastructure\Payments\PaymentGatewayFactoryInterface;
use Billing\Infrastructure\Payments\Gateways\TossPayments\TossPayments;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Response\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\CommandBusException;
use User\Domain\Entities\UserEntity;

#[Route(path: '/billing/checkout/tosspayments/complete', method: RequestMethod::GET)]
class CompleteTossPaymentsRequestHandler extends BillingView implements RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private PaymentGatewayFactoryInterface $factory,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        
        // 토스페이먼츠 필수 파라미터 확인
        if (!isset($params['paymentKey']) || !isset($params['orderId']) || !isset($params['amount'])) {
            return new RedirectResponse('/app/billing?error=invalid_parameters');
        }

        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);
        
        // ORDER_ 접두사 제거
        $orderId = $params['orderId'];
        if (strpos($orderId, 'ORDER_') === 0) {
            $orderId = substr($orderId, 6);
        }
        
        try {
            // 주문 정보 조회
            $cmd = new ReadOrderCommand($orderId);
            $order = $this->dispatcher->dispatch($cmd);
            
            if (!$order instanceof OrderEntity) {
                return new RedirectResponse('/app/billing?error=order_not_found');
            }
            
            // 현재 사용자의 주문인지 확인
            if ($order->getWorkspace()->getOwner()->getId()->getValue() !== $user->getId()->getValue()) {
                return new RedirectResponse('/app/billing?error=unauthorized');
            }
            
            // 토스페이먼츠 게이트웨이 가져오기
            $gateway = $this->factory->create(TossPayments::LOOKUP_KEY);
            
            // 결제 완료 처리
            $params['payment_key'] = $params['paymentKey'];
            $params['order_id'] = $params['orderId'];
            $referenceId = $gateway->completePurchase($order, $params);
            
            // 주문 완료 처리
            $cmd = new CompleteOrderCommand(
                $order,
                TossPayments::LOOKUP_KEY,
                $referenceId
            );
            $this->dispatcher->dispatch($cmd);
            
            // 성공 페이지로 리다이렉트
            return new RedirectResponse('/app/billing/orders/' . $order->getId()->getValue());
            
        } catch (CommandBusException $e) {
            return new RedirectResponse('/app/billing?error=payment_failed&message=' . urlencode($e->getMessage()));
        } catch (\Exception $e) {
            return new RedirectResponse('/app/billing?error=payment_failed');
        }
    }
}
