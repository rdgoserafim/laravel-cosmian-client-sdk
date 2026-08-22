<?php

namespace Cosmian\LaravelKmsSdk\Contracts;

interface CosmianKmsInterface
{
    public function generateKey(string $algorithm, array $options = []): array;

    public function importKey(array $jwk, ?string $kid = null): array;

    public function encrypt(string $keyId, string $plaintext, array $options = []): array;

    public function decrypt(string $keyId, string $ciphertext, array $options = []): string;

    public function sign(string $keyId, string $payload, array $options = []): array;

    public function verify(string $keyId, string $signature, string $payload, array $options = []): bool;

    public function deleteKey(string $keyId): bool;

    public function listKeys(): array;
}