<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App;

use Ai\Application\Commands\ListLibraryItemsCommand;
use Ai\Application\Commands\ReadLibraryItemCommand;
use Ai\Domain\Entities\SlideEntity;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Ai\Domain\Services\AiServiceFactoryInterface;
use Ai\Domain\Slide\SlideServiceInterface;
use Ai\Domain\ValueObjects\ItemType;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Middleware;
use Easy\Router\Attributes\Route;
use Iterator;
use Presentation\AccessControls\LibraryItemAccessControl;
use Presentation\AccessControls\Permission;
use Presentation\Middlewares\ViewMiddleware;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Preset\Application\Commands\ListPresetsCommand;
use Preset\Domain\Entities\PresetEntity;
use Preset\Domain\ValueObjects\Type;
use Preset\Domain\ValueObjects\Status;
use Preset\Infrastructure\Placeholder\ParserService;
use Preset\Infrastructure\Placeholder\PlaceholderFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\Config\OptionResolver;
use Shared\Infrastructure\Services\ModelRegistry;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Middleware(ViewMiddleware::class)]
#[Route(path: '/slides/[uuid:id]?', method: RequestMethod::GET)]
#[Route(path: '/slides', method: RequestMethod::GET)]
class SlideView extends AppView implements RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private ParserService $parser,
        private PlaceholderFactory $factory,
        private LibraryItemAccessControl $ac,
        private AiServiceFactoryInterface $aiFactory,
        private ModelRegistry $modelRegistry,
        private OptionResolver $resolver,

            #[Inject('option.features.slides.is_enabled')]
            private ?string $isEnabled = null
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Redirect if slides feature is disabled or not configured
        if ($this->isEnabled === 'false' || $this->isEnabled === null) {
            return new RedirectResponse('/app');
        }

        $data = [];
        $data['services'] = $this->getServices($request);
        $data['presets'] = $this->getPresets($request);

        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        $id = $request->getAttribute('id');

        if (!$id) {
            // Show slides list
            $data['slides'] = $this->getSlides($request);
            
            return new ViewResponse(
                '/templates/app/slides/index.twig',
                $data
            );
        }

        // Show specific slide
        try {
            $cmd = new ReadLibraryItemCommand($id);
            
            /** @var SlideEntity */
            $slide = $this->dispatcher->dispatch($cmd);

            if (
                !($slide instanceof SlideEntity)
                || !$this->ac->isGranted(Permission::LIBRARY_ITEM_READ, $user, $slide)
            ) {
                return new RedirectResponse('/app/slides');
            }

            $data['slide'] = $slide;

            return new ViewResponse(
                '/templates/app/slides/view.twig',
                $data
            );
        } catch (LibraryItemNotFoundException $th) {
            return new RedirectResponse('/app/slides');
        }
    }

    private function getServices(ServerRequestInterface $request): array
    {
        $granted = [];

        /** @var WorkspaceEntity */
        $ws = $request->getAttribute(WorkspaceEntity::class);

        foreach ($this->aiFactory->list(SlideServiceInterface::class) as $service) {
            foreach ($service->getSupportedModels() as $model) {
                $info = $this->modelRegistry->get($model->value);

                if (!$info) {
                    continue;
                }

                if (!isset($granted[$info['provider']])) {
                    $granted[$info['provider']] = [
                        'title' => $info['provider'],
                        'models' => []
                    ];
                }

                $granted[$info['provider']]['models'][] = [
                    'value' => $model->value,
                    'label' => $info['title'] ?? $model->value,
                ];
            }
        }

        return array_values($granted);
    }

    private function getPresets(ServerRequestInterface $request): Iterator
    {
        /** @var WorkspaceEntity */
        $ws = $request->getAttribute(WorkspaceEntity::class);

        $cmd = new ListPresetsCommand();
        $cmd->setStatus(Status::ACTIVE);
        $cmd->type = Type::SLIDE_GENERATION;
        $cmd->setOrderBy('created_at', 'desc');

        return $this->dispatcher->dispatch($cmd);
    }

    private function getSlides(ServerRequestInterface $request): Iterator
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        /** @var WorkspaceEntity */
        $ws = $request->getAttribute(WorkspaceEntity::class);

        $cmd = new ListLibraryItemsCommand();
        $cmd->setUser($user);
        $cmd->setWorkspace($ws);
        $cmd->setType(ItemType::SLIDE);
        $cmd->sortBy('created_at', 'desc');

        return $this->dispatcher->dispatch($cmd);
    }
}
