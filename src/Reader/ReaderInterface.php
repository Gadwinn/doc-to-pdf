<?php

declare(strict_types=1);

namespace G\DocToPdf\Reader;

use G\DocToPdf\Exception\DocToPdfException;

/**
 * Interface for document readers.
 *
 * Readers are responsible for loading and reading document content from
 * various file formats. Each reader implementation handles a specific
 * format or set of formats.
 *
 * @package G\DocToPdf\Reader
 */
interface ReaderInterface
{
    /**
     * Read document content from a file.
     *
     * @param string $filePath Path to the document file
     * @return string Document content or file path (depending on reader implementation)
     * @throws DocToPdfException If file cannot be read or format is invalid
     */
    public function read(string $filePath): string;

    /**
     * Check if this reader supports a given format.
     *
     * @param string $format Format identifier (e.g., 'html', 'odt', 'ods')
     * @return bool True if this reader supports the format, false otherwise
     */
    public function supports(string $format): bool;
}
