<?php

namespace Spatie\Robots;

use InvalidArgumentException;

class RobotsHeaders
{
    protected $robotHeadersProperties = [];

    public static function readFrom(string $source): self
    {
        $content = @file_get_contents($source);

        if ($content === false) {
            throw new InvalidArgumentException("Could not read from source `{$source}`");
        }

        return new self($http_response_header ?? []);
    }

    public static function create(array $headers): self
    {
        return new self($headers);
    }

    public function __construct(array $headers)
    {
        $this->robotHeadersProperties = $this->parseHeaders($headers);
    }

    public function mayIndex(string $userAgent = '*'): bool
    {
        return ! $this->noindex($userAgent);
    }

    public function mayFollow(string $userAgent = '*'): bool
    {
        return ! $this->nofollow($userAgent);
    }

    public function noindex(string $userAgent = '*'): bool
    {
        return $this->robotHeadersProperties[$userAgent]['noindex'] ?? false;
    }

    public function nofollow(string $userAgent = '*'): bool
    {
        return $this->robotHeadersProperties[$userAgent]['nofollow'] ?? false;
    }

    protected function parseHeaders(array $headers): array
    {
        $robotHeadders = $this->filterRobotHeaders($headers);

        return array_reduce($robotHeadders, function (array $parsedHeaders, $header) {
            $header = $this->normalizeHeaders($header);

            $headerParts = explode(':', $header);

            $userAgent = count($headerParts) === 3
                ? trim($headerParts[1])
                : '*';

            $options = end($headerParts);

            $parsedHeaders[$userAgent] = [
                'noindex' => strpos(strtolower($options), 'noindex') !== false,
                'nofollow' => strpos(strtolower($options), 'nofollow') !== false,
            ];

            return $parsedHeaders;
        }, []);
    }

    protected function filterRobotHeaders(array $headers): array
    {
        return array_filter($headers, function ($header) {
            $header = $this->normalizeHeaders($header);

            return strpos(strtolower($header), 'x-robots-tag') === 0;
        });
    }

    protected function normalizeHeaders($headers): string
    {
        return implode(',', (array) $headers);
    }
}
