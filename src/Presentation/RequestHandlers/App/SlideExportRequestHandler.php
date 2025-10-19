<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App;

use Ai\Application\Commands\ReadLibraryItemCommand;
use Ai\Domain\Entities\SlideEntity;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Ai\Infrastructure\Services\EnhancedSlide\SlideExporter;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\AccessControls\LibraryItemAccessControl;
use Presentation\AccessControls\Permission;
use Presentation\Response\DownloadResponse;
use Presentation\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;

#[Route(path: '/slides/[uuid:id]/export/[pdf|pptx|html:format]', method: RequestMethod::GET)]
class SlideExportRequestHandler extends AppView implements RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private LibraryItemAccessControl $ac,
        private SlideExporter $exporter,

        #[Inject('option.features.slides.is_enabled')]
        private ?string $isEnabled = null,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Check if slides feature is enabled
        if ($this->isEnabled === 'false' || $this->isEnabled === null) {
            return new RedirectResponse('/app');
        }

        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        $id = $request->getAttribute('id');
        $format = $request->getAttribute('format');

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

            // Export based on format
            $filepath = match ($format) {
                'pptx' => $this->exporter->exportToPPTX($slide),
                'pdf' => $this->exporter->exportToPDF($slide),
                'html' => $this->exporter->exportToHTML($slide),
                default => throw new \RuntimeException('Unsupported export format')
            };

            // Determine content type
            $contentType = match ($format) {
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'pdf' => 'application/pdf',
                'html' => 'text/html',
                default => 'application/octet-stream'
            };

            $filename = $this->sanitizeFilename($slide->getTitle()->value) . '.' . $format;

            return new DownloadResponse(
                $filepath,
                $filename,
                $contentType
            );
        } catch (LibraryItemNotFoundException $th) {
            return new RedirectResponse('/app/slides');
        }
    }

    private function sanitizeFilename(string $filename): string
    {
        // Remove special characters and spaces
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        // Limit length
        $filename = substr($filename, 0, 100);
        return $filename;
    }
}
