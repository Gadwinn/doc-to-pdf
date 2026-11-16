<?php

declare(strict_types=1);

namespace G\DocToPdf\Tests;

use G\DocToPdf\Exception\DocToPdfException;
use G\DocToPdf\Pdf;
use PHPUnit\Framework\TestCase;

class PdfTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/doctopdf_pdf_' . uniqid();
        mkdir($this->tempDir, 0o777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            if ($files !== false) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
            @rmdir($this->tempDir);
        }
    }

    public function testConstructor(): void
    {
        $content = '%PDF-1.4 fake content';
        
        $pdf = new Pdf($content);

        $this->assertInstanceOf(Pdf::class, $pdf);
    }

    public function testGetContent(): void
    {
        $content = '%PDF-1.4 test content';
        $pdf = new Pdf($content);

        $result = $pdf->getContent();

        $this->assertSame($content, $result, 'Should return exact content');
    }

    public function testSaveToFile(): void
    {
        $content = '%PDF-1.4 save test';
        $pdf = new Pdf($content);
        $filePath = $this->tempDir . '/test.pdf';

        $pdf->save($filePath);

        $this->assertFileExists($filePath, 'File should be created');
        $this->assertSame($content, file_get_contents($filePath), 'File content should match');
    }

    public function testSaveThrowsExceptionOnFailure(): void
    {
        $content = '%PDF-1.4 fail test';
        $pdf = new Pdf($content);
        
        // Try to save to an invalid/unwritable path
        $invalidPath = '/invalid/path/that/does/not/exist/test.pdf';

        $this->expectException(DocToPdfException::class);
        $this->expectExceptionMessage('Failed to save PDF to file:');

        $pdf->save($invalidPath);
    }

    public function testSaveOverwritesExistingFile(): void
    {
        $filePath = $this->tempDir . '/overwrite.pdf';
        
        // Create initial file
        file_put_contents($filePath, 'old content');
        
        $newContent = '%PDF-1.4 new content';
        $pdf = new Pdf($newContent);

        $pdf->save($filePath);

        $this->assertSame($newContent, file_get_contents($filePath), 'Should overwrite existing file');
    }

    public function testOutputSendsContent(): void
    {
        $content = '%PDF-1.4 output test';
        $pdf = new Pdf($content);

        ob_start();
        $pdf->output();
        $output = ob_get_clean();

        $this->assertSame($content, $output, 'Should output the PDF content');
    }

    public function testOutputSetsHeaders(): void
    {
        $content = '%PDF-1.4';
        $pdf = new Pdf($content);

        // We can't directly test headers in unit tests, but we can verify output() runs
        ob_start();
        $pdf->output();
        $output = ob_get_clean();

        $this->assertNotEmpty($output, 'Should produce output');
        
        // Note: In a real browser context, headers would be sent
        // Testing headers requires integration/functional tests
    }

    public function testSaveEmptyContent(): void
    {
        $pdf = new Pdf('');
        $filePath = $this->tempDir . '/empty.pdf';

        $pdf->save($filePath);

        $this->assertFileExists($filePath);
        $this->assertSame('', file_get_contents($filePath), 'Should save empty content');
    }

    public function testGetContentPreservesEncoding(): void
    {
        // Binary content with various bytes
        $content = "\x25\x50\x44\x46\x2D\x31\x2E\x34\x0A\xC3\xA9"; // %PDF-1.4 with é
        $pdf = new Pdf($content);

        $result = $pdf->getContent();

        $this->assertSame($content, $result, 'Should preserve binary encoding exactly');
    }
}
