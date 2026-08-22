<?php

namespace Cosmian\LaravelKmsSdk;

use Cosmian\LaravelKmsSdk\Contracts\CosmianKmsInterface;
use Cosmian\LaravelKmsSdk\Exceptions\CosmianKmsException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CosmianKmsClient implements CosmianKmsInterface
{
    private const API_PREFIX = '/v1/crypto';

    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function generateKey(string $algorithm, array $options = []): array
    {
        return $this->request('POST', '/keys', array_merge($options, [
            'algorithm' => $algorithm,
        ]));
    }

    public function importKey(array $jwk, ?string $kid = null): array
    {
        $payload = ['jwk' => $jwk];

        if ($kid !== null) {
            $payload['kid'] = $kid;
        }

        return $this->request('POST', '/keys', $payload);
    }

    public function encrypt(string $keyId, string $plaintext, array $options = []): array
    {
        return $this->request('POST', '/encrypt', array_merge($options, [
            'key_id' => $keyId,
            'plaintext' => base64_encode($plaintext),
        ]));
    }

    public function decrypt(string $keyId, string $ciphertext, array $options = []): string
    {
        $response = $this->request('POST', '/decrypt', array_merge($options, [
            'key_id' => $keyId,
            'ciphertext' => $ciphertext,
        ]));
        $encodedPlaintext = $response['plaintext'] ?? $response['data'] ?? null;

        if (! is_string($encodedPlaintext) || ($plaintext = base64_decode($encodedPlaintext, true)) === false) {
            throw new CosmianKmsException('Cosmian KMS returned an invalid base64 plaintext.');
        }

        return $plaintext;
    }

    public function sign(string $keyId, string $payload, array $options = []): array
    {
        return $this->request('POST', '/sign', array_merge($options, [
            'key_id' => $keyId,
            'payload' => base64_encode($payload),
        ]));
    }

    public function verify(string $keyId, string $signature, string $payload, array $options = []): bool
    {
        $response = $this->request('POST', '/verify', array_merge($options, [
            'key_id' => $keyId,
            'signature' => $signature,
            'payload' => base64_encode($payload),
        ]));
        $valid = $response['valid'] ?? $response['verified'] ?? null;

        if (! is_bool($valid)) {
            throw new CosmianKmsException('Cosmian KMS returned an invalid verification result.');
        }

        return $valid;
    }

    public function deleteKey(string $keyId): bool
    {
        $this->request('DELETE', '/keys/'.rawurlencode($keyId));

        return true;
    }

    public function listKeys(): array
    {
        return $this->request('GET', '/keys');
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        $url = self::API_PREFIX.$path;

        Log::debug('Cosmian KMS request', [
            'method' => $method,
            'url' => rtrim((string) ($this->config['url'] ?? ''), '/').$url,
        ]);

        try {
            $request = Http::baseUrl(rtrim((string) ($this->config['url'] ?? ''), '/'))
                ->acceptJson()
                ->asJson()
                ->withToken((string) ($this->config['api_key'] ?? ''))
                ->timeout((int) ($this->config['timeout'] ?? 30))
                ->withOptions(['verify' => (bool) ($this->config['verify_ssl'] ?? true)])
                ->retry(max(1, (int) ($this->config['retry_times'] ?? 3)), 100, null, false);

            $options = $payload === [] ? [] : ['json' => $payload];
            $response = $request->send($method, $url, $options);
        } catch (ConnectionException $exception) {
            throw new CosmianKmsException(
                'Unable to connect to Cosmian KMS: '.$exception->getMessage(),
                null,
                null,
                $exception
            );
        } catch (Throwable $exception) {
            if ($exception instanceof CosmianKmsException) {
                throw $exception;
            }

            throw new CosmianKmsException(
                'Cosmian KMS request failed: '.$exception->getMessage(),
                null,
                null,
                $exception
            );
        }

        $this->logResponse($method, $url, $response);

        if ($response->failed()) {
            throw CosmianKmsException::fromResponse($response);
        }

        if ($response->status() === 204 || $response->body() === '') {
            return [];
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new CosmianKmsException(
                'Cosmian KMS returned an invalid JSON response.',
                $response->status(),
                $response->body()
            );
        }

        return $data;
    }

    private function logResponse(string $method, string $url, Response $response): void
    {
        Log::debug('Cosmian KMS response', [
            'method' => $method,
            'url' => rtrim((string) ($this->config['url'] ?? ''), '/').$url,
            'status' => $response->status(),
        ]);
    }
}