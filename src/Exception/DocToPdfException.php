<?php

declare(strict_types=1);

namespace G\DocToPdf\Exception;

/**
 * Base exception for all DocToPdf library-specific errors.
 *
 * This exception is thrown when library-specific errors occur during
 * document conversion, such as unsupported formats, conversion failures,
 * or generator unavailability.
 *
 * @package G\DocToPdf\Exception
 */
class DocToPdfException extends \Exception
{
}
