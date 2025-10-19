<?php
/**
 * Slides Feature Installation Script
 * 
 * This script enables the Slides feature in your application.
 * Run this file once from the command line or browser, then delete it.
 * 
 * Usage:
 *   Command line: php install-slides.php
 *   Browser: http://your-domain.com/install-slides.php
 */

declare(strict_types=1);

// Prevent direct access from non-CLI if not in debug mode
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><title>Install Slides Feature</title>";
    echo "<style>body{font-family:Arial,sans-serif;margin:40px;}pre{background:#f4f4f4;padding:20px;border-radius:5px;}.success{background:#cfc;padding:20px;border-radius:5px;margin:20px 0;}.error{background:#fcc;padding:20px;border-radius:5px;}</style>";
    echo "</head><body><h1>🚀 Slides Feature Installation</h1>";
}

try {
    // Load environment variables
    if (file_exists(__DIR__ . '/.env')) {
        $envFile = __DIR__ . '/.env';
    } elseif (file_exists(__DIR__ . '/../.env')) {
        $envFile = __DIR__ . '/../.env';
    } else {
        throw new Exception('.env file not found');
    }
    
    $env = parse_ini_file($envFile);
    
    // Database connection
    $dbHost = $env['DB_HOST'] ?? 'localhost';
    $dbPort = $env['DB_PORT'] ?? '3306';
    $dbName = $env['DB_NAME'] ?? '';
    $dbUser = $env['DB_USER'] ?? '';
    $dbPass = $env['DB_PASSWORD'] ?? '';
    $dbSocket = $env['DB_UNIX_SOCKET'] ?? null;
    
    if (empty($dbName) || empty($dbUser)) {
        throw new Exception('Database credentials not found in .env file');
    }
    
    // Connect to database
    if ($dbSocket) {
        $dsn = "mysql:unix_socket=$dbSocket;dbname=$dbName;charset=utf8mb4";
    } else {
        $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    }
    
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    if (!$isCli) echo "<pre>";
    echo "✅ Connected to database: $dbName\n\n";
    echo "Starting slides feature installation...\n";
    echo str_repeat("=", 50) . "\n";
    
    // Prepare SQL statements
    $sqls = [
        "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
         VALUES (UNHEX(REPLACE(UUID(), '-', '')), 'features.slides.is_enabled', 'true', NOW(), NOW()) 
         ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()",
         
        "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
         VALUES (UNHEX(REPLACE(UUID(), '-', '')), 'features.slides.max_slides', '20', NOW(), NOW()) 
         ON DUPLICATE KEY UPDATE `value` = '20', `updated_at` = NOW()",
         
        "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
         VALUES (UNHEX(REPLACE(UUID(), '-', '')), 'features.slides.default_slide_count', '10', NOW(), NOW()) 
         ON DUPLICATE KEY UPDATE `value` = '10', `updated_at` = NOW()",
         
        "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
         VALUES (UNHEX(REPLACE(UUID(), '-', '')), 'features.slides.allow_pdf_export', 'true', NOW(), NOW()) 
         ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()",
         
        "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
         VALUES (UNHEX(REPLACE(UUID(), '-', '')), 'features.slides.allow_pptx_export', 'true', NOW(), NOW()) 
         ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()",
    ];
    
    // Add themes
    $themes = ['professional', 'creative', 'minimal', 'dark', 'colorful'];
    foreach ($themes as $theme) {
        $sqls[] = "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
                   VALUES (UNHEX(REPLACE(UUID(), '-', '')), 'features.slides.themes.$theme', 'true', NOW(), NOW()) 
                   ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()";
    }
    
    // Add templates
    $templates = ['modern', 'classic', 'business', 'academic', 'startup'];
    foreach ($templates as $template) {
        $sqls[] = "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
                   VALUES (UNHEX(REPLACE(UUID(), '-', '')), 'features.slides.templates.$template', 'true', NOW(), NOW()) 
                   ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()";
    }
    
    // Execute queries
    $success = 0;
    $failed = 0;
    
    foreach ($sqls as $index => $sql) {
        echo "\n[" . ($index + 1) . "/" . count($sqls) . "] Executing query... ";
        try {
            $pdo->exec($sql);
            $success++;
            echo "✅ Success";
        } catch (PDOException $e) {
            $failed++;
            echo "❌ Failed: " . $e->getMessage();
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "\nInstallation Summary:\n";
    echo "  Successful: $success\n";
    echo "  Failed: $failed\n";
    echo str_repeat("=", 50) . "\n";
    
    // Verify installation
    $stmt = $pdo->query("SELECT `value` FROM `option` WHERE `key` = 'features.slides.is_enabled'");
    $result = $stmt->fetch();
    
    if ($result && $result['value'] === 'true') {
        echo "\n✅ SUCCESS! Slides feature has been enabled!\n\n";
        echo "Next steps:\n";
        echo "1. Delete this file (install-slides.php) for security\n";
        echo "2. Clear browser cache (Ctrl+F5 or Cmd+Shift+R)\n";
        echo "3. Visit: https://owlxai.com/app to see Slides\n";
        echo "4. Or configure at: https://owlxai.com/admin/settings/features/slides\n";
        
        if (!$isCli) {
            echo "</pre>";
            echo "<div class='success'>";
            echo "<h2>✅ Installation Complete!</h2>";
            echo "<p><strong>Slides feature is now enabled.</strong></p>";
            echo "<ul>";
            echo "<li><a href='/app'>Go to Apps page</a></li>";
            echo "<li><a href='/admin/settings/features/slides'>Configure Slides settings</a></li>";
            echo "</ul>";
            echo "<p><strong>⚠️ Important:</strong> Delete this file immediately!</p>";
            echo "</div>";
        }
    } else {
        echo "\n❌ Verification failed. Please check the database manually.\n";
        
        if (!$isCli) {
            echo "</pre>";
            echo "<div class='error'><h2>❌ Verification Failed</h2>";
            echo "<p>The queries executed but verification failed. Check your database.</p></div>";
        }
    }
    
} catch (Exception $e) {
    if (!$isCli) {
        echo "</pre><div class='error'><h2>❌ Error</h2><pre>";
    }
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    if (!$isCli) {
        echo "</pre></div>";
    }
    exit(1);
}

if (!$isCli) {
    echo "</body></html>";
}

exit(0);
