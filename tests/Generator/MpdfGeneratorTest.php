<?php

declare(strict_types=1);

namespace G\DocToPdf\Tests\Generator;

use G\DocToPdf\Exception\DocToPdfException;
use G\DocToPdf\Generator\MpdfGenerator;
use PHPUnit\Framework\TestCase;

class MpdfGeneratorTest extends TestCase
{
    private MpdfGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new MpdfGenerator();
    }

    public function testSupportsAlwaysTrue(): void
    {
        $this->assertTrue($this->generator->supports(), 'MpdfGenerator should always be supported');
    }

    public function testGetPriorityReturnsFifty(): void
    {
        $this->assertSame(50, $this->generator->getPriority(), 'Priority should be 50 (medium/fallback)');
    }

    public function testGenerateSimpleHtml(): void
    {
        $html = '<html><body><h1>Test</h1></body></html>';

        $pdf = $this->generator->generate($html);

        $this->assertIsString($pdf, 'Should return string');
        $this->assertStringStartsWith('%PDF-', $pdf, 'Should be a valid PDF');
        $this->assertGreaterThan(100, strlen($pdf), 'PDF should have content');
    }

    public function testGenerateComplexHtml(): void
    {
        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Complex Document</title>
            <style>
                body { font-family: Arial, sans-serif; }
                table { border-collapse: collapse; width: 100%; }
                td, th { border: 1px solid black; padding: 8px; }
            </style>
        </head>
        <body>
            <h1>Invoice</h1>
            <table>
                <tr><th>Item</th><th>Price</th></tr>
                <tr><td>Product A</td><td>$10.00</td></tr>
                <tr><td>Product B</td><td>$15.00</td></tr>
            </table>
            <p>Total: $25.00</p>
        </body>
        </html>
        HTML;

        $pdf = $this->generator->generate($html);

        $this->assertStringStartsWith('%PDF-', $pdf, 'Should generate valid PDF from complex HTML');
        $this->assertGreaterThan(1000, strlen($pdf), 'Complex PDF should be larger');
    }

    public function testGenerateEmptyContent(): void
    {
        $pdf = $this->generator->generate('');

        $this->assertStringStartsWith('%PDF-', $pdf, 'Should generate PDF even for empty content');
    }

    public function testGenerateWithCustomOptions(): void
    {
        $html = '<html><body>Custom options test</body></html>';
        $options = [
            'format' => 'Letter',
            'margin_left' => 20,
            'margin_right' => 20,
        ];

        $pdf = $this->generator->generate($html, $options);

        $this->assertStringStartsWith('%PDF-', $pdf, 'Should accept custom mPDF options');
    }

    public function testGenerateHtmlWithUtf8Characters(): void
    {
        $html = '<html><body><p>Héllo Wörld! 日本語 العربية</p></body></html>';

        $pdf = $this->generator->generate($html);

        $this->assertStringStartsWith('%PDF-', $pdf, 'Should handle UTF-8 characters');
    }

    public function testGenerateHtmlWithImages(): void
    {
        // Create a simple base64 encoded 1x1 red pixel PNG
        $redPixel = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFBQIAX8jx0gAAAABJRU5ErkJggg==';
        
        $html = sprintf('<html><body><img src="%s" /></body></html>', $redPixel);

        $pdf = $this->generator->generate($html);

        $this->assertStringStartsWith('%PDF-', $pdf, 'Should handle embedded images');
    }

    public function testGeneratePreservesBasicFormatting(): void
    {
        $html = '<html><body><p><b>Bold</b> <i>Italic</i> <u>Underline</u></p></body></html>';

        $pdf = $this->generator->generate($html);

        $this->assertStringStartsWith('%PDF-', $pdf, 'Should handle basic HTML formatting');
    }
}
