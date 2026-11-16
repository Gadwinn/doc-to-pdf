<?php

declare(strict_types=1);

namespace G\DocToPdf\Tests\Generator;

use G\DocToPdf\Exception\DocToPdfException;
use G\DocToPdf\Generator\LibreOfficeGenerator;
use PHPUnit\Framework\TestCase;

class LibreOfficeGeneratorTest extends TestCase
{
    private LibreOfficeGenerator $generator;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->generator = new LibreOfficeGenerator();
        $this->tempDir = sys_get_temp_dir() . '/doctopdf_lotest_' . uniqid();
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

    public function testGetPriorityReturnsOneHundred(): void
    {
        $this->assertSame(100, $this->generator->getPriority(), 'Priority should be 100 (highest)');
    }

    public function testSupportsDetectsLibreOffice(): void
    {
        $supports = $this->generator->supports();

        $this->assertIsBool($supports, 'supports() should return boolean');
        
        // We can't guarantee LibreOffice is installed, so just verify it returns a boolean
        if ($supports) {
            $this->markTestIncomplete('LibreOffice is available - manual verification needed');
        } else {
            $this->assertFalse($supports, 'LibreOffice not available on this system');
        }
    }

    public function testSupportsWithCustomPath(): void
    {
        $generator = new LibreOfficeGenerator('/non/existent/path');
        
        $supports = $generator->supports();
        
        // Should still check default paths even if custom path is invalid
        $this->assertIsBool($supports);
    }

    public function testGenerateThrowsExceptionWhenLibreOfficeNotAvailable(): void
    {
        // Create generator with invalid path to ensure LibreOffice is not found
        $generator = new LibreOfficeGenerator('/invalid/path/to/soffice');
        
        if ($generator->supports()) {
            $this->markTestSkipped('LibreOffice is available on system, cannot test unavailability');
        }

        $odtFile = $this->createMinimalOdt();

        $this->expectException(DocToPdfException::class);
        $this->expectExceptionMessage('LibreOffice is not available');

        $generator->generate($odtFile);
    }

    public function testGenerateThrowsExceptionForNonExistentFile(): void
    {
        if (!$this->generator->supports()) {
            $this->markTestSkipped('LibreOffice not available on this system');
        }

        $this->expectException(DocToPdfException::class);
        $this->expectExceptionMessage('Source file not found');

        $this->generator->generate('/non/existent/file.odt');
    }

    public function testGenerateWithValidOdtFile(): void
    {
        if (!$this->generator->supports()) {
            $this->markTestSkipped('LibreOffice not available on this system');
        }

        $odtFile = $this->createMinimalOdt();

        $pdf = $this->generator->generate($odtFile);

        $this->assertIsString($pdf, 'Should return string');
        $this->assertStringStartsWith('%PDF-', $pdf, 'Should generate valid PDF');
        $this->assertGreaterThan(100, strlen($pdf), 'PDF should have content');
    }

    public function testTemporaryFileCleanup(): void
    {
        if (!$this->generator->supports()) {
            $this->markTestSkipped('LibreOffice not available on this system');
        }

        $odtFile = $this->createMinimalOdt();
        
        // Count temp files before
        $tempDirsBefore = glob(sys_get_temp_dir() . '/doctopdf_lo_*');
        $countBefore = $tempDirsBefore !== false ? count($tempDirsBefore) : 0;

        $pdf = $this->generator->generate($odtFile);

        // Count temp files after - should be cleaned up
        $tempDirsAfter = glob(sys_get_temp_dir() . '/doctopdf_lo_*');
        $countAfter = $tempDirsAfter !== false ? count($tempDirsAfter) : 0;

        $this->assertLessThanOrEqual(
            $countBefore,
            $countAfter,
            'Temporary directories should be cleaned up'
        );
    }

    public function testGenerateWithOdsFile(): void
    {
        if (!$this->generator->supports()) {
            $this->markTestSkipped('LibreOffice not available on this system');
        }

        $odsFile = $this->createMinimalOds();

        $pdf = $this->generator->generate($odsFile);

        $this->assertStringStartsWith('%PDF-', $pdf, 'Should generate PDF from ODS file');
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
        
        $manifestXml = '<?xml version="1.0" encoding="UTF-8"?>
<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">
<manifest:file-entry manifest:full-path="/" manifest:media-type="application/vnd.oasis.opendocument.text"/>
<manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/>
</manifest:manifest>';
        $zip->addFromString('META-INF/manifest.xml', $manifestXml);
        
        $contentXml = '<?xml version="1.0" encoding="UTF-8"?>
<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0">
<office:body>
<office:text>
<text:p>Test Document</text:p>
</office:text>
</office:body>
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
        
        $manifestXml = '<?xml version="1.0" encoding="UTF-8"?>
<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">
<manifest:file-entry manifest:full-path="/" manifest:media-type="application/vnd.oasis.opendocument.spreadsheet"/>
<manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/>
</manifest:manifest>';
        $zip->addFromString('META-INF/manifest.xml', $manifestXml);
        
        $contentXml = '<?xml version="1.0" encoding="UTF-8"?>
<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0">
<office:body>
<office:spreadsheet>
<table:table table:name="Sheet1">
<table:table-row>
<table:table-cell><text:p>Test</text:p></table:table-cell>
</table:table-row>
</table:table>
</office:spreadsheet>
</office:body>
</office:document-content>';
        $zip->addFromString('content.xml', $contentXml);

        $zip->close();
        return $odsFile;
    }
}
