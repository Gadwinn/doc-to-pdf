<?php

declare(strict_types=1);

namespace G\DocToPdf\Tests\Detector;

use G\DocToPdf\Detector\FormatDetector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FormatDetectorTest extends TestCase
{
    private FormatDetector $detector;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->detector = new FormatDetector();
        $this->tempDir = sys_get_temp_dir() . '/doctopdf_test_' . uniqid();
        mkdir($this->tempDir, 0o777, true);
    }

    protected function tearDown(): void
    {
        // Clean up temporary files
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

    public function testDetectHtmlByContent(): void
    {
        $htmlFile = $this->tempDir . '/test.html';
        file_put_contents($htmlFile, '<!DOCTYPE html><html><head><title>Test</title></head><body>Hello</body></html>');

        $format = $this->detector->detect($htmlFile);

        $this->assertSame('html', $format, 'Should detect HTML format');
    }

    public function testDetectHtmlWithLowercaseDoctype(): void
    {
        $htmlFile = $this->tempDir . '/test.html';
        file_put_contents($htmlFile, '<!doctype html><html><body>Test</body></html>');

        $format = $this->detector->detect($htmlFile);

        $this->assertSame('html', $format, 'Should detect HTML with lowercase doctype');
    }

    public function testDetectHtmlWithJustHtmlTag(): void
    {
        $htmlFile = $this->tempDir . '/test.html';
        file_put_contents($htmlFile, '<html><body>Test</body></html>');

        $format = $this->detector->detect($htmlFile);

        $this->assertSame('html', $format, 'Should detect HTML even without DOCTYPE');
    }

    public function testDetectOdtByMagicBytes(): void
    {
        $odtFile = $this->createMinimalOdt();

        $format = $this->detector->detect($odtFile);

        $this->assertSame('odt', $format, 'Should detect ODT format');
    }

    public function testDetectOdsByMagicBytes(): void
    {
        $odsFile = $this->createMinimalOds();

        $format = $this->detector->detect($odsFile);

        $this->assertSame('ods', $format, 'Should detect ODS format');
    }

    public function testDetectUnknownFormat(): void
    {
        $txtFile = $this->tempDir . '/test.txt';
        file_put_contents($txtFile, 'Just some plain text');

        $format = $this->detector->detect($txtFile);

        $this->assertSame('unknown', $format, 'Should return unknown for unsupported formats');
    }

    public function testFileNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File not found:');

        $this->detector->detect('/non/existent/file.txt');
    }

    public function testDetectByMagicBytes(): void
    {
        $htmlFile = $this->tempDir . '/test.html';
        file_put_contents($htmlFile, '<!DOCTYPE html><html><body>Test</body></html>');

        $format = $this->detector->detectByMagicBytes($htmlFile);

        $this->assertSame('html', $format, 'detectByMagicBytes should detect HTML');
    }

    public function testDetectByMimeType(): void
    {
        $htmlFile = $this->tempDir . '/test.html';
        file_put_contents($htmlFile, '<html><body>Test</body></html>');

        $format = $this->detector->detectByMimeType($htmlFile);

        // MIME detection may return 'html' or null depending on system
        $this->assertTrue(
            $format === 'html' || $format === null,
            'detectByMimeType should return html or null for HTML file'
        );
    }

    /**
     * Create a minimal valid ODT file for testing.
     *
     * @return string Path to the created ODT file
     */
    private function createMinimalOdt(): string
    {
        $odtFile = $this->tempDir . '/test.odt';
        $zip = new \ZipArchive();

        if ($zip->open($odtFile, \ZipArchive::CREATE) !== true) {
            $this->fail('Failed to create ZIP archive for ODT');
        }

        // Add mimetype file (must be first, uncompressed)
        $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');

        // Add minimal content.xml
        $contentXml = '<?xml version="1.0" encoding="UTF-8"?>
<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0">
<office:body><office:text><text:p>Test</text:p></office:text></office:body>
</office:document-content>';
        $zip->addFromString('content.xml', $contentXml);

        $zip->close();

        return $odtFile;
    }

    /**
     * Create a minimal valid ODS file for testing.
     *
     * @return string Path to the created ODS file
     */
    private function createMinimalOds(): string
    {
        $odsFile = $this->tempDir . '/test.ods';
        $zip = new \ZipArchive();

        if ($zip->open($odsFile, \ZipArchive::CREATE) !== true) {
            $this->fail('Failed to create ZIP archive for ODS');
        }

        // Add mimetype file
        $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.spreadsheet');

        // Add minimal content.xml
        $contentXml = '<?xml version="1.0" encoding="UTF-8"?>
<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0">
<office:body><office:spreadsheet><table:table table:name="Sheet1"></table:table></office:spreadsheet></office:body>
</office:document-content>';
        $zip->addFromString('content.xml', $contentXml);

        $zip->close();

        return $odsFile;
    }
}
