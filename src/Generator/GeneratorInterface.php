<?php

declare(strict_types=1);

namespace G\DocToPdf\Generator;

use G\DocToPdf\Exception\DocToPdfException;

/**
 * Interface for PDF generators.
 *
 * Generators convert document content to PDF format. Multiple implementations
 * can exist with different strategies (LibreOffice, mPDF, etc.), and the
 * best available generator is selected automatically based on priority.
 *
 * @package G\DocToPdf\Generator
 */
interface GeneratorInterface
{
    /**
     * Check if this generator is available on the current system.
     *
     * For example, LibreOffice generator checks if the binary is installed,
     * while pure PHP generators always return true.
     *
     * @return bool True if generator can be used, false otherwise
     */
    public function supports(): bool;

    /**
     * Generate PDF from content.
     *
     * @param string $content Content to convert (may be HTML, file path, etc. depending on generator)
     * @param array<string, mixed> $options Additional generation options
     * @return string PDF content as binary string
     * @throws DocToPdfException If PDF generation fails
     */
    public function generate(string $content, array $options = []): string;

    /**
     * Get generator priority.
     *
     * Higher priority generators are preferred when multiple generators
     * are available. Typical values:
     * - 100: High priority (e.g., LibreOffice - best fidelity)
     * - 50: Medium priority (e.g., mPDF - fallback)
     * - 10: Low priority (basic implementations)
     *
     * @return int Priority value (higher = more preferred)
     */
    public function getPriority(): int;
}
