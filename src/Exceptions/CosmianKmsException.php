<?php

namespace Cosmian\LaravelKmsSdk\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;
use Throwable;

class CosmianKmsException extends RuntimeException
{
    private ?int $statusCode;

    private ?string $responseBody;

    public function __construct(
        string $message,
        ?int $statusCode = null,
        ?string $responseBody = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);

        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
    }

    public static function fromResponse(Response $response): self
    {
        $body = $response->json();
        $message = is_array($body)
            ? ($body['message'] ?? $body['error'] ?? null)
            : null;

        if (is_array($message)) {
            $message = $message['message'] ?? json_encode($message);
        }

        return new self(
            is_string($message) && $message !== '' ? $message : 'Cosmian KMS request failed.',
            $response->status(),
            $response->body()
        );
    }

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }

    public function responseBody(): ?string
    {
        return $this->responseBody;
    }
}