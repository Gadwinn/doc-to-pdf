<?php

declare(strict_types=1);

namespace G\DocToPdf\Tests\Reader;

use G\DocToPdf\Exception\DocToPdfException;
use G\DocToPdf\Reader\HtmlReader;
use PHPUnit\Framework\TestCase;

class HtmlReaderTest extends TestCase
{
    private HtmlReader $reader;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->reader = new HtmlReader();
        $this->tempDir = sys_get_temp_dir() . '/doctopdf_htmlreader_' . uniqid();
        mkdir($this->tempDir, 0o777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
    }

    public function testReadHtmlFile(): void
    {
        $htmlContent = '<!DOCTYPE html><html><body>Hello World</body></html>';
        $htmlFile = $this->tempDir . '/test.html';
        file_put_contents($htmlFile, $htmlContent);

        $content = $this->reader->read($htmlFile);

        $this->assertSame($htmlContent, $content, 'Should return exact HTML content');
    }

    public function testSupportsHtmlFormat(): void
    {
        $this->assertTrue($this->reader->supports('html'), 'Should support html format');
    }

    public function testDoesNotSupportOdtFormat(): void
    {
        $this->assertFalse($this->reader->supports('odt'), 'Should not support odt format');
    }

    public function testDoesNotSupportOdsFormat(): void
    {
        $this->assertFalse($this->reader->supports('ods'), 'Should not support ods format');
    }

    public function testDoesNotSupportOtherFormats(): void
    {
        $this->assertFalse($this->reader->supports('pdf'), 'Should not support pdf format');
        $this->assertFalse($this->reader->supports('docx'), 'Should not support docx format');
        $this->assertFalse($this->reader->supports('txt'), 'Should not support txt format');
    }

    public function testThrowsExceptionForMissingFile(): void
    {
        $this->expectException(DocToPdfException::class);
        $this->expectExceptionMessage('Failed to read HTML file:');

        $this->reader->read('/non/existent/file.html');
    }

    public function testReadComplexHtml(): void
    {
        $htmlContent = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Test Page</title>
            <style>body { font-family: Arial; }</style>
        </head>
        <body>
            <h1>Title</h1>
            <p>Paragraph with special chars: &amp; &lt; &gt;</p>
        </body>
        </html>
        HTML;

        $htmlFile = $this->tempDir . '/complex.html';
        file_put_contents($htmlFile, $htmlContent);

        $content = $this->reader->read($htmlFile);

        $this->assertSame($htmlContent, $content, 'Should preserve complex HTML exactly');
    }

    public function testReadEmptyHtmlFile(): void
    {
        $htmlFile = $this->tempDir . '/empty.html';
        file_put_contents($htmlFile, '');

        $content = $this->reader->read($htmlFile);

        $this->assertSame('', $content, 'Should handle empty file');
    }
}
