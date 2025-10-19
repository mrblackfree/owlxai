<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Gateways\Plisio;

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

#[Route(path: '/webhooks/plisio', method: RequestMethod::POST)]
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
        $data = (array) $request->getParsedBody();

        // 콜백 데이터 검증
        if (!$this->client->verifyCallbackData($data)) {
            $this->logger->warning('Plisio webhook signature verification failed');
            throw new WebhookException('Invalid webhook signature');
        }

        $this->logger->info('Plisio webhook received', [
            'status' => $data['status'] ?? 'unknown',
            'order_number' => $data['order_number'] ?? null
        ]);

        try {
            $this->processWebhook($data);
        } catch (CommandBusException $e) {
            $this->logger->error('Plisio webhook processing error', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw new WebhookException('Failed to process webhook', 0, $e);
        }

        return new EmptyResponse();
    }

    private function processWebhook(array $data): void
    {
        $status = $data['status'] ?? '';
        $orderId = $data['order_number'] ?? null;
        $invoiceId = $data['id'] ?? '';

        if (!$orderId) {
            $this->logger->warning('Plisio webhook without order_number', ['data' => $data]);
            return;
        }

        // Plisio 상태 매핑
        $paymentStatus = match ($status) {
            'completed', 'mismatch' => PaymentStatus::COMPLETED, // mismatch = overpaid
            'expired', 'cancelled', 'error' => PaymentStatus::FAILED,
            'pending', 'new' => PaymentStatus::PENDING,
            default => null
        };

        if ($paymentStatus === null) {
            $this->logger->info('Unhandled Plisio payment status', [
                'status' => $status,
                'order_id' => $orderId
            ]);
            return;
        }

        // 결제 상태 업데이트
        $cmd = new CreatePaymentCommand(
            $orderId,
            $paymentStatus,
            $invoiceId,
            'plisio',
            $data
        );
        
        $this->dispatcher->dispatch($cmd);
    }
}
