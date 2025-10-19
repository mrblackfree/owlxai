<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Gateways\Payoneer;

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

#[Route(path: '/webhooks/payoneer', method: RequestMethod::POST)]
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
        $signature = $request->getHeaderLine('X-Payoneer-Signature');
        $timestamp = $request->getHeaderLine('X-Payoneer-Timestamp');

        // 시그니처 검증
        if (!$this->client->verifyWebhookSignature($body, $signature, $timestamp)) {
            $this->logger->warning('Payoneer webhook signature verification failed');
            throw new WebhookException('Invalid webhook signature');
        }

        $data = json_decode($body, true);
        if (!$data) {
            throw new WebhookException('Invalid webhook payload');
        }

        $this->logger->info('Payoneer webhook received', ['event' => $data['eventType'] ?? 'unknown']);

        try {
            switch ($data['eventType'] ?? '') {
                case 'charge.succeeded':
                    $this->handleChargeSucceeded($data['data']);
                    break;
                    
                case 'charge.failed':
                    $this->handleChargeFailed($data['data']);
                    break;
                    
                case 'charge.refunded':
                    $this->handleChargeRefunded($data['data']);
                    break;
                    
                default:
                    $this->logger->info('Unhandled Payoneer webhook event', ['event' => $data['eventType']]);
            }
        } catch (CommandBusException $e) {
            $this->logger->error('Payoneer webhook processing error', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw new WebhookException('Failed to process webhook', 0, $e);
        }

        return new EmptyResponse();
    }

    private function handleChargeSucceeded(array $charge): void
    {
        $orderId = $charge['metadata']['order_id'] ?? null;
        if (!$orderId) {
            $this->logger->warning('Payoneer charge without order_id', ['charge' => $charge]);
            return;
        }

        $cmd = new CreatePaymentCommand(
            $orderId,
            PaymentStatus::COMPLETED,
            $charge['id'],
            'payoneer',
            $charge
        );
        $this->dispatcher->dispatch($cmd);
    }

    private function handleChargeFailed(array $charge): void
    {
        $orderId = $charge['metadata']['order_id'] ?? null;
        if (!$orderId) {
            $this->logger->warning('Payoneer charge without order_id', ['charge' => $charge]);
            return;
        }

        $cmd = new CreatePaymentCommand(
            $orderId,
            PaymentStatus::FAILED,
            $charge['id'],
            'payoneer',
            $charge
        );
        $this->dispatcher->dispatch($cmd);
    }

    private function handleChargeRefunded(array $charge): void
    {
        $orderId = $charge['metadata']['order_id'] ?? null;
        if (!$orderId) {
            $this->logger->warning('Payoneer charge without order_id', ['charge' => $charge]);
            return;
        }

        $cmd = new CreatePaymentCommand(
            $orderId,
            PaymentStatus::REFUNDED,
            $charge['id'],
            'payoneer',
            $charge
        );
        $this->dispatcher->dispatch($cmd);
    }
}
