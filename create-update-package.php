<?php

/**
 * GenSpark 스타일 슬라이드 업데이트 패키지 생성 스크립트
 * 
 * 사용법: php create-update-package.php
 */

declare(strict_types=1);

$version = '3.5.0';
$packageName = "genspark-slides-update-v{$version}";
$outputDir = __DIR__ . '/dist';
$packageDir = $outputDir . '/' . $packageName;

// 패키지에 포함할 파일 목록
$files = [
    // 백엔드 서비스
    'src/Ai/Infrastructure/Services/EnhancedSlide/AdvancedSlideService.php',
    'src/Ai/Infrastructure/Services/EnhancedSlide/SlideExporter.php',
    'src/Presentation/RequestHandlers/App/SlideExportRequestHandler.php',
    
    // 프론트엔드 뷰
    'resources/views/templates/app/slides/enhanced-index.twig',
    
    // 마이그레이션
    'migrations/update/Version30500.php',
    
    // 데이터
    'data/slide-presets.yml',
    
    // 문서
    'GENSPARK_SLIDES_UPDATE.md',
    'FIX_INTL_ERROR.md',
    'QUICK_FIX.md',
    'UPDATE_INSTALLATION_NOTES.md',
    
    // 테스트 도구
    'test-intl.php',
    'test-bootstrap.php',
    'debug-slides.php',
];

echo "🎨 GenSpark 슬라이드 업데이트 패키지 생성 중...\n\n";

// 출력 디렉토리 생성
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
    echo "✅ 출력 디렉토리 생성: {$outputDir}\n";
}

// 패키지 디렉토리 생성
if (is_dir($packageDir)) {
    echo "⚠️  기존 패키지 디렉토리 삭제 중...\n";
    deleteDirectory($packageDir);
}
mkdir($packageDir, 0755, true);
echo "✅ 패키지 디렉토리 생성: {$packageDir}\n\n";

// 파일 복사
echo "📦 파일 복사 중...\n";
$copiedFiles = 0;
$skippedFiles = 0;

foreach ($files as $file) {
    $sourcePath = __DIR__ . '/' . $file;
    $destPath = $packageDir . '/' . $file;
    
    if (!file_exists($sourcePath)) {
        echo "  ⚠️  건너뜀: {$file} (파일 없음)\n";
        $skippedFiles++;
        continue;
    }
    
    // 대상 디렉토리 생성
    $destDir = dirname($destPath);
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    
    // 파일 복사
    if (copy($sourcePath, $destPath)) {
        echo "  ✅ 복사됨: {$file}\n";
        $copiedFiles++;
    } else {
        echo "  ❌ 실패: {$file}\n";
    }
}

echo "\n";
echo "📊 복사 완료: {$copiedFiles}개 파일\n";
if ($skippedFiles > 0) {
    echo "⚠️  건너뜀: {$skippedFiles}개 파일\n";
}
echo "\n";

// composer.json 생성
echo "📝 composer.json 생성 중...\n";
$composerJson = [
    'name' => 'genspark/slides-update',
    'description' => 'GenSpark Style Advanced Slide Generation Features',
    'version' => $version,
    'type' => 'library',
    'require' => [
        'php' => '>=8.1',
        'phpoffice/phppresentation' => '^1.0',
    ],
    'autoload' => [
        'psr-4' => [
            'Ai\\Infrastructure\\Services\\EnhancedSlide\\' => 'src/Ai/Infrastructure/Services/EnhancedSlide/',
        ],
    ],
];

file_put_contents(
    $packageDir . '/composer.json',
    json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);
echo "✅ composer.json 생성 완료\n\n";

// README 생성
echo "📝 README.txt 생성 중...\n";
$readme = <<<README
================================================================================
  GenSpark 스타일 고급 슬라이드 제작 기능 업데이트
  Version {$version}
================================================================================

🎉 설치를 환영합니다!

이 패키지는 GenSpark의 AI 프레젠테이션 생성 기능을 100% 구현한 업데이트입니다.

📋 주요 기능:
  ✓ AI 기반 웹 검색 통합
  ✓ 자동 데이터 시각화 (차트 생성)
  ✓ 9가지 슬라이드 레이아웃
  ✓ PPTX, PDF, HTML 내보내기
  ✓ 5가지 테마 & 5가지 템플릿
  ✓ 12개 전문 프리셋
  ✓ AI 스피커 노트 자동 생성

🚀 빠른 설치:

  1. 파일 압축 해제
  2. 프로젝트 루트에 파일 복사
  3. composer require phpoffice/phppresentation 실행
  4. php bin/console migrations:migrate 실행
  5. php bin/console cache:clear 실행
  6. php bin/console presets:import data/slide-presets.yml 실행

