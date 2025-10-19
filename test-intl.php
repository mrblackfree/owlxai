<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>PHP intl 확장 테스트</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        .success { color: #10b981; font-size: 24px; font-weight: bold; }
        .error { color: #ef4444; font-size: 24px; font-weight: bold; }
        .info { background: #e0f2fe; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #0284c7; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
    </style>
</head>
<body>
    <div class="box">
        <h1>🧪 PHP intl 확장 테스트</h1>
        
        <?php if (extension_loaded('intl')): ?>
            <p class="success">✅ intl 확장이 활성화되었습니다!</p>
            
            <div class="info">
                <strong>🎉 좋습니다!</strong><br>
                이제 <a href="/app/slides">슬라이드 페이지</a>로 이동하여 GenSpark 스타일 프레젠테이션을 만들어보세요!
            </div>
            
            <h3>intl 버전 정보:</h3>
            <ul>
                <li>ICU Version: <?= defined('INTL_ICU_VERSION') ? INTL_ICU_VERSION : 'N/A' ?></li>
                <li>ICU Data Version: <?= defined('INTL_ICU_DATA_VERSION') ? INTL_ICU_DATA_VERSION : 'N/A' ?></li>
            </ul>
            
        <?php else: ?>
            <p class="error">❌ intl 확장이 비활성화되어 있습니다!</p>
            
            <div class="info">
                <strong>⚠️ 수정 필요</strong><br>
                PHP intl 확장을 활성화해야 애플리케이션이 작동합니다.
            </div>
            
            <h3>해결 방법:</h3>
            
            <h4>Windows (XAMPP/WAMP):</h4>
            <ol>
                <li><code>php.ini</code> 파일 열기</li>
                <li><code>;extension=intl</code> 찾기</li>
                <li>세미콜론(;) 제거: <code>extension=intl</code></li>
                <li>웹 서버 재시작</li>
            </ol>
            
            <h4>Linux (Ubuntu/Debian):</h4>
            <pre>sudo apt-get install php8.1-intl
sudo systemctl restart apache2</pre>
            
            <h4>macOS:</h4>
            <pre>brew reinstall php</pre>
            
            <h4>호스팅 (cPanel):</h4>
            <ol>
                <li>cPanel 로그인</li>
                <li>"Select PHP Version" 클릭</li>
                <li>"Extensions" 탭</li>
                <li>"intl" 체크 ✅</li>
                <li>"Save" 클릭</li>
            </ol>
            
        <?php endif; ?>
        
        <hr style="margin: 30px 0;">
        
        <h3>추가 정보:</h3>
        <ul>
            <li>PHP Version: <strong><?= PHP_VERSION ?></strong></li>
            <li>Server: <strong><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></strong></li>
            <li>OS: <strong><?= PHP_OS ?></strong></li>
        </ul>
        
        <p style="text-align: center; margin-top: 30px;">
            <a href="/" style="color: #0284c7; text-decoration: none;">🏠 홈으로 돌아가기</a>
        </p>
    </div>
</body>
</html>






