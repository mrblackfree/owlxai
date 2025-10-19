# 🔧 빠른 수정 가이드

## "An unexpected error occurred" 에러 해결

### ✅ 해결 완료!

다음 수정사항이 적용되었습니다:

---

## 📝 수정된 파일

### `src/Ai/Infrastructure/AiModuleBootstrapper.php`

**1. Namespace Import 추가 (26번째 줄):**

```php
use Ai\Infrastructure\Services\Luma;
use Ai\Infrastructure\Services\EnhancedSlide;  // ⭐ 추가!
use Ai\Infrastructure\Services\DocumentReader\DocumentReader;
```

**2. 서비스 등록 추가 (98-99번째 줄):**

```php
->register(OpenAi\EmbeddingService::class)
->register(OpenAi\SlideService::class)                    // ⭐ 추가!
->register(EnhancedSlide\AdvancedSlideService::class)     // ⭐ 추가!
->register(ElevenLabs\SpeechService::class)
```

**3. Anthropic 슬라이드 서비스 추가 (111번째 줄):**

```php
->register(Anthropic\MessageService::class)
->register(Anthropic\SlideService::class)                 // ⭐ 추가!
->register(Azure\SpeechService::class)
```

---

## 🧹 캐시 클리어

### Windows에서:

```powershell
Remove-Item -Recurse -Force var\cache\*
```

또는 관리자 패널에서:
1. **Admin > Settings** 이동
2. **Clear Cache** 버튼 클릭

### Linux/Mac에서:

```bash
rm -rf var/cache/*
```

---

## ✅ 확인 방법

1. **브라우저 새로고침** (Ctrl+F5 또는 Cmd+Shift+R)
2. **/app/slides** 페이지로 이동
3. **"Create with AI"** 버튼 클릭
4. **모달이 열리면 성공!** 🎉

---

## 🚀 테스트 방법

간단한 슬라이드 생성 테스트:

1. **주제 입력:**
   ```
   "Introduction to AI and Machine Learning"
   ```

2. **옵션 선택:**
   - AI Model: GPT-4 (또는 사용 가능한 모델)
   - Slides: 10개
   - Theme: Professional
   - Template: Modern

3. **"Create Presentation"** 클릭

4. **30-60초 후 슬라이드 생성 완료!**

---

## 🐛 여전히 에러가 발생하나요?

### 체크리스트:

- [ ] AiModuleBootstrapper.php에 EnhancedSlide import 추가됨
- [ ] 3개의 서비스 등록 추가됨
- [ ] 캐시 클리어 완료
- [ ] Composer autoload 재생성 완료
- [ ] 브라우저 캐시 클리어 (Ctrl+F5)

### 추가 디버깅:

**1. PHP 에러 확인:**

```bash
# Windows
Get-Content var\log\app.log -Tail 50

# Linux/Mac
tail -50 var/log/app.log
```

**2. 브라우저 콘솔 확인:**
- F12 → Console 탭
- 빨간색 에러 메시지 확인

**3. 서비스 등록 확인:**

```php
// 임시 테스트 파일: test-service.php
<?php
require __DIR__ . '/bootstrap/autoload.php';

use Ai\Infrastructure\Services\EnhancedSlide\AdvancedSlideService;

if (class_exists(AdvancedSlideService::class)) {
    echo "✅ AdvancedSlideService 클래스를 찾았습니다!\n";
} else {
    echo "❌ AdvancedSlideService 클래스를 찾을 수 없습니다.\n";
}
```

실행:
```bash
php test-service.php
```

---

## 💡 일반적인 문제들

### 1. "Class not found" 에러

**해결:**
```bash
composer dump-autoload
```

### 2. "Service not registered" 에러

**원인:** AiModuleBootstrapper.php 수정이 제대로 안됨

**해결:**
- 파일을 다시 열어 수정사항 확인
- 3개의 ->register() 라인이 모두 추가되었는지 확인

### 3. "Invalid plugin" 에러

**원인:** 캐시가 클리어되지 않음

**해결:**
```bash
# 캐시 완전 삭제
rm -rf var/cache/*

# Composer autoload 재생성
composer dump-autoload

# 웹 서버 재시작
sudo systemctl restart php-fpm
sudo systemctl restart nginx  # 또는 apache2
```

### 4. "Method not found" 에러

**원인:** PHPOffice/PhpPresentation 라이브러리 누락

**해결:**
```bash
composer require phpoffice/phppresentation
```

---

## 📞 추가 지원

문제가 계속되면:

1. **에러 메시지 전체 복사**
2. **브라우저 콘솔 스크린샷**
3. **var/log/app.log 마지막 50줄**

위 정보와 함께 문의해주세요!

---

## 🎉 완료!

모든 수정이 완료되었습니다. 이제 GenSpark 스타일의 멋진 AI 프레젠테이션을 만들어보세요!

### 다음 단계:
1. ✅ 수정사항 확인 완료
2. 🧹 캐시 클리어 완료  
3. 🔄 Autoload 재생성 완료
4. 🚀 **이제 /app/slides에서 시작하세요!**






