<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Library;

use Ai\Application\Commands\UpdateLibraryItemCommand;
use Ai\Domain\Entities\SlideEntity;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Override;
use Presentation\Exceptions\NotFoundException;
use Presentation\Resources\Api\SlideResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/[uuid:id]', method: RequestMethod::PATCH)]
#[Route(path: '/[uuid:id]', method: RequestMethod::POST)]
class UpdateSlideRequestHandler extends LibraryApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher
    ) {
    }

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);
        $payload = $request->getParsedBody();

        $cmd = new UpdateLibraryItemCommand($request->getAttribute("id"));

        if (property_exists($payload, 'slides')) {
            $cmd->setMeta('slides', $payload->slides);
        }

        if (property_exists($payload, 'theme')) {
            $cmd->setMeta('theme', $payload->theme);
        }

        if (property_exists($payload, 'template')) {
            $cmd->setMeta('template', $payload->template);
        }

        if (property_exists($payload, 'title')) {
            $cmd->setTitle($payload->title);
        }

        try {
            /** @var SlideEntity */
            $item = $this->dispatcher->dispatch($cmd);
        } catch (LibraryItemNotFoundException $th) {
            throw new NotFoundException(
                param: 'id',
                previous: $th
            );
        }

        return new JsonResponse(
            new SlideResource($item)
        );
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'slides' => 'sometimes|array',
            'theme' => 'sometimes|string',
            'template' => 'sometimes|string',
            'title' => 'sometimes|string',
        ]);
    }
}
