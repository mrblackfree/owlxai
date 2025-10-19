<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Ai;

use Ai\Application\Commands\GenerateSlideCommand;
use Ai\Domain\Entities\SlideEntity;
use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Ai\Domain\ValueObjects\Chunk;
use Ai\Domain\ValueObjects\Model;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Generator;
use Presentation\Exceptions\HttpException;
use Presentation\Exceptions\UnprocessableEntityException;
use Presentation\Resources\Api\SlideResource;
use Presentation\Response\Response;
use Presentation\Stream\CallbackStream;
use Presentation\Stream\Streamer;
use Presentation\Validation\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/slides', method: RequestMethod::POST)]
class SlidesApi extends AiServicesApi implements RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher,
        private Streamer $streamer
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);

        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        /** @var WorkspaceEntity */
        $ws = $request->getAttribute(WorkspaceEntity::class);

        $params = (array) $request->getParsedBody();

        $model = new Model($params['model'] ?? 'gpt-4');
        
        $cmd = new GenerateSlideCommand(
            $ws,
            $user,
            $model
        );

        $cmd->setPrompt($params['prompt'] ?? '')
            ->setParams($params);

        if (isset($params['theme'])) {
            $cmd->setTheme($params['theme']);
        }

        if (isset($params['template'])) {
            $cmd->setTemplate($params['template']);
        }

        try {
            /** @var Generator<int,Chunk,null,SlideEntity> */
            $generator = $this->dispatcher->dispatch($cmd);
        } catch (InsufficientCreditsException $th) {
            throw new HttpException(
                'Insufficient credits',
                403
            );
        }

        $resp = (new Response())
            ->withHeader('Content-Type', 'text/event-stream')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Connection', 'keep-alive')
            ->withHeader('X-Accel-Buffering', 'no');

        $stream = new CallbackStream(
            $this->callback(...),
            $generator
        );

        return $resp->withBody($stream);
    }

    private function callback(Generator $generator)
    {
        try {
            $this->streamer->stream($generator);

            /** @var SlideEntity */
            $slide = $generator->getReturn();
            $this->streamer->sendEvent('slide', new SlideResource($slide));
        } catch (ApiException $th) {
            $this->streamer->sendError($th->getMessage());
        }
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'prompt' => 'required|string',
            'model' => 'sometimes|string',
            'theme' => 'sometimes|string',
            'template' => 'sometimes|string',
        ]);
    }
}
