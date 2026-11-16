<?php

declare(strict_types=1);

namespace G\DocToPdf\Reader;

use G\DocToPdf\Detector\FormatDetector;
use G\DocToPdf\Exception\DocToPdfException;

/**
 * ODT/ODS document reader.
 *
 * Reads OpenDocument format files (ODT for text, ODS for spreadsheets).
 * This reader validates that the file can be opened but doesn't process
 * the content directly - that's handled by odtphp during the rendering phase.
 *
 * @package G\DocToPdf\Reader
 */
class OdtReader implements ReaderInterface
{
    /**
     * Read ODT/ODS document.
     *
     * For ODT/ODS files, we validate the file exists and return the path.
     * The actual processing with odtphp happens in the rendering phase
     * since odtphp has its own templating system.
     *
     * @param string $filePath Path to the ODT/ODS file
     * @return string File path (validated)
     * @throws DocToPdfException If file cannot be read or is invalid
     */
    public function read(string $filePath): string
    {
        // Validate file exists and is readable
        if (!file_exists($filePath)) {
            throw new DocToPdfException("ODT/ODS file not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new DocToPdfException("ODT/ODS file is not readable: {$filePath}");
        }

        // Validate it's a valid ZIP file (ODT/ODS are ZIP archives)
        $zip = new \ZipArchive();
        $result = $zip->open($filePath, \ZipArchive::RDONLY);

        if ($result !== true) {
            throw new DocToPdfException("Invalid ODT/ODS file (not a valid ZIP archive): {$filePath}");
        }

        $zip->close();

        // Return the file path - it will be processed by odtphp later
        return $filePath;
    }

    /**
     * Check if this reader supports the given format.
     *
     * @param string $format Format identifier
     * @return bool True if format is 'odt' or 'ods', false otherwise
     */
    public function supports(string $format): bool
    {
        return in_array($format, [FormatDetector::ODT, FormatDetector::ODS], true);
    }
}
