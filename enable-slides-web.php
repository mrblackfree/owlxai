<?php

declare(strict_types=1);

// Security check - remove this file after use!
// This file should only be used once to enable the slides feature

try {
    // Bootstrap application
    $container = include __DIR__ . '/bootstrap/app.php';
    
    // Get entity manager
    /** @var \Doctrine\ORM\EntityManagerInterface $em */
    $em = $container->get(\Doctrine\ORM\EntityManagerInterface::class);
    $connection = $em->getConnection();
    
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Enable Slides Feature</title>";
    echo "<style>body { font-family: Arial, sans-serif; margin: 40px; } pre { background: #f4f4f4; padding: 20px; border-radius: 5px; }</style>";
    echo "</head><body>";
    echo "<h2>Slides Feature Migration</h2>";
    echo "<pre>";
    echo "Starting slides feature migration...\n\n";
    
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
        echo "Executing query " . ($index + 1) . "/" . count($sqls) . "... ";
        try {
            $connection->executeStatement($sql);
            $success++;
            echo "✅ Success\n";
        } catch (\Exception $e) {
            $failed++;
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n==========================================\n";
    echo "Migration completed!\n";
    echo "Successful queries: $success\n";
    echo "Failed queries: $failed\n";
    echo "==========================================\n\n";
    
    echo "The following settings have been enabled:\n";
    echo "- Slides feature: ENABLED\n";
    echo "- Max slides per presentation: 20\n";
    echo "- Default slide count: 10\n";
    echo "- PDF export: ENABLED\n";
    echo "- PPTX export: ENABLED\n";
    echo "- All themes: ENABLED\n";
    echo "- All templates: ENABLED\n";
    
    // Verify the settings
    echo "\n==========================================\n";
    echo "Verifying settings...\n";
    echo "==========================================\n";
    
    $result = $connection->fetchAssociative(
        "SELECT `value` FROM `option` WHERE `key` = 'features.slides.is_enabled'"
    );
    
    if ($result && $result['value'] === 'true') {
        echo "\n✅ <strong>SUCCESS!</strong> Slides feature is confirmed to be enabled!\n";
        echo "\n<strong>Next steps:</strong>\n";
        echo "1. Delete this file immediately for security\n";
        echo "2. Clear your browser cache (Ctrl+F5)\n";
        echo "3. Go to the Apps page to see the Slides feature\n";
        echo "4. Or go to Settings > Features > Slides to configure\n";
    } else {
        echo "\n❌ Warning: Could not verify slides feature status.\n";
        echo "Please check your database manually.\n";
    }
    
    echo "</pre>";
    
} catch (\Exception $e) {
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Error</title></head><body>";
    echo "<h2>Error</h2>";
    echo "<pre style='background: #fee; padding: 20px; border-radius: 5px;'>";
    echo "❌ Error: " . htmlspecialchars($e->getMessage()) . "\n";
    echo "\nStack trace:\n" . htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
    echo "</body></html>";
}

echo "<hr>";
echo "<div style='background: #ffc; padding: 20px; border-radius: 5px; margin-top: 20px;'>";
echo "<p><strong>⚠️ SECURITY WARNING:</strong></p>";
echo "<p>This file contains database access code. Please delete it immediately after use!</p>";
echo "<p>Files to delete:</p>";
echo "<ul>";
echo "<li>enable-slides-web.php (root directory)</li>";
echo "<li>public/enable-slides-web.php</li>";
echo "</ul>";
echo "</div>";
echo "</body></html>";
