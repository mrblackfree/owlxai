<?php

/**
 * GenSpark 슬라이드 디버깅 스크립트
 * 
 * 브라우저에서 접근: http://yourdomain.com/debug-slides.php
 */

require __DIR__ . '/bootstrap/autoload.php';
require __DIR__ . '/bootstrap/app.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GenSpark 슬라이드 디버깅</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #00ff00; }
        .success { color: #00ff00; }
        .error { color: #ff0000; }
        .warning { color: #ffaa00; }
        .info { color: #00aaff; }
        .box { background: #2a2a2a; border: 1px solid #444; padding: 15px; margin: 10px 0; border-radius: 5px; }
        h1, h2 { color: #ffffff; }
        pre { background: #000; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🧪 GenSpark 슬라이드 업데이트 디버깅</h1>
    
    <?php
    
    echo '<div class="box">';
    echo '<h2>1️⃣ PHP 환경 확인</h2>';
    echo '<pre>';
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "Memory Limit: " . ini_get('memory_limit') . "\n";
    echo "Max Execution Time: " . ini_get('max_execution_time') . "s\n";
    echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
    echo '</pre>';
    echo '</div>';
    
    // 2. 클래스 존재 확인
    echo '<div class="box">';
    echo '<h2>2️⃣ 클래스 존재 확인</h2>';
    
    $classes = [
        'Ai\Infrastructure\Services\EnhancedSlide\AdvancedSlideService',
        'Ai\Infrastructure\Services\EnhancedSlide\SlideExporter',
        'Ai\Infrastructure\Services\OpenAi\SlideService',
        'Ai\Infrastructure\Services\Anthropic\SlideService',
        'Ai\Domain\Slide\SlideServiceInterface',
        'Ai\Domain\Entities\SlideEntity',
    ];
    
    foreach ($classes as $class) {
        if (class_exists($class)) {
            echo "<span class='success'>✅ {$class}</span><br>";
        } else {
            echo "<span class='error'>❌ {$class} - NOT FOUND!</span><br>";
        }
    }
    echo '</div>';
    
    // 3. Container 확인
    echo '<div class="box">';
    echo '<h2>3️⃣ Container 서비스 확인</h2>';
    
    try {
        $container = \Application::make(\Psr\Container\ContainerInterface::class);
        
        $services = [
            'Ai\Infrastructure\Services\EnhancedSlide\SlideExporter',
            'Ai\Domain\Services\AiServiceFactoryInterface',
        ];
        
        foreach ($services as $service) {
            try {
                $instance = $container->get($service);
                echo "<span class='success'>✅ {$service} (타입: " . get_class($instance) . ")</span><br>";
            } catch (Throwable $e) {
                echo "<span class='error'>❌ {$service}: {$e->getMessage()}</span><br>";
            }
        }
    } catch (Throwable $e) {
        echo "<span class='error'>❌ Container 접근 실패: {$e->getMessage()}</span><br>";
    }
    
    echo '</div>';
    
    // 4. AI 서비스 팩토리 확인
    echo '<div class="box">';
    echo '<h2>4️⃣ AI 서비스 팩토리 확인</h2>';
    
    try {
        $factory = \Application::make(\Ai\Domain\Services\AiServiceFactoryInterface::class);
        
        if ($factory) {
            echo "<span class='success'>✅ AiServiceFactory 로드됨</span><br><br>";
            
            // 슬라이드 서비스 목록
            echo "<strong class='info'>SlideServiceInterface 구현체:</strong><br>";
            $slideServices = iterator_to_array($factory->list(\Ai\Domain\Slide\SlideServiceInterface::class));
            
            if (count($slideServices) > 0) {
                foreach ($slideServices as $service) {
                    echo "<span class='success'>✅ " . get_class($service) . "</span><br>";
                    
                    // 지원 모델 확인
                    $models = iterator_to_array($service->getSupportedModels());
                    if (count($models) > 0) {
                        echo "  <span class='info'>지원 모델: ";
                        echo implode(', ', array_map(fn($m) => $m->value, array_slice($models, 0, 3)));
                        if (count($models) > 3) echo " (외 " . (count($models) - 3) . "개)";
                        echo "</span><br>";
                    }
                }
            } else {
                echo "<span class='warning'>⚠️ 등록된 SlideService가 없습니다!</span><br>";
            }
        } else {
            echo "<span class='error'>❌ AiServiceFactory를 로드할 수 없습니다</span><br>";
        }
    } catch (Throwable $e) {
        echo "<span class='error'>❌ 에러: {$e->getMessage()}</span><br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo '</div>';
    
    // 5. 옵션 확인
    echo '<div class="box">';
    echo '<h2>5️⃣ 슬라이드 기능 옵션 확인</h2>';
    
    $options = [
        'option.features.slides.is_enabled',
        'option.features.slides.enable_web_search',
        'option.features.slides.enable_auto_images',
        'option.features.slides.max_slides',
    ];
    
    foreach ($options as $option) {
        try {
            $value = \Application::make($option);
            echo "<span class='success'>✅ {$option}: <strong>{$value}</strong></span><br>";
        } catch (Throwable $e) {
            echo "<span class='warning'>⚠️ {$option}: 설정되지 않음</span><br>";
        }
    }
    
    echo '</div>';
    
    // 6. 파일 존재 확인
    echo '<div class="box">';
    echo '<h2>6️⃣ 필수 파일 확인</h2>';
    
    $files = [
        'src/Ai/Infrastructure/Services/EnhancedSlide/AdvancedSlideService.php',
        'src/Ai/Infrastructure/Services/EnhancedSlide/SlideExporter.php',
        'src/Presentation/RequestHandlers/App/SlideExportRequestHandler.php',
        'migrations/update/Version30500.php',
        'data/slide-presets.yml',
        'resources/views/templates/app/slides/enhanced-index.twig',
    ];
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            $size = filesize($file);
            echo "<span class='success'>✅ {$file} (" . number_format($size) . " bytes)</span><br>";
        } else {
            echo "<span class='error'>❌ {$file} - 파일 없음!</span><br>";
        }
    }
    
    echo '</div>';
    
    // 7. 데이터베이스 확인
    echo '<div class="box">';
    echo '<h2>7️⃣ 데이터베이스 마이그레이션 확인</h2>';
    
    try {
        $em = \Application::make(\Doctrine\ORM\EntityManagerInterface::class);
        $conn = $em->getConnection();
        
        // option 테이블에서 slides 관련 설정 확인
        $sql = "SELECT `key`, `value` FROM `option` WHERE `key` LIKE 'features.slides.%' LIMIT 10";
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();
        $rows = $result->fetchAllAssociative();
        
        if (count($rows) > 0) {
            echo "<span class='success'>✅ 슬라이드 옵션 발견: " . count($rows) . "개</span><br><br>";
            foreach ($rows as $row) {
                echo "<span class='info'>{$row['key']}: {$row['value']}</span><br>";
            }
        } else {
            echo "<span class='warning'>⚠️ 슬라이드 옵션이 데이터베이스에 없습니다.</span><br>";
            echo "<span class='warning'>마이그레이션을 실행해주세요: php bin/console migrations:migrate</span><br>";
        }
    } catch (Throwable $e) {
        echo "<span class='error'>❌ DB 확인 실패: {$e->getMessage()}</span><br>";
    }
    
    echo '</div>';
    
    // 8. 최종 상태
    echo '<div class="box">';
    echo '<h2>8️⃣ 최종 상태</h2>';
    
    $allGood = true;
    
    // 모든 체크 통과 여부
    echo '<h3>체크리스트:</h3>';
    echo '<ul>';
    
    if (class_exists('Ai\Infrastructure\Services\EnhancedSlide\AdvancedSlideService')) {
        echo "<li class='success'>✅ AdvancedSlideService 로드됨</li>";
    } else {
        echo "<li class='error'>❌ AdvancedSlideService 로드 실패</li>";
        $allGood = false;
    }
    
    if (file_exists('vendor/phpoffice/phppresentation')) {
        echo "<li class='success'>✅ PHPOffice/PhpPresentation 설치됨</li>";
    } else {
        echo "<li class='warning'>⚠️ PHPOffice/PhpPresentation 미설치 (내보내기 기능 제한)</li>";
    }
    
    try {
        $enabled = \Application::make('option.features.slides.is_enabled');
        if ($enabled === 'true' || $enabled === true) {
            echo "<li class='success'>✅ 슬라이드 기능 활성화됨</li>";
        } else {
            echo "<li class='warning'>⚠️ 슬라이드 기능 비활성화됨</li>";
            $allGood = false;
        }
    } catch (Throwable $e) {
        echo "<li class='error'>❌ 슬라이드 기능 설정 없음</li>";
        $allGood = false;
    }
    
    echo '</ul>';
    
    if ($allGood) {
        echo '<h3 class="success">🎉 모든 체크 통과! 슬라이드 기능을 사용할 수 있습니다.</h3>';
        echo '<p><a href="/app/slides" style="color: #00aaff;">👉 /app/slides 페이지로 이동</a></p>';
    } else {
        echo '<h3 class="error">❌ 일부 문제가 발견되었습니다. 위의 내용을 확인해주세요.</h3>';
    }
    
    echo '</div>';
    
    // 9. 빠른 수정 가이드
    echo '<div class="box">';
    echo '<h2>9️⃣ 빠른 수정 가이드</h2>';
    echo '<pre>';
    echo "# 1. Composer 의존성 설치\n";
    echo "composer require phpoffice/phppresentation\n\n";
    
    echo "# 2. 마이그레이션 실행\n";
    echo "php bin/console migrations:migrate\n\n";
    
    echo "# 3. 캐시 클리어\n";
    echo "Remove-Item -Recurse -Force var\\cache\\*   # Windows\n";
    echo "rm -rf var/cache/*                         # Linux/Mac\n\n";
    
    echo "# 4. Autoload 재생성\n";
    echo "composer dump-autoload\n\n";
    
    echo "# 5. 프리셋 임포트 (선택사항)\n";
    echo "php bin/console presets:import data/slide-presets.yml\n";
    echo '</pre>';
    echo '</div>';
    
    ?>
</body>
</html>
