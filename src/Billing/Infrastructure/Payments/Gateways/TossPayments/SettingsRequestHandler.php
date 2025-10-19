<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Gateways\TossPayments;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\RequestHandlers\Admin\AbstractAdminViewRequestHandler;
use Presentation\Response\HtmlResponse;
use Presentation\Response\JsonResponse;
use Presentation\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Option\Application\Commands\SaveOptionCommand;

#[Route(path: '/tosspayments/settings', method: RequestMethod::GET)]
#[Route(path: '/tosspayments/settings', method: RequestMethod::POST)]
class SettingsRequestHandler extends AbstractAdminViewRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            $params = (array) $request->getParsedBody();

            // 체크박스 처리
            $params['is_enabled'] = isset($params['is_enabled']) && $params['is_enabled'] === 'on';
            $params['is_live'] = isset($params['is_live']) && $params['is_live'] === 'on';

            // 옵션 저장
            $cmd = new SaveOptionCommand('tosspayments.is_enabled', $params['is_enabled']);
            $this->dispatcher->dispatch($cmd);

            $cmd = new SaveOptionCommand('tosspayments.is_live', $params['is_live']);
            $this->dispatcher->dispatch($cmd);

            if (!empty($params['client_key'])) {
                $cmd = new SaveOptionCommand('tosspayments.client_key', $params['client_key']);
                $this->dispatcher->dispatch($cmd);
            }

            if (!empty($params['secret_key'])) {
                $cmd = new SaveOptionCommand('tosspayments.secret_key', $params['secret_key']);
                $this->dispatcher->dispatch($cmd);
            }

            return new JsonResponse([
                'message' => '설정이 저장되었습니다.'
            ]);
        }

        return new HtmlResponse($this->render('@tosspayments/settings.twig'));
    }
}
