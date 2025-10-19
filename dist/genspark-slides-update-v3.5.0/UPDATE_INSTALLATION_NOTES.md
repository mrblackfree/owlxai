# ⚠️ 중요한 업데이트 설치 안내

## "Invalid plugin" 에러 해결 방법

GenSpark 슬라이드 업데이트를 설치한 후 "Invalid plugin" 에러가 발생하는 경우, 다음 단계를 따라주세요:

### 해결 방법

#### 1. AiModuleBootstrapper.php 업데이트

파일: `src/Ai/Infrastructure/AiModuleBootstrapper.php`

**찾기 (88번째 줄 근처):**
```php
$this->factory
    ->register(OpenAi\CompletionService::class)
    ->register(OpenAi\TitleGeneratorService::class)
    ->register(OpenAi\CodeCompletionService::class)
    ->register(OpenAi\ImageService::class)
    ->register(OpenAi\TranscriptionService::class)
    ->register(OpenAi\SpeechService::class)
    ->register(OpenAi\MessageService::class)
    ->register(OpenAi\ClassificationService::class)
    ->register(OpenAi\EmbeddingService::class)
```

**다음으로 변경:**
```php
$this->factory
    ->register(OpenAi\CompletionService::class)
    ->register(OpenAi\TitleGeneratorService::class)
    ->register(OpenAi\CodeCompletionService::class)
    ->register(OpenAi\ImageService::class)
    ->register(OpenAi\TranscriptionService::class)
    ->register(OpenAi\SpeechService::class)
    ->register(OpenAi\MessageService::class)
    ->register(OpenAi\ClassificationService::class)
    ->register(OpenAi\EmbeddingService::class)
    ->register(OpenAi\SlideService::class)
    ->register(EnhancedSlide\AdvancedSlideService::class)
```

**그리고 Anthropic 섹션 (105번째 줄 근처):**

찾기:
```php
->register(Anthropic\CompletionService::class)
->register(Anthropic\CodeCompletionService::class)
->register(Anthropic\TitleGeneratorService::class)
->register(Anthropic\MessageService::class)
```

다음으로 변경:
```php
->register(Anthropic\CompletionService::class)
->register(Anthropic\CodeCompletionService::class)
->register(Anthropic\TitleGeneratorService::class)
->register(Anthropic\MessageService::class)
->register(Anthropic\SlideService::class)
```

#### 2. 캐시 클리어

```bash
php bin/console cache:clear
```

또는 수동으로:
```bash
rm -rf var/cache/*
```

Windows:
```cmd
rmdir /s /q var\cache
```

#### 3. 서버 재시작 (필요시)

PHP-FPM 사용 시:
```bash
sudo systemctl restart php-fpm
# 또는
sudo service php8.1-fpm restart
```

Apache 사용 시:
```bash
sudo systemctl restart apache2
# 또는
sudo service apache2 restart
```

Nginx 사용 시:
```bash
sudo systemctl restart nginx
```

---

## 자동 패치 스크립트

편의를 위해 자동 패치 스크립트를 제공합니다:

```bash
php apply-bootstrap-patch.php
```

또는 수동으로 위의 변경사항을 적용해주세요.

---

## 확인 방법

설치가 올바르게 되었는지 확인하는 방법:

1. `/app/slides` 페이지로 이동
2. "Create with AI" 버튼 클릭
3. 에러 없이 모달이 열리면 성공!

---

## 여전히 문제가 있나요?

### 로그 확인

```bash
tail -f var/log/app.log
```

### 일반적인 문제들

**1. "Class not found" 에러**
- Composer autoload 재생성: `composer dump-autoload`

**2. "Service not registered" 에러**
- 위의 AiModuleBootstrapper 수정 다시 확인
- 캐시 클리어 다시 실행

**3. "Method not found" 에러**
- PHPOffice/PhpPresentation 설치 확인: `composer show phpoffice/phppresentation`
- 없으면: `composer require phpoffice/phppresentation`

---

## 지원

추가 도움이 필요하시면:
- GitHub Issues
- Email: support@yourcompany.com
- Discord Community

