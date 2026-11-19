<?php

declare(strict_types=1);

namespace G\DocToPdf;

use G\DocToPdf\Exception\DocToPdfException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

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
 * // or, in a PSR-7 application
 * return $pdf->toResponse($responseFactory, $streamFactory, 'invoice.pdf', Pdf::ATTACHMENT);
 * // or, in a plain PHP script
 * $pdf->output('invoice.pdf');
 * ```
 *
 * @package G\DocToPdf
 */
class Pdf
{
    /** Displayed by the browser. */
    public const INLINE = 'inline';

    /** Downloaded by the browser. */
    public const ATTACHMENT = 'attachment';

    private const DEFAULT_FILENAME = 'document.pdf';

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
     * Build a PSR-7 response carrying the PDF.
     *
     * The right choice in a PSR-15 application or a long-running worker
     * (FrankenPHP, RoadRunner...): nothing is sent until the response is emitted.
     *
     * @param string $filename    Name proposed to the browser
     * @param string $disposition {@see self::INLINE} (view) or {@see self::ATTACHMENT} (download)
     * @throws DocToPdfException If the disposition is invalid
     */
    public function toResponse(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        string $filename = self::DEFAULT_FILENAME,
        string $disposition = self::INLINE,
    ): ResponseInterface {
        $response = $responseFactory->createResponse();
        foreach ($this->headers($filename, $disposition) as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response->withBody($streamFactory->createStream($this->content));
    }

    /**
     * Send the PDF to the browser: HTTP headers, then the content.
     *
     * For plain PHP scripts only: in a PSR-15 application or a long-running
     * worker, use {@see toResponse()}.
     *
     * @param string $filename    Name proposed to the browser
     * @param string $disposition {@see self::INLINE} (view) or {@see self::ATTACHMENT} (download)
     * @throws DocToPdfException If the disposition is invalid
     */
    public function output(string $filename = self::DEFAULT_FILENAME, string $disposition = self::INLINE): void
    {
        foreach ($this->headers($filename, $disposition) as $name => $value) {
            header($name . ': ' . $value);
        }

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

    /**
     * Headers of a PDF response.
     *
     * @return array<string, string>
     * @throws DocToPdfException If the disposition is invalid
     */
    private function headers(string $filename, string $disposition): array
    {
        if ($disposition !== self::INLINE && $disposition !== self::ATTACHMENT) {
            throw new DocToPdfException(
                sprintf('Invalid disposition "%s": expected "inline" or "attachment".', $disposition)
            );
        }

        return [
            'Content-Type' => 'application/pdf',
            'Content-Length' => (string) strlen($this->content),
            'Content-Disposition' => $disposition . $this->dispositionFilename($filename),
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
        ];
    }

    /**
     * Filename parameters of Content-Disposition (RFC 6266): an ASCII fallback,
     * plus the UTF-8 name when it is not plain ASCII. Control characters, quotes,
     * backslashes and path separators never reach the header.
     */
    private function dispositionFilename(string $filename): string
    {
        $filename = trim(basename(str_replace('\\', '/', $filename)));
        $filename = (string) preg_replace('/[\x00-\x1F\x7F"]/', '', $filename);
        if ($filename === '') {
            $filename = self::DEFAULT_FILENAME;
        }

        $ascii = (string) preg_replace('/[^\x20-\x7E]/u', '_', $filename);
        $parameters = '; filename="' . $ascii . '"';

        if ($ascii !== $filename) {
            $parameters .= "; filename*=UTF-8''" . rawurlencode($filename);
        }

        return $parameters;
    }
}
