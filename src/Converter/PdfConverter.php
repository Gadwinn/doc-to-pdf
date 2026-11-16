<?php

declare(strict_types=1);

namespace G\DocToPdf\Converter;

use G\DocToPdf\Detector\FormatDetector;
use G\DocToPdf\Exception\DocToPdfException;
use G\DocToPdf\Generator\GeneratorInterface;
use G\DocToPdf\Generator\LibreOfficeGenerator;
use G\DocToPdf\Generator\MpdfGenerator;
use G\DocToPdf\Pdf;
use G\DocToPdf\Reader\HtmlReader;
use G\DocToPdf\Reader\OdtReader;
use G\DocToPdf\Reader\ReaderInterface;
use G\DocToPdf\Renderer\TwigRenderer;
use InvalidArgumentException;
use Odtphp\Odf;
use Odtphp\Exceptions\OdfException;

/**
 * Main PDF converter class.
 *
 * Orchestrates the entire conversion process: format detection, reading,
 * template rendering, and PDF generation. Supports multiple input formats
 * (ODT, ODS, HTML) and automatically selects the best available generator.
 *
 * Example usage:
 * ```php
 * $converter = new PdfConverter('template.odt');
 * $pdf = $converter->render(['customer' => 'John Doe', 'total' => 100]);
 * $pdf->save('invoice.pdf');
 * ```
 *
 * @package G\DocToPdf\Converter
 */
class PdfConverter
{
    public const string DRIVER_AUTO = 'auto';
    public const string DRIVER_PHP = 'php';
    public const string DRIVER_LIBREOFFICE = 'libreoffice';

    private const int TEMP_NAME_BYTES = 8;

    private string $templatePath;
    /** @var array<string, mixed> */
    private array $options;
    private string $format;
    private ReaderInterface $reader;
    private TwigRenderer $twigRenderer;
    /** @var array<GeneratorInterface> */
    private array $generators = [];

    /**
     * Constructor.
     *
     * @param string $templatePath Path to the template file
     * @param array<string, mixed> $options Configuration options
     *        - driver: 'auto'|'libreoffice'|'php' - PDF generator selection
     *        - libreoffice_path: Custom path to LibreOffice binary
     *        - temp_dir: Temporary files directory
     *        - twig_options: Options for Twig renderer
     * @throws InvalidArgumentException If template file doesn't exist
     * @throws DocToPdfException If format is unsupported
     */
    public function __construct(string $templatePath, array $options = [])
    {
        if (!file_exists($templatePath)) {
            throw new InvalidArgumentException("Template file not found: {$templatePath}");
        }

        $this->templatePath = $templatePath;
        $this->options = array_merge([
            'driver' => self::DRIVER_AUTO,
            'libreoffice_path' => null,
            'temp_dir' => sys_get_temp_dir(),
            'twig_options' => [],
        ], $options);

        // Detect format
        $detector = new FormatDetector();
        $this->format = $detector->detect($templatePath);

        if ($this->format === FormatDetector::UNKNOWN) {
            throw new DocToPdfException("Unsupported file format: {$templatePath}");
        }

        // Initialize reader
        $this->initializeReader();

        // Initialize Twig renderer (used for HTML)
        /** @var array<string, mixed> */
        $twigOptions = isset($this->options['twig_options']) && is_array($this->options['twig_options'])
            ? $this->options['twig_options']
            : [];
        $this->twigRenderer = new TwigRenderer($twigOptions);

        // Register generators
        $this->registerGenerators();
    }

    /**
     * Render template with data and generate PDF.
     *
     * @param array<string, mixed> $data Template variables
     * @return Pdf Generated PDF object
     * @throws DocToPdfException If conversion fails
     */
    public function render(array $data = []): Pdf
    {
        if ($this->format === FormatDetector::HTML) {
            return $this->renderHtml($data);
        }

        if (in_array($this->format, [FormatDetector::ODT, FormatDetector::ODS], true)) {
            return $this->renderOdt($data);
        }

        throw new DocToPdfException("Unsupported format: {$this->format}");
    }

    /**
     * Render HTML template to PDF.
     *
     * @param array<string, mixed> $data Template variables
     * @return Pdf Generated PDF
     * @throws DocToPdfException If conversion fails
     */
    private function renderHtml(array $data): Pdf
    {
        // Read HTML template
        $htmlTemplate = $this->reader->read($this->templatePath);

        // Render with Twig
        $renderedHtml = $this->twigRenderer->render($htmlTemplate, $data);

        // Generate PDF using mPDF (HTML → PDF)
        $generator = $this->selectBestGenerator(FormatDetector::HTML);
        $pdfContent = $generator->generate($renderedHtml);

        return new Pdf($pdfContent);
    }

