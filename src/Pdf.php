<?php

declare(strict_types=1);

namespace G\DocToPdf;

use G\DocToPdf\Exception\DocToPdfException;

/**
 * PDF document result object.
 *
 * Represents a generated PDF document and provides methods to save,
 * output, or retrieve the PDF content.
 *
 * Example usage:
 * ```php
 * $pdf = new Pdf($pdfBinaryContent);
 * $pdf->save('invoice.pdf');
 * // or
 * $pdf->output(); // Send to browser
 * ```
 *
 * @package G\DocToPdf
 */
class Pdf
{
    private string $content;

    /**
     * Constructor.
     *
     * @param string $content PDF binary content
     */
    public function __construct(string $content)
    {
        $this->content = $content;
    }

    /**
     * Save PDF to a file.
     *
     * @param string $filePath Path where to save the PDF file
     * @throws DocToPdfException If file cannot be written
     */
    public function save(string $filePath): void
    {
        $result = @file_put_contents($filePath, $this->content);

        if ($result === false) {
            throw new DocToPdfException("Failed to save PDF to file: {$filePath}");
        }
    }

    /**
     * Output PDF to browser with appropriate headers.
     *
     * Sends the PDF directly to the browser for display or download.
     * This method sends HTTP headers and outputs the content.
     */
    public function output(): void
    {
        header('Content-Type: application/pdf');
        header('Content-Length: ' . strlen($this->content));
        header('Content-Disposition: inline; filename="document.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $this->content;
    }

    /**
     * Get raw PDF content.
     *
     * @return string PDF binary content
     */
    public function getContent(): string
    {
        return $this->content;
    }
}
