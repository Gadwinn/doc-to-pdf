<?php

declare(strict_types=1);

namespace G\DocToPdf\Tests\Reader;

use G\DocToPdf\Exception\DocToPdfException;
use G\DocToPdf\Reader\OdtReader;
use PHPUnit\Framework\TestCase;

class OdtReaderTest extends TestCase
{
    private OdtReader $reader;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->reader = new OdtReader();
        $this->tempDir = sys_get_temp_dir() . '/doctopdf_odtreader_' . uniqid();
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

    public function testSupportsOdtFormat(): void
    {
        $this->assertTrue($this->reader->supports('odt'), 'Should support odt format');
    }

    public function testSupportsOdsFormat(): void
    {
        $this->assertTrue($this->reader->supports('ods'), 'Should support ods format');
    }

    public function testDoesNotSupportHtmlFormat(): void
    {
        $this->assertFalse($this->reader->supports('html'), 'Should not support html format');
    }

    public function testDoesNotSupportOtherFormats(): void
    {
        $this->assertFalse($this->reader->supports('pdf'), 'Should not support pdf format');
        $this->assertFalse($this->reader->supports('docx'), 'Should not support docx format');
        $this->assertFalse($this->reader->supports('txt'), 'Should not support txt format');
    }

    public function testReadValidOdtFile(): void
    {
        $odtFile = $this->createMinimalOdt();

        $result = $this->reader->read($odtFile);

        $this->assertSame($odtFile, $result, 'Should return the file path for valid ODT');
    }

    public function testReadValidOdsFile(): void
    {
        $odsFile = $this->createMinimalOds();

        $result = $this->reader->read($odsFile);

        $this->assertSame($odsFile, $result, 'Should return the file path for valid ODS');
    }

    public function testThrowsExceptionForMissingFile(): void
    {
        $this->expectException(DocToPdfException::class);
        $this->expectExceptionMessage('ODT/ODS file not found:');

        $this->reader->read('/non/existent/file.odt');
    }

    public function testThrowsExceptionForInvalidZipFile(): void
    {
        $invalidFile = $this->tempDir . '/invalid.odt';
        file_put_contents($invalidFile, 'This is not a valid ZIP file');

        $this->expectException(DocToPdfException::class);
        $this->expectExceptionMessage('Invalid ODT/ODS file (not a valid ZIP archive):');

        $this->reader->read($invalidFile);
    }

    public function testThrowsExceptionForNonReadableFile(): void
    {
        // Skip on Windows where chmod doesn't work reliably
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('File permission tests are not reliable on Windows');
        }

        $odtFile = $this->createMinimalOdt();

        // Make file non-readable
        if (chmod($odtFile, 0o000)) {
            $this->expectException(DocToPdfException::class);
            $this->expectExceptionMessage('ODT/ODS file is not readable:');

            try {
                $this->reader->read($odtFile);
            } finally {
                // Restore permissions for cleanup
                chmod($odtFile, 0o644);
            }
        } else {
            $this->markTestSkipped('Cannot change file permissions on this system');
        }
    }

    /**
     * Create a minimal valid ODT file for testing.
     */
    private function createMinimalOdt(): string
    {
        $odtFile = $this->tempDir . '/test.odt';
        $zip = new \ZipArchive();

        if ($zip->open($odtFile, \ZipArchive::CREATE) !== true) {
            $this->fail('Failed to create ZIP archive for ODT');
        }

        $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');
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
     */
    private function createMinimalOds(): string
    {
        $odsFile = $this->tempDir . '/test.ods';
        $zip = new \ZipArchive();

        if ($zip->open($odsFile, \ZipArchive::CREATE) !== true) {
            $this->fail('Failed to create ZIP archive for ODS');
        }

        $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.spreadsheet');
        $contentXml = '<?xml version="1.0" encoding="UTF-8"?>
<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0">
<office:body><office:spreadsheet><table:table table:name="Sheet1"></table:table></office:spreadsheet></office:body>
</office:document-content>';
        $zip->addFromString('content.xml', $contentXml);
        $zip->close();

        return $odsFile;
    }
}