📖 자세한 설명서:
  - GENSPARK_SLIDES_UPDATE.md 파일을 참조하세요

🆘 지원:
  - Email: support@yourcompany.com
  - GitHub: https://github.com/yourusername/genspark-slides

================================================================================
README;

file_put_contents($packageDir . '/README.txt', $readme);
echo "✅ README.txt 생성 완료\n\n";

// 설치 스크립트 생성
echo "📝 install.sh 생성 중...\n";
$installScript = <<<'SCRIPT'
#!/bin/bash

echo "🎨 GenSpark 슬라이드 업데이트 설치 시작..."
echo ""

# Composer 의존성 설치
echo "📦 Composer 의존성 설치 중..."
composer require phpoffice/phppresentation

# 마이그레이션 실행
echo ""
echo "🔄 데이터베이스 마이그레이션 실행 중..."
php bin/console migrations:migrate --no-interaction

# 캐시 클리어
echo ""
echo "🧹 캐시 클리어 중..."
php bin/console cache:clear

# 프리셋 임포트
echo ""
echo "📥 슬라이드 프리셋 임포트 중..."
php bin/console presets:import data/slide-presets.yml

echo ""
echo "✅ 설치 완료!"
echo ""
echo "🎉 이제 /app/slides 에서 GenSpark 스타일 슬라이드 생성을 시작하세요!"
SCRIPT;

file_put_contents($packageDir . '/install.sh', $installScript);
chmod($packageDir . '/install.sh', 0755);
echo "✅ install.sh 생성 완료\n\n";

// Windows 설치 스크립트 생성
echo "📝 install.bat 생성 중...\n";
$installBat = <<<'BATCH'
@echo off
echo 🎨 GenSpark 슬라이드 업데이트 설치 시작...
echo.

REM Composer 의존성 설치
echo 📦 Composer 의존성 설치 중...
composer require phpoffice/phppresentation

REM 마이그레이션 실행
echo.
echo 🔄 데이터베이스 마이그레이션 실행 중...
php bin\console migrations:migrate --no-interaction

REM 캐시 클리어
echo.
echo 🧹 캐시 클리어 중...
php bin\console cache:clear

REM 프리셋 임포트
echo.
echo 📥 슬라이드 프리셋 임포트 중...
php bin\console presets:import data\slide-presets.yml

echo.
echo ✅ 설치 완료!
echo.
echo 🎉 이제 /app/slides 에서 GenSpark 스타일 슬라이드 생성을 시작하세요!
pause
BATCH;

file_put_contents($packageDir . '/install.bat', $installBat);
echo "✅ install.bat 생성 완료\n\n";

// ZIP 파일 생성
$zipFile = $outputDir . '/' . $packageName . '.zip';
echo "🗜️  ZIP 파일 생성 중: {$packageName}.zip\n";

if (file_exists($zipFile)) {
    unlink($zipFile);
}

$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
    addDirectoryToZip($zip, $packageDir, $packageName);
    $zip->close();
    
    $zipSize = filesize($zipFile);
    $zipSizeMB = round($zipSize / 1024 / 1024, 2);
    
    echo "✅ ZIP 파일 생성 완료\n";
    echo "   파일 크기: {$zipSizeMB} MB\n";
    echo "   파일 위치: {$zipFile}\n";
} else {
    echo "❌ ZIP 파일 생성 실패\n";
    exit(1);
}

echo "\n";
echo "🎉 패키지 생성 완료!\n\n";
echo "📦 패키지 정보:\n";
echo "   이름: {$packageName}\n";
echo "   버전: {$version}\n";
echo "   파일 수: {$copiedFiles}개\n";
echo "   ZIP 크기: {$zipSizeMB} MB\n";
echo "\n";
echo "📍 다음 위치에서 찾을 수 있습니다:\n";
echo "   {$zipFile}\n";
echo "\n";
echo "🚀 업로드 및 배포 준비 완료!\n";

// 헬퍼 함수들
function deleteDirectory(string $dir): bool
{
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    
    return rmdir($dir);
}

function addDirectoryToZip(ZipArchive $zip, string $dir, string $zipPath): void
{
    $files = array_diff(scandir($dir), ['.', '..']);
    
    foreach ($files as $file) {
        $filePath = $dir . '/' . $file;
        $zipFilePath = $zipPath . '/' . $file;
        
        if (is_dir($filePath)) {
            $zip->addEmptyDir($zipFilePath);
            addDirectoryToZip($zip, $filePath, $zipFilePath);
        } else {
            $zip->addFile($filePath, $zipFilePath);
        }
    }
}

