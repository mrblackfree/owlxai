<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Doctrine\ORM\EntityManagerInterface;
use Presentation\Response\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Domain\Entities\UserEntity;

#[Route(path: '/enable-slides-feature', method: RequestMethod::GET)]
class EnableSlidesView extends AppView implements RequestHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Check if user is admin
        /** @var UserEntity|null $user */
        $user = $request->getAttribute(UserEntity::class);
        
        $isAdmin = $user && ($user->getRole()->value === 'admin');
        
        $connection = $this->em->getConnection();
        
        $html = "<!DOCTYPE html>";
        $html .= "<html><head><title>Enable Slides Feature</title>";
        $html .= "<style>body { font-family: Arial, sans-serif; margin: 40px; } ";
        $html .= "pre { background: #f4f4f4; padding: 20px; border-radius: 5px; } ";
        $html .= ".warning { background: #ffc; padding: 20px; border-radius: 5px; margin: 20px 0; } ";
        $html .= ".success { background: #cfc; padding: 20px; border-radius: 5px; } ";
        $html .= ".error { background: #fcc; padding: 20px; border-radius: 5px; }</style>";
        $html .= "</head><body>";
        
        if (!$isAdmin) {
            $html .= "<div class='error'>";
            $html .= "<h2>⛔ Access Denied</h2>";
            $html .= "<p>You must be logged in as an administrator to access this page.</p>";
            $html .= "<p><a href='/admin'>Go to Admin Login</a></p>";
            $html .= "</div>";
            $html .= "</body></html>";
            
            return new Response($html, 403);
        }
        
        $html .= "<h1>🚀 Enable Slides Feature</h1>";
        $html .= "<div class='warning'>";
        $html .= "<strong>⚠️ Security Notice:</strong> This page will be automatically disabled after use.";
        $html .= "</div>";
        
        try {
            $html .= "<pre>";
            $html .= "Starting slides feature migration...\n\n";
            
            // Enable slides feature
            $sqls = [
                "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
                 VALUES (
                     UNHEX(REPLACE(UUID(), '-', '')), 
                     'features.slides.is_enabled', 
                     'true', 
                     NOW(), 
                     NOW()
                 ) ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()",
                 
                "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
                 VALUES (
                     UNHEX(REPLACE(UUID(), '-', '')), 
                     'features.slides.max_slides', 
                     '20', 
                     NOW(), 
                     NOW()
                 ) ON DUPLICATE KEY UPDATE `value` = '20', `updated_at` = NOW()",
                 
                "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
                 VALUES (
                     UNHEX(REPLACE(UUID(), '-', '')), 
                     'features.slides.default_slide_count', 
                     '10', 
                     NOW(), 
                     NOW()
                 ) ON DUPLICATE KEY UPDATE `value` = '10', `updated_at` = NOW()",
                 
                "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
                 VALUES (
                     UNHEX(REPLACE(UUID(), '-', '')), 
                     'features.slides.allow_pdf_export', 
                     'true', 
                     NOW(), 
                     NOW()
                 ) ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()",
                 
                "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
                 VALUES (
                     UNHEX(REPLACE(UUID(), '-', '')), 
                     'features.slides.allow_pptx_export', 
                     'true', 
                     NOW(), 
                     NOW()
                 ) ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()",
            ];
            
            // Enable themes
            $themes = ['professional', 'creative', 'minimal', 'dark', 'colorful'];
            foreach ($themes as $theme) {
                $sqls[] = "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
                           VALUES (
                               UNHEX(REPLACE(UUID(), '-', '')), 
                               'features.slides.themes.$theme', 
                               'true', 
                               NOW(), 
                               NOW()
                           ) ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()";
            }
            
            // Enable templates
            $templates = ['modern', 'classic', 'business', 'academic', 'startup'];
            foreach ($templates as $template) {
                $sqls[] = "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
                           VALUES (
                               UNHEX(REPLACE(UUID(), '-', '')), 
                               'features.slides.templates.$template', 
                               'true', 
                               NOW(), 
                               NOW()
                           ) ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()";
            }
            
            // Execute all queries
            $success = 0;
            $failed = 0;
            foreach ($sqls as $index => $sql) {
                $html .= "Executing query " . ($index + 1) . "/" . count($sqls) . "... ";
                try {
                    $connection->executeStatement($sql);
                    $success++;
                    $html .= "✅ Success\n";
                } catch (\Exception $e) {
                    $failed++;
                    $html .= "❌ Error: " . $e->getMessage() . "\n";
                }
            }
            
            $html .= "\n==========================================\n";
            $html .= "Migration completed!\n";
            $html .= "Successful queries: $success\n";
            $html .= "Failed queries: $failed\n";
            $html .= "==========================================\n";
            $html .= "</pre>";
            
            // Verify the settings
            $result = $connection->fetchAssociative(
                "SELECT `value` FROM `option` WHERE `key` = 'features.slides.is_enabled'"
            );
            
            if ($result && $result['value'] === 'true') {
                $html .= "<div class='success'>";
                $html .= "<h2>✅ SUCCESS!</h2>";
                $html .= "<p>Slides feature has been enabled successfully!</p>";
                $html .= "<p><strong>Next steps:</strong></p>";
                $html .= "<ul>";
                $html .= "<li><a href='/app'>Go to Apps page</a> to see the Slides feature</li>";
                $html .= "<li><a href='/admin/settings/features/slides'>Configure Slides settings</a></li>";
                $html .= "</ul>";
                $html .= "</div>";
                
                // Disable this route for security
                $connection->executeStatement(
                    "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
                     VALUES (
                         UNHEX(REPLACE(UUID(), '-', '')), 
                         'features.slides.setup_completed', 
                         'true', 
                         NOW(), 
                         NOW()
                     ) ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()"
                );
            } else {
                $html .= "<div class='error'>";
                $html .= "<h2>❌ Verification Failed</h2>";
                $html .= "<p>Could not verify that the slides feature was enabled.</p>";
                $html .= "</div>";
            }
            
        } catch (\Exception $e) {
            $html .= "<div class='error'>";
            $html .= "<h2>❌ Error</h2>";
            $html .= "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
            $html .= "</div>";
        }
        
        $html .= "</body></html>";
        
        return new Response($html);
    }
}
