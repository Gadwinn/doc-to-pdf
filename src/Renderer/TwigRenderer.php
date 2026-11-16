<?php

declare(strict_types=1);

namespace G\DocToPdf\Renderer;

use G\DocToPdf\Exception\DocToPdfException;
use Twig\Environment;
use Twig\Error\Error as TwigError;
use Twig\Loader\ArrayLoader;

/**
 * Twig-based template renderer.
 *
 * Uses the Twig templating engine to process templates with variables,
 * loops, conditions, and filters. Provides a powerful and flexible
 * templating solution for HTML content.
 *
 * Example usage:
 * ```php
 * $renderer = new TwigRenderer();
 * $html = $renderer->render('<h1>{{ title }}</h1>', ['title' => 'Hello']);
 * ```
 *
 * @package G\DocToPdf\Renderer
 */
class TwigRenderer implements RendererInterface
{
    /** @var array<string, mixed> */
    private array $options;

    /**
     * Constructor.
     *
     * @param array<string, mixed> $options Twig environment options
     *                                      Default: ['autoescape' => false, 'strict_variables' => true]
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'autoescape' => 'html',       // Data is escaped: use the |raw filter for trusted HTML
            'strict_variables' => true,   // Fail on undefined variables
        ], $options);
    }

    /**
     * Render a template using Twig.
     *
     * @param string $template Template content with Twig syntax
     * @param array<string, mixed> $data Variables to pass to the template
     * @return string Rendered content
     * @throws DocToPdfException If Twig rendering fails
     */
    public function render(string $template, array $data): string
    {
        try {
            $loader = new ArrayLoader(['template' => $template]);
            $twig = new Environment($loader, $this->options);

            return $twig->render('template', $data);
        } catch (TwigError $e) {
            throw new DocToPdfException(
                "Twig rendering error: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }
}
