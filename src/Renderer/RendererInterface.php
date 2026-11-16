<?php

declare(strict_types=1);

namespace G\DocToPdf\Renderer;

use G\DocToPdf\Exception\DocToPdfException;

/**
 * Interface for template renderers.
 *
 * Renderers process template content and replace placeholders with actual data.
 * They handle variable substitution, loops, conditions, and other template logic.
 *
 * @package G\DocToPdf\Renderer
 */
interface RendererInterface
{
    /**
     * Render a template with provided data.
     *
     * @param string $template Template content (not file path)
     * @param array<string, mixed> $data Associative array of variables for the template
     * @return string Rendered content
     * @throws DocToPdfException If rendering fails
     */
    public function render(string $template, array $data): string;
}
