<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\EnhancedSlide;

use Ai\Domain\Entities\SlideEntity;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Bullet;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Slide;
use PhpOffice\PhpPresentation\Shape\Chart;
use PhpOffice\PhpPresentation\Shape\Drawing\File as DrawingFile;
use RuntimeException;

/**
 * GenSpark 스타일 슬라이드 내보내기 엔진
 * 
 * PPTX, PDF, HTML 등 다양한 형식으로 슬라이드 내보내기
 */
class SlideExporter
{
    private array $themeColors = [
        'professional' => [
            'primary' => '1f3a93',
            'secondary' => '2d5aa0',
            'accent' => '4a90e2',
            'background' => 'ffffff',
            'text' => '333333'
        ],
        'creative' => [
            'primary' => '8b5cf6',
            'secondary' => 'ec4899',
            'accent' => 'f59e0b',
            'background' => 'faf5ff',
            'text' => '1f2937'
        ],
        'minimal' => [
            'primary' => '000000',
            'secondary' => '6b7280',
            'accent' => '9ca3af',
            'background' => 'ffffff',
            'text' => '111827'
        ],
        'dark' => [
            'primary' => 'ffffff',
            'secondary' => 'e5e7eb',
            'accent' => '60a5fa',
            'background' => '111827',
            'text' => 'f9fafb'
        ],
        'colorful' => [
            'primary' => 'ef4444',
            'secondary' => '3b82f6',
            'accent' => '10b981',
            'background' => 'fef3c7',
            'text' => '1f2937'
        ],
    ];

    public function __construct(
        private string $uploadDir
    ) {}

    /**
     * PPTX 형식으로 슬라이드 내보내기
     */
    public function exportToPPTX(SlideEntity $entity): string
    {
        $presentation = new PhpPresentation();
        $presentation->getDocumentProperties()
            ->setTitle($entity->getTitle()->value)
            ->setCreator('GenSpark AI Presentation Generator')
            ->setDescription('AI-Generated Presentation');

        $theme = $entity->getTheme() ?? 'professional';
        $colors = $this->themeColors[$theme] ?? $this->themeColors['professional'];

        // Remove default slide
        $presentation->removeSlideByIndex(0);

        // Generate slides
        foreach ($entity->getSlides() as $slideData) {
            $this->createSlide($presentation, $slideData, $colors);
        }

        // Save to file
        $filename = $entity->getId()->getValue()->toString() . '.pptx';
        $filepath = $this->uploadDir . DIRECTORY_SEPARATOR . $filename;

        $writer = IOFactory::createWriter($presentation, 'PowerPoint2007');
        $writer->save($filepath);

        return $filepath;
    }

    private function createSlide(PhpPresentation $presentation, array $slideData, array $colors): void
    {
        $slide = $presentation->createSlide();
        $type = $slideData['type'] ?? 'content';

        // Set background
        $slide->getBackground()
            ->setColor(new Color($colors['background']));

        switch ($type) {
            case 'title':
                $this->createTitleSlide($slide, $slideData, $colors);
                break;
            case 'content':
                $this->createContentSlide($slide, $slideData, $colors);
                break;
            case 'data':
                $this->createDataSlide($slide, $slideData, $colors);
                break;
            case 'quote':
                $this->createQuoteSlide($slide, $slideData, $colors);
                break;
            case 'comparison':
                $this->createComparisonSlide($slide, $slideData, $colors);
                break;
            case 'conclusion':
                $this->createConclusionSlide($slide, $slideData, $colors);
                break;
            default:
                $this->createContentSlide($slide, $slideData, $colors);
        }

        // Add speaker notes
        if (!empty($slideData['speaker_notes'])) {
            $note = $slide->getNote();
            $noteShape = $note->createRichTextShape();
            $noteShape->setWidth(720)->setHeight(540);
            $noteShape->createTextRun($slideData['speaker_notes']);
        }
    }

