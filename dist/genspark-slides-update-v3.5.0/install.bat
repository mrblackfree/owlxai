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