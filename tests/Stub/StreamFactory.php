<?php

declare(strict_types=1);

namespace G\DocToPdf\Tests\Stub;

use LogicException;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

final class StreamFactory implements StreamFactoryInterface
{
    public function createStream(string $content = ''): StreamInterface
    {
        return new Stream($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        throw new LogicException('Not used by the tests');
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        throw new LogicException('Not used by the tests');
    }
}
