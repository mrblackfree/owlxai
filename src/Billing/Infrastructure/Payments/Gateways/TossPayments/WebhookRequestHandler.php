<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Gateways\TossPayments;

use Billing\Application\Commands\CancelSubscriptionCommand;
use Billing\Application\Commands\CreatePaymentCommand;
use Billing\Domain\ValueObjects\PaymentStatus;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\RequestHandlers\AbstractRequestHandler;
use Presentation\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Billing\Infrastructure\Payments\WebhookHandlerInterface;
use Billing\Infrastructure\Payments\Exceptions\WebhookException;
use Shared\Infrastructure\CommandBus\Exception\CommandBusException;
use Psr\Log\LoggerInterface;

#[Route(path: '/webhooks/tosspayments', method: RequestMethod::POST)]
class WebhookRequestHandler extends AbstractRequestHandler implements 
    RequestHandlerInterface, 
    WebhookHandlerInterface
{
    public function __construct(
        private Client $client,
        private Dispatcher $dispatcher,
        private LoggerInterface $logger
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = (string) $request->getBody();
        $signature = $request->getHeaderLine('Toss-Signature');

        // 시그니처 검증
        if (!$this->client->verifyWebhookSignature($body, $signature)) {
            $this->logger->warning('토스페이먼츠 웹훅 시그니처 검증 실패');
            throw new WebhookException('Invalid webhook signature');
        }

        $data = json_decode($body, true);
        if (!$data) {
            throw new WebhookException('Invalid webhook payload');
        }

        $this->logger->info('토스페이먼츠 웹훅 수신', ['event' => $data['eventType'] ?? 'unknown']);

        try {
            switch ($data['eventType'] ?? '') {
                case 'PAYMENT_STATUS_CHANGED':
                    $this->handlePaymentStatusChanged($data['data']);
                    break;
                    
                default:
                    $this->logger->info('처리되지 않은 토스페이먼츠 웹훅 이벤트', ['event' => $data['eventType']]);
            }
        } catch (CommandBusException $e) {
            $this->logger->error('토스페이먼츠 웹훅 처리 중 오류', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw new WebhookException('Failed to process webhook', 0, $e);
        }

        return new EmptyResponse();
    }

    private function handlePaymentStatusChanged(array $payment): void
    {
        $status = $payment['status'] ?? '';
        $orderId = $payment['orderId'] ?? '';
        $paymentKey = $payment['paymentKey'] ?? '';

        // ORDER_ 접두사 제거
        if (strpos($orderId, 'ORDER_') === 0) {
            $orderId = substr($orderId, 6);
        }

        switch ($status) {
            case 'DONE':
                // 결제 완료
                $cmd = new CreatePaymentCommand(
                    $orderId,
                    PaymentStatus::COMPLETED,
                    $paymentKey,
                    'tosspayments',
                    $payment
                );
                $this->dispatcher->dispatch($cmd);
                break;

            case 'CANCELED':
            case 'PARTIAL_CANCELED':
                // 결제 취소
                $cmd = new CreatePaymentCommand(
                    $orderId,
                    PaymentStatus::REFUNDED,
                    $paymentKey,
                    'tosspayments',
                    $payment
                );
                $this->dispatcher->dispatch($cmd);
                break;

            case 'ABORTED':
            case 'EXPIRED':
                // 결제 실패
                $cmd = new CreatePaymentCommand(
                    $orderId,
                    PaymentStatus::FAILED,
                    $paymentKey,
                    'tosspayments',
                    $payment
                );
                $this->dispatcher->dispatch($cmd);
                break;

            default:
                $this->logger->info('처리되지 않은 토스페이먼츠 결제 상태', [
                    'status' => $status,
                    'orderId' => $orderId
                ]);
        }
    }
}
