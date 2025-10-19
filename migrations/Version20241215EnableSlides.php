<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241215EnableSlides extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enable slides feature by default';
    }

    public function up(Schema $schema): void
    {
        // Enable slides feature
        $this->addSql(
            "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
            VALUES (
                UNHEX(REPLACE(UUID(), '-', '')), 
                'features.slides.is_enabled', 
                'true', 
                NOW(), 
                NOW()
            ) ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()"
        );

        // Set default slide settings
        $this->addSql(
            "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
            VALUES (
                UNHEX(REPLACE(UUID(), '-', '')), 
                'features.slides.max_slides', 
                '20', 
                NOW(), 
                NOW()
            ) ON DUPLICATE KEY UPDATE `value` = '20', `updated_at` = NOW()"
        );

        $this->addSql(
            "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
            VALUES (
                UNHEX(REPLACE(UUID(), '-', '')), 
                'features.slides.default_slide_count', 
                '10', 
                NOW(), 
                NOW()
            ) ON DUPLICATE KEY UPDATE `value` = '10', `updated_at` = NOW()"
        );

        $this->addSql(
            "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
            VALUES (
                UNHEX(REPLACE(UUID(), '-', '')), 
                'features.slides.allow_pdf_export', 
                'true', 
                NOW(), 
                NOW()
            ) ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()"
        );

        $this->addSql(
            "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
            VALUES (
                UNHEX(REPLACE(UUID(), '-', '')), 
                'features.slides.allow_pptx_export', 
                'true', 
                NOW(), 
                NOW()
            ) ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()"
        );

        // Enable available themes
        $themes = ['professional', 'creative', 'minimal', 'dark', 'colorful'];
        foreach ($themes as $theme) {
            $this->addSql(
                "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
                VALUES (
                    UNHEX(REPLACE(UUID(), '-', '')), 
                    'features.slides.themes.$theme', 
                    'true', 
                    NOW(), 
                    NOW()
                ) ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()"
            );
        }

        // Enable available templates  
        $templates = ['modern', 'classic', 'business', 'academic', 'startup'];
        foreach ($templates as $template) {
            $this->addSql(
                "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
                VALUES (
                    UNHEX(REPLACE(UUID(), '-', '')), 
                    'features.slides.templates.$template', 
                    'true', 
                    NOW(), 
                    NOW()
                ) ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()"
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Remove slides feature options
        $this->addSql("DELETE FROM `option` WHERE `key` LIKE 'features.slides.%'");
    }
}
