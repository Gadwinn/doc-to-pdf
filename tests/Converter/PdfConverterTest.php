<?php

declare(strict_types=1);

namespace G\DocToPdf\Tests\Converter;

use G\DocToPdf\Converter\PdfConverter;
use G\DocToPdf\Exception\DocToPdfException;
use G\DocToPdf\Pdf;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PdfConverterTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/../fixtures';
        if (!is_dir($this->fixturesDir)) {
            mkdir($this->fixturesDir, 0o777, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up temp fixtures
        $files = glob($this->fixturesDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    public function testConstructorWithNonExistentFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Template file not found');

        new PdfConverter('/path/to/nonexistent/file.html');
    }

    public function testConstructorWithUnsupportedFormat(): void
    {
        $filePath = $this->fixturesDir . '/document.txt';
        file_put_contents($filePath, 'Plain text content');

        $this->expectException(DocToPdfException::class);
        $this->expectExceptionMessage('Unsupported file format');

        new PdfConverter($filePath);
    }

    public function testHtmlConversionBasic(): void
    {
        $filePath = $this->fixturesDir . '/simple.html';
        file_put_contents($filePath, '<!DOCTYPE html><html><body><h1>Hello World</h1></body></html>');

        $converter = new PdfConverter($filePath);
        $pdf = $converter->render();

        $this->assertInstanceOf(Pdf::class, $pdf);
        $this->assertStringContainsString('%PDF', $pdf->getContent());
    }

    public function testHtmlConversionWithVariables(): void
    {
        $filePath = $this->fixturesDir . '/variables.html';
        file_put_contents($filePath, '<!DOCTYPE html><html><body><h1>Hello {{ name }}</h1></body></html>');

        $converter = new PdfConverter($filePath);
        $pdf = $converter->render(['name' => 'John']);

        $this->assertInstanceOf(Pdf::class, $pdf);
        $content = $pdf->getContent();
        $this->assertStringContainsString('%PDF', $content);
        // mPDF will have embedded "Hello John" in the PDF
    }

    public function testHtmlConversionWithLoop(): void
    {
        $template = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
<ul>
{% for item in items %}
    <li>{{ item.name }}: {{ item.price }}</li>
{% endfor %}
</ul>
</body>
</html>
HTML;

        $filePath = $this->fixturesDir . '/loop.html';
        file_put_contents($filePath, $template);

        $converter = new PdfConverter($filePath);
        $pdf = $converter->render([
            'items' => [
                ['name' => 'Product 1', 'price' => '10'],
                ['name' => 'Product 2', 'price' => '20'],
            ],
        ]);

        $this->assertInstanceOf(Pdf::class, $pdf);
        $this->assertStringContainsString('%PDF', $pdf->getContent());
    }

    public function testHtmlConversionWithCondition(): void
    {
        $template = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
{% if show_title %}
    <h1>{{ title }}</h1>
{% endif %}
<p>Content</p>
</body>
</html>
HTML;

        $filePath = $this->fixturesDir . '/condition.html';
        file_put_contents($filePath, $template);

        $converter = new PdfConverter($filePath);
        $pdf = $converter->render([
            'show_title' => true,
            'title' => 'My Title',
        ]);

        $this->assertInstanceOf(Pdf::class, $pdf);
        $this->assertStringContainsString('%PDF', $pdf->getContent());
    }

    public function testOdtConversionDetection(): void
    {
        // Create minimal ODT file structure
        $odtPath = $this->fixturesDir . '/template.odt';
        $this->createMinimalOdt($odtPath);

        // Just verify constructor works with ODT
        $converter = new PdfConverter($odtPath);
        $this->assertInstanceOf(PdfConverter::class, $converter);
    }

    public function testOdsConversionDetection(): void
    {
        // Create minimal ODS file structure
        $odsPath = $this->fixturesDir . '/template.ods';
        $this->createMinimalOds($odsPath);

        // Just verify constructor works with ODS
        $converter = new PdfConverter($odsPath);
        $this->assertInstanceOf(PdfConverter::class, $converter);
    }

    public function testDriverOptionPhp(): void
    {
        $filePath = $this->fixturesDir . '/simple.html';
        file_put_contents($filePath, '<!DOCTYPE html><html><body>Test</body></html>');

        $converter = new PdfConverter($filePath, ['driver' => 'php']);
        $pdf = $converter->render();

        $this->assertInstanceOf(Pdf::class, $pdf);
        $this->assertStringContainsString('%PDF', $pdf->getContent());
    }

    public function testDriverOptionLibreOfficeWhenNotAvailable(): void
    {
        $filePath = $this->fixturesDir . '/simple.html';
        file_put_contents($filePath, '<!DOCTYPE html><html><body>Test</body></html>');

        // Force non-existent LibreOffice path
        $options = [
            'driver' => 'libreoffice',
            'libreoffice_path' => '/nonexistent/libreoffice',
        ];

        $this->expectException(DocToPdfException::class);
        $this->expectExceptionMessage("Requested driver 'libreoffice' is not available");

        $converter = new PdfConverter($filePath, $options);
        $converter->render();
    }

    public function testCustomTempDir(): void
    {
        $customTemp = $this->fixturesDir . '/custom_temp';
        if (!is_dir($customTemp)) {
            mkdir($customTemp, 0o777, true);
        }

        $filePath = $this->fixturesDir . '/simple.html';
        file_put_contents($filePath, '<!DOCTYPE html><html><body>Test</body></html>');

        $converter = new PdfConverter($filePath, ['temp_dir' => $customTemp]);
        $pdf = $converter->render();

        $this->assertInstanceOf(Pdf::class, $pdf);

        // Clean up
        @rmdir($customTemp);
    }

    /**
     * Create minimal valid ODT file for testing.
     */
    private function createMinimalOdt(string $path): void
    {
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // mimetype file (uncompressed)
        $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');
        $zip->setCompressionName('mimetype', \ZipArchive::CM_STORE);

        // META-INF/manifest.xml
        $manifest = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">
    <manifest:file-entry manifest:full-path="/" manifest:media-type="application/vnd.oasis.opendocument.text"/>
    <manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/>
</manifest:manifest>
XML;
        $zip->addFromString('META-INF/manifest.xml', $manifest);

        // content.xml with variables
        $content = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0">
    <office:body>
        <office:text>
            <text:p>{title}</text:p>
            <text:p>{content}</text:p>
        </office:text>
    </office:body>
</office:document-content>
XML;
        $zip->addFromString('content.xml', $content);

        $zip->close();
    }

    /**
     * Create minimal valid ODS file for testing.
     */
    private function createMinimalOds(string $path): void
    {
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // mimetype file
        $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.spreadsheet');
        $zip->setCompressionName('mimetype', \ZipArchive::CM_STORE);

        // META-INF/manifest.xml
        $manifest = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">
    <manifest:file-entry manifest:full-path="/" manifest:media-type="application/vnd.oasis.opendocument.spreadsheet"/>
    <manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/>
</manifest:manifest>
XML;
        $zip->addFromString('META-INF/manifest.xml', $manifest);

        // content.xml
        $content = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0">
    <office:body>
        <office:spreadsheet>
            <table:table>
                <table:table-row>
                    <table:table-cell><text:p>{value1}</text:p></table:table-cell>
                    <table:table-cell><text:p>{value2}</text:p></table:table-cell>
                </table:table-row>
            </table:table>
        </office:spreadsheet>
    </office:body>
</office:document-content>
XML;
        $zip->addFromString('content.xml', $content);

        $zip->close();
    }
}
