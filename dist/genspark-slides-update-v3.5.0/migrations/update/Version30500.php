<?php

declare(strict_types=1);

namespace Migrations\Update;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * GenSpark 스타일 고급 슬라이드 제작 기능 업데이트
 * 
 * 새로운 기능:
 * - 웹 검색 통합 슬라이드 생성
 * - 자동 이미지 생성 및 삽입
 * - 고급 차트 및 데이터 시각화
 * - 다양한 레이아웃 타입
 * - PPTX, PDF, HTML 내보내기
 * - 고급 테마 및 템플릿 시스템
 * - AI 기반 디자인 제안
 */
final class Version30500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'GenSpark Style Advanced Slide Generation Features';
    }

    public function up(Schema $schema): void
    {
        // Enable enhanced slide features
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

        // Enable web search for slide generation
        $this->addSql(
            "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
            VALUES (
                UNHEX(REPLACE(UUID(), '-', '')), 
                'features.slides.enable_web_search', 
                'true', 
                NOW(), 
                NOW()
            ) ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()"
        );

        // Enable auto image generation
        $this->addSql(
            "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
            VALUES (
                UNHEX(REPLACE(UUID(), '-', '')), 
                'features.slides.enable_auto_images', 
                'true', 
                NOW(), 
                NOW()
            ) ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()"
        );

        // Enable chart generation
        $this->addSql(
            "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
            VALUES (
                UNHEX(REPLACE(UUID(), '-', '')), 
                'features.slides.enable_charts', 
                'true', 
                NOW(), 
                NOW()
            ) ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()"
        );

        // Max slides per presentation
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

        // Default slide count
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

        // Export formats
        $exportFormats = ['pdf', 'pptx', 'html'];
        foreach ($exportFormats as $format) {
            $this->addSql(
                "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
                VALUES (
                    UNHEX(REPLACE(UUID(), '-', '')), 
                    'features.slides.allow_{$format}_export', 
                    'true', 
                    NOW(), 
                    NOW()
                ) ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()"
            );
        }

        // Enhanced themes
        $themes = [
            'professional' => 'Classic professional business theme',
            'creative' => 'Vibrant creative theme with gradients',
            'minimal' => 'Clean minimal design',
            'dark' => 'Dark mode theme',
            'colorful' => 'Colorful gradient theme'
        ];

        foreach ($themes as $theme => $description) {
            $this->addSql(
                "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
                VALUES (
                    UNHEX(REPLACE(UUID(), '-', '')), 
                    'features.slides.themes.{$theme}', 
                    " . $this->connection->quote(json_encode([
                        'enabled' => true,
                        'name' => ucfirst($theme),
                        'description' => $description
                    ])) . ", 
                    NOW(), 
                    NOW()
                ) ON DUPLICATE KEY UPDATE `value` = " . $this->connection->quote(json_encode([
                        'enabled' => true,
                        'name' => ucfirst($theme),
                        'description' => $description
                    ])) . ", `updated_at` = NOW()"
            );
        }

        // Enhanced templates
        $templates = [
            'modern' => 'Modern sleek design',
            'classic' => 'Traditional professional layout',
            'business' => 'Corporate business style',
            'academic' => 'Academic research format',
            'startup' => 'Startup pitch deck style'
        ];

        foreach ($templates as $template => $description) {
            $this->addSql(
                "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
                VALUES (
                    UNHEX(REPLACE(UUID(), '-', '')), 
                    'features.slides.templates.{$template}', 
                    " . $this->connection->quote(json_encode([
                        'enabled' => true,
                        'name' => ucfirst($template),
                        'description' => $description
                    ])) . ", 
                    NOW(), 
                    NOW()
                ) ON DUPLICATE KEY UPDATE `value` = " . $this->connection->quote(json_encode([
                        'enabled' => true,
                        'name' => ucfirst($template),
                        'description' => $description
                    ])) . ", `updated_at` = NOW()"
            );
        }

        // Layout types
        $layouts = [
            'title_slide',
            'title_and_content',
            'two_column',
            'title_chart_and_content',
            'quote_slide',
            'image_and_text',
            'full_image',
            'comparison',
            'conclusion_slide'
        ];

        foreach ($layouts as $layout) {
            $this->addSql(
                "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
                VALUES (
                    UNHEX(REPLACE(UUID(), '-', '')), 
                    'features.slides.layouts.{$layout}', 
                    'true', 
                    NOW(), 
                    NOW()
                ) ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()"
            );
        }

        // Chart types
        $chartTypes = ['bar', 'line', 'pie', 'scatter', 'area'];
        foreach ($chartTypes as $chartType) {
            $this->addSql(
                "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
                VALUES (
                    UNHEX(REPLACE(UUID(), '-', '')), 
                    'features.slides.charts.{$chartType}', 
                    'true', 
                    NOW(), 
                    NOW()
                ) ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()"
            );
        }

        // AI-powered features
        $this->addSql(
            "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
            VALUES (
                UNHEX(REPLACE(UUID(), '-', '')), 
                'features.slides.ai_design_suggestions', 
                'true', 
                NOW(), 
                NOW()
            ) ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()"
        );

        $this->addSql(
            "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
            VALUES (
                UNHEX(REPLACE(UUID(), '-', '')), 
                'features.slides.auto_layout_optimization', 
                'true', 
                NOW(), 
                NOW()
            ) ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()"
        );

        $this->addSql(
            "INSERT INTO `option` (`id`, `key`, `value`, `created_at`, `updated_at`) 
            VALUES (
                UNHEX(REPLACE(UUID(), '-', '')), 
                'features.slides.speaker_notes_generation', 
                'true', 
                NOW(), 
                NOW()
            ) ON DUPLICATE KEY UPDATE `value` = 'true', `updated_at` = NOW()"
        );
    }

    public function down(Schema $schema): void
    {
        // Remove all slide-related options
        $this->addSql("DELETE FROM `option` WHERE `key` LIKE 'features.slides.%'");
    }
}

