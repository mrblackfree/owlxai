<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Gateways\Payoneer;

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

#[Route(path: '/payoneer/settings', method: RequestMethod::GET)]
#[Route(path: '/payoneer/settings', method: RequestMethod::POST)]
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
            $cmd = new SaveOptionCommand('payoneer.is_enabled', $params['is_enabled']);
            $this->dispatcher->dispatch($cmd);

            $cmd = new SaveOptionCommand('payoneer.is_live', $params['is_live']);
            $this->dispatcher->dispatch($cmd);

            if (!empty($params['store_id'])) {
                $cmd = new SaveOptionCommand('payoneer.store_id', $params['store_id']);
                $this->dispatcher->dispatch($cmd);
            }

            if (!empty($params['client_id'])) {
                $cmd = new SaveOptionCommand('payoneer.client_id', $params['client_id']);
                $this->dispatcher->dispatch($cmd);
            }

            if (!empty($params['client_secret'])) {
                $cmd = new SaveOptionCommand('payoneer.client_secret', $params['client_secret']);
                $this->dispatcher->dispatch($cmd);
            }

            return new JsonResponse([
                'message' => 'Settings saved successfully.'
            ]);
        }

        return new HtmlResponse($this->render('@payoneer/settings.twig'));
    }
}