    /**
     * Render ODT/ODS template to PDF.
     *
     * @param array<string, mixed> $data Template variables
     * @return Pdf Generated PDF
     * @throws DocToPdfException If conversion fails
     */
    private function renderOdt(array $data): Pdf
    {
        try {
            // Load ODT/ODS with odtphp
            $odf = new Odf($this->templatePath);

            // Apply data to template
            $this->applyDataToOdt($odf, $data);

            // Save filled template to temp file
            if (!is_string($this->options['temp_dir'])) {
                throw new DocToPdfException('temp_dir option must be a string');
            }
            $tempDir = $this->options['temp_dir'];
            $tempFile = $tempDir . '/doctopdf_filled_'
                . bin2hex(random_bytes(self::TEMP_NAME_BYTES)) . '.' . $this->format;

            try {
                $odf->saveToDisk($tempFile);

                // Generate PDF
                $generator = $this->selectBestGenerator(FormatDetector::ODT);

                return new Pdf($generator->generate($tempFile));
            } finally {
                // The filled document may hold personal data: never leave it behind
                @unlink($tempFile);
            }
        } catch (OdfException $e) {
            throw new DocToPdfException(
                "ODT/ODS processing error: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Apply data to ODT template using odtphp.
     *
     * @param Odf $odf ODT/ODS document object
     * @param array<string, mixed> $data Template data
     */
    private function applyDataToOdt(Odf $odf, array $data): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Handle arrays as segments (loops)
                try {
                    $segment = $odf->setSegment($key);
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            foreach ($item as $itemKey => $itemValue) {
                                if (is_scalar($itemValue) || $itemValue === null) {
                                    $segment->setVars($itemKey, (string)$itemValue);
                                }
                            }
                        }
                        $segment->merge();
                    }
                } catch (OdfException $e) {
                    // Segment might not exist in template, skip
                    continue;
                }
            } elseif (is_scalar($value) || $value === null) {
                // Handle scalar values as simple variables
                $odf->setVars($key, (string)$value);
            }
        }
    }

    /**
     * Initialize the appropriate reader for the detected format.
     *
     * @throws DocToPdfException If no reader supports the format
     */
    private function initializeReader(): void
    {
        $readers = [
            new HtmlReader(),
            new OdtReader(),
        ];

        foreach ($readers as $reader) {
            if ($reader->supports($this->format)) {
                $this->reader = $reader;
                return;
            }
        }

        throw new DocToPdfException("No reader available for format: {$this->format}");
    }

    /**
     * Register all available PDF generators.
     */
    private function registerGenerators(): void
    {
        $libreOfficePath = null;
        if (isset($this->options['libreoffice_path'])) {
            if (!is_string($this->options['libreoffice_path'])) {
                throw new DocToPdfException('libreoffice_path option must be a string');
            }
            $libreOfficePath = $this->options['libreoffice_path'];
        }

        $this->generators = [
            new MpdfGenerator(),
            new LibreOfficeGenerator($libreOfficePath),
        ];
    }

    /**
     * Select the best available generator for the content type.
     *
     * @param string $contentType Type of content ('html' or 'odt')
     * @return GeneratorInterface Best available generator
     * @throws DocToPdfException If no generator is available
     */
    private function selectBestGenerator(string $contentType): GeneratorInterface
    {
        // Filter by driver option if specified
        if (!is_string($this->options['driver'])) {
            throw new DocToPdfException('driver option must be a string');
        }
        $driver = $this->options['driver'];
        if ($driver !== self::DRIVER_AUTO) {
            $filtered = $this->filterGeneratorsByDriver($driver);
            if (empty($filtered)) {
                throw new DocToPdfException(
                    "Requested driver '{$driver}' is not available"
                );
            }
            $this->generators = $filtered;
        }

        // Filter by content type preference
        $suitable = $this->filterGeneratorsByContentType($contentType);

        if (empty($suitable)) {
            throw new DocToPdfException('No PDF generator available');
        }

        // Sort by priority (highest first)
        usort($suitable, function (GeneratorInterface $a, GeneratorInterface $b) {
            return $b->getPriority() <=> $a->getPriority();
        });

        return $suitable[0];
    }

    /**
     * Filter generators by driver name.
     *
     * @param string $driverName Driver name ('libreoffice' or 'php')
     * @return array<GeneratorInterface> Filtered generators
     */
    private function filterGeneratorsByDriver(string $driverName): array
    {
        $filtered = [];

        foreach ($this->generators as $generator) {
            if (!$generator->supports()) {
                continue;
            }

            if ($driverName === self::DRIVER_LIBREOFFICE && $generator instanceof LibreOfficeGenerator) {
                $filtered[] = $generator;
            } elseif ($driverName === self::DRIVER_PHP && $generator instanceof MpdfGenerator) {
                $filtered[] = $generator;
            }
        }

        return $filtered;
    }

    /**
     * Filter generators by content type suitability.
     *
     * @param string $contentType Type of content
     * @return array<GeneratorInterface> Suitable generators
     */
    private function filterGeneratorsByContentType(string $contentType): array
    {
        $suitable = [];

        foreach ($this->generators as $generator) {
            if (!$generator->supports()) {
                continue;
            }

            // For ODT/ODS, prefer LibreOffice
            if ($contentType === FormatDetector::ODT && $generator instanceof LibreOfficeGenerator) {
                $suitable[] = $generator;
            }

            // For HTML or as fallback, use mPDF
            if ($generator instanceof MpdfGenerator) {
                $suitable[] = $generator;
            }
        }

        return $suitable;
    }
}
