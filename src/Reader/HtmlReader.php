<?php

declare(strict_types=1);

namespace G\DocToPdf\Reader;

use G\DocToPdf\Detector\FormatDetector;
use G\DocToPdf\Exception\DocToPdfException;

/**
 * HTML document reader.
 *
 * Reads HTML files and returns their content as a string.
 * The content can then be processed by a template renderer.
 *
 * @package G\DocToPdf\Reader
 */
class HtmlReader implements ReaderInterface
{
    /**
     * Read HTML content from a file.
     *
     * @param string $filePath Path to the HTML file
     * @return string HTML content
     * @throws DocToPdfException If file cannot be read
     */
    public function read(string $filePath): string
    {
        $content = @file_get_contents($filePath);

        if ($content === false) {
            throw new DocToPdfException("Failed to read HTML file: {$filePath}");
        }

        return $content;
    }

    /**
     * Check if this reader supports the given format.
     *
     * @param string $format Format identifier
     * @return bool True if format is 'html', false otherwise
     */
    public function supports(string $format): bool
    {
        return $format === FormatDetector::HTML;
    }
}
