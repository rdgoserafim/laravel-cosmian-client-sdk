<?php

namespace Cosmian\LaravelKmsSdk\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array generateKey(string $algorithm, array $options = [])
 * @method static array importKey(array $jwk, ?string $kid = null)
 * @method static array encrypt(string $keyId, string $plaintext, array $options = [])
 * @method static string decrypt(string $keyId, string $ciphertext, array $options = [])
 * @method static array sign(string $keyId, string $payload, array $options = [])
 * @method static bool verify(string $keyId, string $signature, string $payload, array $options = [])
 * @method static bool deleteKey(string $keyId)
 * @method static array listKeys()
 *
 * @see \Cosmian\LaravelKmsSdk\CosmianKmsClient
 */
class CosmianKms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'cosmian-kms';
    }
}