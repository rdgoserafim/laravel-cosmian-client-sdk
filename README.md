# Laravel Cosmian Client SDK

Laravel package for consuming the Cosmian KMS REST crypto API through Laravel's HTTP client.

## Requirements

- PHP 8.0 or newer (Laravel 12 requires PHP 8.2 or newer)
- Laravel 9, 10, 11, or 12

## Installation

Install the package with Composer:

```bash
composer require rdgoserafim/laravel-cosmian-client-sdk
```

Laravel discovers the service provider and facade automatically. Publish the configuration file:

```bash
php artisan vendor:publish --tag=cosmian-kms-config
```

Add the connection settings to `.env`:

```dotenv
COSMIAN_KMS_URL=http://localhost:9998
COSMIAN_KMS_API_KEY=your-api-key
COSMIAN_KMS_TIMEOUT=30
COSMIAN_KMS_RETRY_TIMES=3
COSMIAN_KMS_VERIFY_SSL=true
```

The published `config/cosmian-kms.php` contains all available options. Keep SSL verification enabled outside controlled local development environments.

## Usage

Use dependency injection through the contract:

```php
use Cosmian\LaravelKmsSdk\Contracts\CosmianKmsInterface;

final class EncryptionService
{
    public function __construct(private CosmianKmsInterface $kms)
    {
    }

    public function encrypt(string $value): array
    {
        return $this->kms->encrypt('my-key-id', $value, [
            'algorithm' => 'A256GCM',
        ]);
    }
}
```

Or use the facade:

```php
use Cosmian\LaravelKmsSdk\Facades\CosmianKms;

$key = CosmianKms::generateKey('RSA', ['size' => 2048]);
$imported = CosmianKms::importKey($jwk, 'optional-key-id');
$encrypted = CosmianKms::encrypt($key['kid'], 'sensitive value', [
    'algorithm' => 'RSA-OAEP',
]);
$plaintext = CosmianKms::decrypt($key['kid'], $encrypted['ciphertext']);
$signed = CosmianKms::sign($key['kid'], 'message', ['algorithm' => 'RS256']);
$valid = CosmianKms::verify($key['kid'], $signed['signature'], 'message');
$keys = CosmianKms::listKeys();
$deleted = CosmianKms::deleteKey($key['kid']);
```

## Error handling

Failed requests throw `Cosmian\LaravelKmsSdk\Exceptions\CosmianKmsException`. The exception exposes the API message, HTTP status, and raw response body:

```php
use Cosmian\LaravelKmsSdk\Exceptions\CosmianKmsException;

try {
    $keys = CosmianKms::listKeys();
} catch (CosmianKmsException $exception) {
    report($exception);

    $status = $exception->statusCode();
    $body = $exception->responseBody();
}
```

Every request uses Bearer authentication and the configured timeout, SSL verification, and retry count. Request and response metadata are logged at `debug` level; keys, plaintexts, signatures, API keys, and response bodies are never written to logs.

## REST payload assumptions

The client targets the requested `/v1/crypto` API and uses the field names `key_id`, `plaintext`, `ciphertext`, `payload`, and `signature`. Plaintext and signing payloads are base64 encoded before transmission. Decryption accepts base64 plaintext from either the `plaintext` field or the `data` field, while verification accepts a boolean from either `valid` or `verified`.

Key generation options are sent alongside `algorithm`; encryption, decryption, signing, and verification options are sent alongside their required fields. Required arguments take precedence over duplicate keys in the options array.

## Testing

```bash
composer install
composer test
```