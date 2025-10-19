# 🔧 "An unexpected error occurred" 에러 해결

## 🔴 문제: IntlDateFormatter not found

실제 에러 메시지:
```
PHP Fatal error: Uncaught Error: Class "IntlDateFormatter" not found
```

이것은 **PHP intl 확장이 활성화되지 않아서** 발생하는 문제입니다.

---

## ✅ 해결 방법

### 🪟 Windows에서 (XAMPP/WAMP/로컬 개발)

#### 1. php.ini 파일 찾기

```powershell
# PHP 설정 파일 위치 확인
php --ini
```

#### 2. php.ini 파일 열기

```powershell
# 메모장으로 열기
notepad C:\xampp\php\php.ini

# 또는 경로가 다를 수 있습니다
notepad C:\php\php.ini
```

#### 3. intl 확장 활성화

파일에서 다음 줄을 찾으세요:

```ini
;extension=intl
```

**세미콜론(;)을 제거**하여 활성화:

```ini
extension=intl
```

#### 4. 웹 서버 재시작

**XAMPP:**
- XAMPP Control Panel → Apache "Stop" → "Start"

**WAMP:**
- WAMP 아이콘 → Apache → Service → Restart

**IIS + PHP:**
```powershell
iisreset
```

#### 5. 확인

```powershell
php -m | findstr intl
```

"intl"이 출력되면 성공! ✅

---

### 🐧 Linux에서 (Ubuntu/Debian)

#### 1. intl 확장 설치

```bash
# PHP 8.1
sudo apt-get update
sudo apt-get install php8.1-intl

# PHP 8.2
sudo apt-get install php8.2-intl

# PHP 8.3
sudo apt-get install php8.3-intl
```

#### 2. 웹 서버 재시작

```bash
# Apache
sudo systemctl restart apache2

# Nginx + PHP-FPM
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx
```

#### 3. 확인

```bash
php -m | grep intl
```

---

### 🍎 macOS에서

#### 1. Homebrew로 PHP 재설치 (intl 포함)

```bash
brew reinstall php
```

또는 특정 버전:

```bash
brew reinstall php@8.1
brew reinstall php@8.2
```

#### 2. 확인

```bash
php -m | grep intl
```

---

### ☁️ 호스팅 서버 (cPanel/Plesk)

#### cPanel:

1. **cPanel 로그인**
2. **Select PHP Version** 클릭
3. **Extensions** 탭
4. **intl** 체크박스 선택 ✅
5. **Save** 클릭

#### Plesk:

1. **Plesk 로그인**
2. **Tools & Settings**
3. **PHP Settings**
4. 해당 PHP 버전 선택
5. **intl** 활성화 ✅
6. **Apply** 클릭

#### 공유 호스팅:

호스팅 제공자에게 문의:
```
"PHP intl 확장을 활성화해주세요"
```

---

## 🧪 테스트 방법

### 간단한 테스트:

새 파일 `test-intl.php` 생성:

```php
<?php
if (extension_loaded('intl')) {
    echo "✅ intl 확장이 활성화되었습니다!";
} else {
    echo "❌ intl 확장이 비활성화되어 있습니다.";
}
?>
```

실행:
```bash
php test-intl.php
```

또는 브라우저에서:
```
http://yourdomain.com/test-intl.php
```

---

## ✅ 확인 후 다음 단계

intl 확장을 활성화한 후:

1. **웹 서버 재시작**
2. **브라우저 새로고침** (Ctrl+F5)
3. **/app/slides** 페이지로 이동
4. **"Create with AI"** 버튼 클릭
5. **정상 작동 확인!** 🎉

---

## 🐛 여전히 문제가 있나요?

### 추가 디버깅:

**1. PHP 정보 확인:**

```php
<?php phpinfo(); ?>
```

브라우저에서 열어서 **"intl"** 검색

**2. 로드된 확장 확인:**

```bash
php -m
```

**intl**이 목록에 있어야 합니다.

**3. php.ini 위치 확인:**

```bash
php --ini
```

올바른 php.ini 파일을 수정했는지 확인!

---

## 💡 추가 정보

### intl 확장이 하는 일:

- 날짜/시간 국제화
- 숫자 포맷팅
- 통화 포맷팅
- 로케일 처리
- Twig 템플릿 엔진의 날짜 필터

### 프로젝트에서 필요한 이유:

이 프로젝트는 **Twig IntlExtension**을 사용하여 다국어 날짜 표시를 지원합니다. intl 확장 없이는 Twig가 로드되지 않습니다.

---

## 🎯 요약

1. **문제**: PHP intl 확장 비활성화
2. **해결**: php.ini에서 `extension=intl` 활성화
3. **재시작**: 웹 서버 재시작
4. **테스트**: 브라우저 새로고침

---

**이 문제는 GenSpark 슬라이드 업데이트와 무관하며, PHP 환경 설정 문제입니다!**

intl 확장 활성화 후에는 모든 기능이 정상 작동할 것입니다! 🚀

