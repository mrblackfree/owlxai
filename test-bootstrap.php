<?php

/**
 * Bootstrap 테스트 스크립트
 * AiModuleBootstrapper가 올바르게 작동하는지 확인
 */

require __DIR__ . '/bootstrap/autoload.php';

echo "🧪 GenSpark 슬라이드 업데이트 테스트\n\n";

// 1. 클래스 존재 확인
echo "1️⃣ 클래스 존재 확인...\n";

$classes = [
    'Ai\Infrastructure\Services\EnhancedSlide\AdvancedSlideService',
    'Ai\Infrastructure\Services\EnhancedSlide\SlideExporter',
    'Ai\Infrastructure\Services\OpenAi\SlideService',
    'Ai\Infrastructure\Services\Anthropic\SlideService',
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "  ✅ {$class}\n";
    } else {
        echo "  ❌ {$class} - NOT FOUND!\n";
    }
}

// 2. 인터페이스 구현 확인
echo "\n2️⃣ 인터페이스 구현 확인...\n";

use Ai\Domain\Slide\SlideServiceInterface;
use Ai\Infrastructure\Services\EnhancedSlide\AdvancedSlideService;

if (class_exists(AdvancedSlideService::class)) {
    $reflection = new ReflectionClass(AdvancedSlideService::class);
    
    if ($reflection->implementsInterface(SlideServiceInterface::class)) {
        echo "  ✅ AdvancedSlideService implements SlideServiceInterface\n";
    } else {
        echo "  ❌ AdvancedSlideService does NOT implement SlideServiceInterface\n";
    }
    
    // 필수 메서드 확인
    $methods = ['generateSlide', 'supportsModel', 'getSupportedModels'];
    foreach ($methods as $method) {
        if ($reflection->hasMethod($method)) {
            echo "  ✅ Method: {$method}()\n";
        } else {
            echo "  ❌ Missing method: {$method}()\n";
        }
    }
}

// 3. 파일 존재 확인
echo "\n3️⃣ 파일 존재 확인...\n";

$files = [
    'src/Ai/Infrastructure/Services/EnhancedSlide/AdvancedSlideService.php',
    'src/Ai/Infrastructure/Services/EnhancedSlide/SlideExporter.php',
    'src/Presentation/RequestHandlers/App/SlideExportRequestHandler.php',
    'migrations/update/Version30500.php',
    'data/slide-presets.yml',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "  ✅ {$file}\n";
    } else {
        echo "  ❌ {$file} - NOT FOUND!\n";
    }
}

echo "\n✅ 테스트 완료!\n";
echo "\n💡 다음 단계:\n";
echo "  1. 모든 항목이 ✅ 이면 성공!\n";
echo "  2. ❌ 가 있으면 해당 파일을 확인하세요.\n";
echo "  3. 브라우저에서 /app/slides 테스트하세요.\n";






