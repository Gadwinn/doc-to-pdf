<?php

declare(strict_types=1);

namespace G\DocToPdf\Tests\Stub;

use Psr\Http\Message\StreamInterface;

/**
 * Headers and body shared by the request and response stubs.
 */
trait MessageTrait
{
    /** @var array<string, list<string>> */
    private array $headers = [];

    private StreamInterface $body;

    /**
     * @param array<string, string|list<string>> $headers
     */
    private function setHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            $this->headers[$name] = array_values((array) $value);
        }
    }

    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion(string $version): static
    {
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return $this->findHeader($name) !== null;
    }

    public function getHeader(string $name): array
    {
        $found = $this->findHeader($name);

        return $found !== null ? $this->headers[$found] : [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): static
    {
        $new = $this->withoutHeader($name);
        $new->headers[$name] = array_values(array_map('strval', (array) $value));

        return $new;
    }

    public function withAddedHeader(string $name, $value): static
    {
        return $this->withHeader($name, [...$this->getHeader($name), ...array_map('strval', (array) $value)]);
    }

    public function withoutHeader(string $name): static
    {
        $new = clone $this;
        $found = $this->findHeader($name);
        if ($found !== null) {
            unset($new->headers[$found]);
        }

        return $new;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): static
    {
        $new = clone $this;
        $new->body = $body;

        return $new;
    }

    private function findHeader(string $name): ?string
    {
        foreach (array_keys($this->headers) as $key) {
            if (strcasecmp($key, $name) === 0) {
                return $key;
            }
        }

        return null;
    }
}