    private function createTitleSlide(Slide $slide, array $data, array $colors): void
    {
        // Title
        $title = $slide->createRichTextShape();
        $title->setHeight(150)
            ->setWidth(900)
            ->setOffsetX(50)
            ->setOffsetY(200);
        
        $titleText = $title->createTextRun($data['title'] ?? 'Untitled');
        $titleText->getFont()
            ->setBold(true)
            ->setSize(44)
            ->setColor(new Color($colors['primary']));
        
        $title->getActiveParagraph()
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Subtitle
        if (!empty($data['subtitle'])) {
            $subtitle = $slide->createRichTextShape();
            $subtitle->setHeight(100)
                ->setWidth(900)
                ->setOffsetX(50)
                ->setOffsetY(370);
            
            $subtitleText = $subtitle->createTextRun($data['subtitle']);
            $subtitleText->getFont()
                ->setSize(24)
                ->setColor(new Color($colors['secondary']));
            
            $subtitle->getActiveParagraph()
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }

    private function createContentSlide(Slide $slide, array $data, array $colors): void
    {
        // Title
        $title = $slide->createRichTextShape();
        $title->setHeight(70)
            ->setWidth(900)
            ->setOffsetX(50)
            ->setOffsetY(30);
        
        $titleText = $title->createTextRun($data['title'] ?? 'Untitled');
        $titleText->getFont()
            ->setBold(true)
            ->setSize(32)
            ->setColor(new Color($colors['primary']));

        // Content
        if (!empty($data['content']) && is_array($data['content'])) {
            $content = $slide->createRichTextShape();
            $content->setHeight(400)
                ->setWidth(850)
                ->setOffsetX(75)
                ->setOffsetY(150);

            foreach ($data['content'] as $point) {
                $paragraph = $content->createParagraph();
                $paragraph->getBulletStyle()
                    ->setBulletType(Bullet::TYPE_BULLET)
                    ->setBulletColor(new Color($colors['accent']));
                
                $text = $paragraph->createTextRun($point);
                $text->getFont()
                    ->setSize(20)
                    ->setColor(new Color($colors['text']));
                
                $paragraph->setSpacingAfter(15);
            }
        }
    }

    private function createDataSlide(Slide $slide, array $data, array $colors): void
    {
        // Title
        $title = $slide->createRichTextShape();
        $title->setHeight(70)
            ->setWidth(900)
            ->setOffsetX(50)
            ->setOffsetY(30);
        
        $titleText = $title->createTextRun($data['title'] ?? 'Data Visualization');
        $titleText->getFont()
            ->setBold(true)
            ->setSize(32)
            ->setColor(new Color($colors['primary']));

        // Chart (if data provided)
        if (!empty($data['chart']) && is_array($data['chart'])) {
            $this->createChart($slide, $data['chart'], 100, 120, 450, 350);
        }

        // Key points
        if (!empty($data['content']) && is_array($data['content'])) {
            $content = $slide->createRichTextShape();
            $content->setHeight(350)
                ->setWidth(400)
                ->setOffsetX(560)
                ->setOffsetY(120);

            foreach ($data['content'] as $point) {
                $paragraph = $content->createParagraph();
                $paragraph->getBulletStyle()
                    ->setBulletType(Bullet::TYPE_BULLET)
                    ->setBulletColor(new Color($colors['accent']));
                
                $text = $paragraph->createTextRun($point);
                $text->getFont()
                    ->setSize(18)
                    ->setColor(new Color($colors['text']));
                
                $paragraph->setSpacingAfter(12);
            }
        }
    }

    private function createQuoteSlide(Slide $slide, array $data, array $colors): void
    {
        // Quote
        $quote = $slide->createRichTextShape();
        $quote->setHeight(300)
            ->setWidth(850)
            ->setOffsetX(75)
            ->setOffsetY(150);
        
        $quoteText = $quote->createTextRun('"' . ($data['quote'] ?? '') . '"');
        $quoteText->getFont()
            ->setSize(32)
            ->setItalic(true)
            ->setColor(new Color($colors['primary']));
        
        $quote->getActiveParagraph()
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Author
        if (!empty($data['author'])) {
            $author = $slide->createRichTextShape();
            $author->setHeight(60)
                ->setWidth(850)
                ->setOffsetX(75)
                ->setOffsetY(470);
            
            $authorText = $author->createTextRun('— ' . $data['author']);
            if (!empty($data['author_title'])) {
                $authorText = $author->createTextRun('— ' . $data['author'] . ', ' . $data['author_title']);
            }
            
            $authorText->getFont()
                ->setSize(20)
                ->setColor(new Color($colors['secondary']));
            
            $author->getActiveParagraph()
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
    }

    private function createComparisonSlide(Slide $slide, array $data, array $colors): void
    {
        // Title
        $title = $slide->createRichTextShape();
        $title->setHeight(70)
            ->setWidth(900)
            ->setOffsetX(50)
            ->setOffsetY(30);
        
        $titleText = $title->createTextRun($data['title'] ?? 'Comparison');
        $titleText->getFont()
            ->setBold(true)
            ->setSize(32)
            ->setColor(new Color($colors['primary']));

        // Left side
        if (!empty($data['left_side'])) {
            $left = $slide->createRichTextShape();
            $left->setHeight(400)
                ->setWidth(400)
                ->setOffsetX(50)
                ->setOffsetY(120);
            
            $leftTitle = $left->createParagraph();
            $leftTitleText = $leftTitle->createTextRun($data['left_side']['title'] ?? '');
            $leftTitleText->getFont()
                ->setBold(true)
                ->setSize(24)
                ->setColor(new Color($colors['primary']));
            
            if (!empty($data['left_side']['points'])) {
                foreach ($data['left_side']['points'] as $point) {
                    $paragraph = $left->createParagraph();
                    $paragraph->getBulletStyle()
                        ->setBulletType(Bullet::TYPE_BULLET);
                    $text = $paragraph->createTextRun($point);
                    $text->getFont()->setSize(16);
                }
            }
        }

        // Right side
        if (!empty($data['right_side'])) {
            $right = $slide->createRichTextShape();
            $right->setHeight(400)
                ->setWidth(400)
                ->setOffsetX(520)
                ->setOffsetY(120);
            
            $rightTitle = $right->createParagraph();
            $rightTitleText = $rightTitle->createTextRun($data['right_side']['title'] ?? '');
            $rightTitleText->getFont()
                ->setBold(true)
                ->setSize(24)
                ->setColor(new Color($colors['secondary']));
            
            if (!empty($data['right_side']['points'])) {
                foreach ($data['right_side']['points'] as $point) {
                    $paragraph = $right->createParagraph();
                    $paragraph->getBulletStyle()
                        ->setBulletType(Bullet::TYPE_BULLET);
                    $text = $paragraph->createTextRun($point);
                    $text->getFont()->setSize(16);
                }
            }
        }
    }

    private function createConclusionSlide(Slide $slide, array $data, array $colors): void
    {
        // Title
        $title = $slide->createRichTextShape();
        $title->setHeight(70)
            ->setWidth(900)
            ->setOffsetX(50)
            ->setOffsetY(30);
        
        $titleText = $title->createTextRun($data['title'] ?? 'Conclusion');
        $titleText->getFont()
            ->setBold(true)
            ->setSize(36)
            ->setColor(new Color($colors['primary']));

        // Key takeaways
        if (!empty($data['content']) && is_array($data['content'])) {
            $content = $slide->createRichTextShape();
            $content->setHeight(300)
                ->setWidth(850)
                ->setOffsetX(75)
                ->setOffsetY(130);

            foreach ($data['content'] as $point) {
                $paragraph = $content->createParagraph();
                $paragraph->getBulletStyle()
                    ->setBulletType(Bullet::TYPE_BULLET)
                    ->setBulletColor(new Color($colors['accent']));
                
                $text = $paragraph->createTextRun($point);
                $text->getFont()
                    ->setBold(true)
                    ->setSize(22)
                    ->setColor(new Color($colors['text']));
                
                $paragraph->setSpacingAfter(20);
            }
        }

        // Call to action
        if (!empty($data['call_to_action'])) {
            $cta = $slide->createRichTextShape();
            $cta->setHeight(80)
                ->setWidth(850)
                ->setOffsetX(75)
                ->setOffsetY(460);
            
            $ctaText = $cta->createTextRun($data['call_to_action']);
            $ctaText->getFont()
                ->setSize(24)
                ->setColor(new Color($colors['accent']));
            
            $cta->getActiveParagraph()
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }

    private function createChart(Slide $slide, array $chartData, int $x, int $y, int $width, int $height): void
    {
        $type = $chartData['type'] ?? 'bar';
        
        // Create chart based on type
        $chartType = Chart\Type\Bar::class;
        switch ($type) {
            case 'line':
                $chartType = Chart\Type\Line::class;
                break;
            case 'pie':
                $chartType = Chart\Type\Pie::class;
                break;
            case 'scatter':
                $chartType = Chart\Type\Scatter::class;
                break;
        }

        $chart = $slide->createChartShape();
        $chart->setResizeProportional(false)
            ->setHeight($height)
            ->setWidth($width)
            ->setOffsetX($x)
            ->setOffsetY($y);

        if (!empty($chartData['title'])) {
            $chart->getTitle()->setText($chartData['title']);
        }

        // Add data series
        if (isset($chartData['data'])) {
            $series = new Chart\Series(
                $chartData['data']['name'] ?? 'Data',
                $chartData['data']['values'] ?? []
            );
            $chart->getPlotArea()->setType(new $chartType());
            $chart->getPlotArea()->getSeries()->addSeries($series);
        }
    }

    /**
     * PDF 형식으로 슬라이드 내보내기 (PPTX를 먼저 생성 후 변환)
     */
    public function exportToPDF(SlideEntity $entity): string
    {
        // First export to PPTX
        $pptxPath = $this->exportToPPTX($entity);
        
        // Then convert to PDF (requires LibreOffice or similar)
        $pdfPath = str_replace('.pptx', '.pdf', $pptxPath);
        
        // TODO: Implement PDF conversion using LibreOffice or other tools
        // For now, we'll keep the PPTX file
        
        return $pptxPath;
    }

    /**
     * HTML 형식으로 슬라이드 내보내기
     */
    public function exportToHTML(SlideEntity $entity): string
    {
        $slides = $entity->getSlides();
        $theme = $entity->getTheme() ?? 'professional';
        $colors = $this->themeColors[$theme] ?? $this->themeColors['professional'];
        
        $html = $this->generateHTMLPresentation($entity->getTitle()->value, $slides, $colors);
        
        $filename = $entity->getId()->getValue()->toString() . '.html';
        $filepath = $this->uploadDir . DIRECTORY_SEPARATOR . $filename;
        
        file_put_contents($filepath, $html);
        
        return $filepath;
    }

    private function generateHTMLPresentation(string $title, array $slides, array $colors): string
    {
        $slidesHTML = '';
        foreach ($slides as $index => $slide) {
            $slidesHTML .= $this->generateSlideHTML($slide, $index, $colors);
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; overflow: hidden; }
        .presentation { width: 100vw; height: 100vh; position: relative; }
        .slide {
            width: 100%;
            height: 100%;
            display: none;
            padding: 60px 80px;
            background: #{$colors['background']};
            color: #{$colors['text']};
        }
        .slide.active { display: flex; flex-direction: column; }
        .slide h1 { color: #{$colors['primary']}; font-size: 3em; margin-bottom: 30px; }
        .slide h2 { color: #{$colors['secondary']}; font-size: 2em; margin-bottom: 20px; }
        .slide ul { list-style: none; }
        .slide li { 
            font-size: 1.5em; 
            margin: 15px 0; 
            padding-left: 30px;
            position: relative;
        }
        .slide li:before {
            content: '•';
            color: #{$colors['accent']};
            font-weight: bold;
            position: absolute;
            left: 0;
        }
        .navigation {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 20px;
            z-index: 100;
        }
        .nav-btn {
            background: #{$colors['primary']};
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .nav-btn:hover { opacity: 0.8; }
        .nav-btn:disabled { opacity: 0.3; cursor: not-allowed; }
        .slide-counter {
            position: fixed;
            bottom: 30px;
            right: 30px;
            color: #{$colors['secondary']};
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="presentation">
        {$slidesHTML}
    </div>
    
    <div class="navigation">
        <button class="nav-btn" id="prevBtn" onclick="previousSlide()">◀ Previous</button>
        <button class="nav-btn" id="nextBtn" onclick="nextSlide()">Next ▶</button>
    </div>
    
    <div class="slide-counter">
        <span id="currentSlide">1</span> / <span id="totalSlides">{count($slides)}</span>
    </div>
    
    <script>
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');
        const totalSlides = slides.length;
        
        function showSlide(index) {
            slides.forEach(s => s.classList.remove('active'));
            slides[index].classList.add('active');
            document.getElementById('currentSlide').textContent = index + 1;
            document.getElementById('prevBtn').disabled = index === 0;
            document.getElementById('nextBtn').disabled = index === totalSlides - 1;
        }
        
        function nextSlide() {
            if (currentSlide < totalSlides - 1) {
                currentSlide++;
                showSlide(currentSlide);
            }
        }
        
        function previousSlide() {
            if (currentSlide > 0) {
                currentSlide--;
                showSlide(currentSlide);
            }
        }
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' || e.key === ' ') {
                e.preventDefault();
                nextSlide();
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                previousSlide();
            }
        });
        
        showSlide(0);
    </script>
</body>
</html>
HTML;
    }

    private function generateSlideHTML(array $slide, int $index, array $colors): string
    {
        $type = $slide['type'] ?? 'content';
        $html = "<div class=\"slide\">\n";

        switch ($type) {
            case 'title':
                $html .= "<div style=\"text-align: center; margin: auto;\">\n";
                $html .= "<h1>" . htmlspecialchars($slide['title'] ?? '') . "</h1>\n";
                if (!empty($slide['subtitle'])) {
                    $html .= "<h2>" . htmlspecialchars($slide['subtitle']) . "</h2>\n";
                }
                $html .= "</div>\n";
                break;

            case 'quote':
                $html .= "<div style=\"text-align: center; margin: auto; max-width: 800px;\">\n";
                $html .= "<p style=\"font-size: 2em; font-style: italic; color: #{$colors['primary']};\">";
                $html .= '"' . htmlspecialchars($slide['quote'] ?? '') . '"';
                $html .= "</p>\n";
                if (!empty($slide['author'])) {
                    $html .= "<p style=\"margin-top: 30px; font-size: 1.5em; color: #{$colors['secondary']};\">";
                    $html .= "— " . htmlspecialchars($slide['author']);
                    if (!empty($slide['author_title'])) {
                        $html .= ", " . htmlspecialchars($slide['author_title']);
                    }
                    $html .= "</p>\n";
                }
                $html .= "</div>\n";
                break;

            default:
                $html .= "<h1>" . htmlspecialchars($slide['title'] ?? 'Untitled') . "</h1>\n";
                if (!empty($slide['content']) && is_array($slide['content'])) {
                    $html .= "<ul>\n";
                    foreach ($slide['content'] as $point) {
                        $html .= "<li>" . htmlspecialchars($point) . "</li>\n";
                    }
                    $html .= "</ul>\n";
                }
        }

        $html .= "</div>\n";
        return $html;
    }
}

