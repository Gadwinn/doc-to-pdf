<?php

declare(strict_types=1);

namespace G\DocToPdf\Tests\Stub;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * In-memory PSR-7 stream; can pretend not to be seekable.
 */
final class Stream implements StreamInterface
{
    private int $position = 0;

    public function __construct(private string $content = '', private readonly bool $seekable = true)
    {
    }

    public function __toString(): string
    {
        if ($this->seekable) {
            $this->position = 0;
        }

        return $this->getContents();
    }

    public function close(): void
    {
    }

    public function detach()
    {
        return null;
    }

    public function getSize(): ?int
    {
        return strlen($this->content);
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function eof(): bool
    {
        return $this->position >= strlen($this->content);
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!$this->seekable) {
            throw new RuntimeException('Not seekable');
        }

        $this->position = $offset;
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        throw new RuntimeException('Not writable');
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        $chunk = substr($this->content, $this->position, $length);
        $this->position += strlen($chunk);

        return $chunk;
    }

    public function getContents(): string
    {
        $rest = substr($this->content, $this->position);
        $this->position = strlen($this->content);

        return $rest;
    }

    public function getMetadata(?string $key = null)
    {
        return $key === null ? [] : null;
    }
}
