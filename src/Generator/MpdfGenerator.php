<?php

declare(strict_types=1);

namespace G\DocToPdf\Generator;

use G\DocToPdf\Exception\DocToPdfException;
use Mpdf\Mpdf;
use Mpdf\MpdfException;

/**
 * mPDF-based PDF generator.
 *
 * Converts HTML content to PDF using the mPDF library. This generator
 * is always available (pure PHP) and serves as a fallback when other
 * generators are not available.
 *
 * Priority: 50 (medium - fallback option)
 *
 * @package G\DocToPdf\Generator
 */
class MpdfGenerator implements GeneratorInterface
{
    private const PRIORITY = 50;

    /**
     * Check if mPDF is available (always true - pure PHP).
     *
     * @return bool Always returns true
     */
    public function supports(): bool
    {
        return true;
    }

    /**
     * Get generator priority.
     *
     * @return int Priority value (50 = medium/fallback)
     */
    public function getPriority(): int
    {
        return self::PRIORITY;
    }

    /**
     * Generate PDF from HTML content using mPDF.
     *
     * @param string $content HTML content to convert
     * @param array<string, mixed> $options mPDF configuration options
     * @return string PDF binary content
     * @throws DocToPdfException If PDF generation fails
     */
    public function generate(string $content, array $options = []): string
    {
        $config = array_merge([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
        ], $options);

        try {
            $mpdf = new Mpdf($config);
            $mpdf->WriteHTML($content);

            $pdfContent = $mpdf->Output('', 'S');
            if (!is_string($pdfContent)) {
                throw new DocToPdfException('mPDF Output failed to return string');
            }

            return $pdfContent;
        } catch (MpdfException $e) {
            throw new DocToPdfException(
                "mPDF generation failed: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }
}
