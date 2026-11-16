<?php

declare(strict_types=1);

namespace G\DocToPdf\Detector;

use G\DocToPdf\Exception\DocToPdfException;
use InvalidArgumentException;

/**
 * Detects document format using magic bytes and MIME types.
 *
 * This class provides reliable format detection for supported document types
 * by examining file signatures (magic bytes) and falling back to MIME type
 * detection when necessary.
 *
 * Supported formats: ODT, ODS, HTML
 *
 * @package G\DocToPdf\Detector
 */
class FormatDetector
{
    public const string HTML = 'html';
    public const string ODT = 'odt';
    public const string ODS = 'ods';
    public const string UNKNOWN = 'unknown';

    private const string MIME_ODT = 'application/vnd.oasis.opendocument.text';
    private const string MIME_ODS = 'application/vnd.oasis.opendocument.spreadsheet';
    private const string MIME_HTML = 'text/html';
    private const string ZIP_SIGNATURE = "PK\x03\x04";
    private const int SIGNATURE_LENGTH = 4;

    /**
     * Detect the format of a file.
     *
     * Tries magic bytes detection first (more reliable), then falls back
     * to MIME type detection if magic bytes are inconclusive.
     *
     * @param string $filePath Path to the file to detect
     * @return string Format identifier: 'odt', 'ods', 'html', 'unknown'
     * @throws InvalidArgumentException If file doesn't exist or isn't readable
     */
    public function detect(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("File not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new InvalidArgumentException("File is not readable: {$filePath}");
        }

        // Try magic bytes first (more reliable)
        $format = $this->detectByMagicBytes($filePath);
        if ($format !== null) {
            return $format;
        }

        // Fall back to MIME type
        $format = $this->detectByMimeType($filePath);
        if ($format !== null) {
            return $format;
        }

        return self::UNKNOWN;
    }

    /**
     * Detect format using magic bytes (file signature).
     *
     * Examines the file's binary signature to determine its type.
     * For ZIP-based formats (ODT, ODS), also checks internal structure.
     *
     * @param string $filePath Path to the file to detect
     * @return string|null Format identifier or null if detection fails
     */
    public function detectByMagicBytes(string $filePath): ?string
    {
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            return null;
        }

        // Read first bytes for magic number detection
        $bytes = fread($handle, self::SIGNATURE_LENGTH);
        fclose($handle);

        if ($bytes === false || strlen($bytes) < self::SIGNATURE_LENGTH) {
            return null;
        }

        // Check for ZIP signature (ODT, ODS are ZIP archives)
        if ($bytes === self::ZIP_SIGNATURE) {
            return $this->detectZipBasedFormat($filePath);
        }

        // Check for HTML
        $content = file_get_contents($filePath, false, null, 0, 1024);
        if ($content !== false) {
            $contentLower = strtolower($content);
            if (strpos($contentLower, '<!doctype') !== false || strpos($contentLower, '<html') !== false) {
                return self::HTML;
            }
        }

        return null;
    }

    /**
     * Detect ZIP-based format (ODT, ODS) by examining internal structure.
     *
     * @param string $filePath Path to the ZIP file
     * @return string|null Format identifier or null if detection fails
     */
    private function detectZipBasedFormat(string $filePath): ?string
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return null;
        }

        // Check for mimetype file (ODT/ODS have this)
        $mimeContent = $zip->getFromName('mimetype');
        $zip->close();

        if ($mimeContent === false) {
            return null;
        }

        $mimeType = trim($mimeContent);

        return match ($mimeType) {
            self::MIME_ODT => self::ODT,
            self::MIME_ODS => self::ODS,
            default => null,
        };
    }

    /**
     * Detect format using MIME type.
     *
     * Uses PHP's fileinfo extension to determine file type.
     * This is a fallback method when magic bytes are inconclusive.
     *
     * @param string $filePath Path to the file to detect
     * @return string|null Format identifier or null if detection fails
     */
    public function detectByMimeType(string $filePath): ?string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return null;
        }

        $mimeType = finfo_file($finfo, $filePath);

        if ($mimeType === false) {
            return null;
        }

        return match ($mimeType) {
            self::MIME_ODT => self::ODT,
            self::MIME_ODS => self::ODS,
            self::MIME_HTML => self::HTML,
            default => null,
        };
    }
}
