<?php

namespace Cosmian\LaravelKmsSdk\Tests;

use Cosmian\LaravelKmsSdk\Contracts\CosmianKmsInterface;
use Cosmian\LaravelKmsSdk\CosmianKmsClient;
use Cosmian\LaravelKmsSdk\Exceptions\CosmianKmsException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class CosmianKmsClientTest extends TestCase
{
    public function test_it_registers_the_client_as_a_singleton(): void
    {
        $client = $this->app->make(CosmianKmsClient::class);

        $this->assertSame($client, $this->app->make(CosmianKmsInterface::class));
        $this->assertSame($client, $this->app->make('cosmian-kms'));
    }

    public function test_it_generates_a_key_with_authentication_and_options(): void
    {
        Http::fake([
            '*' => Http::response(['kid' => 'key-123'], 201),
        ]);

        $result = $this->client()->generateKey('RSA', ['size' => 2048]);

        $this->assertSame(['kid' => 'key-123'], $result);
        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://kms.example.test/v1/crypto/keys'
                && $request->hasHeader('Authorization', 'Bearer secret-token')
                && $request['algorithm'] === 'RSA'
                && $request['size'] === 2048;
        });
    }

    public function test_it_imports_a_jwk_with_an_optional_kid(): void
    {
        Http::fake(['*' => Http::response(['kid' => 'imported-key'])]);
        $jwk = ['kty' => 'oct', 'k' => 'c2VjcmV0'];

        $this->client()->importKey($jwk, 'imported-key');

        Http::assertSent(fn (Request $request): bool => $request['jwk'] === $jwk
            && $request['kid'] === 'imported-key');
    }

    public function test_it_encodes_plaintext_and_signing_payload_as_base64(): void
    {
        Http::fakeSequence()
            ->push(['ciphertext' => 'encrypted'])
            ->push(['signature' => 'signed']);

        $this->client()->encrypt('key-1', 'plain text', ['algorithm' => 'A256GCM']);
        $this->client()->sign('key-1', 'payload');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://kms.example.test/v1/crypto/encrypt'
            && $request['plaintext'] === base64_encode('plain text')
            && $request['algorithm'] === 'A256GCM');
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://kms.example.test/v1/crypto/sign'
            && $request['payload'] === base64_encode('payload'));
    }

    public function test_it_decodes_plaintext_and_returns_verification_result(): void
    {
        Http::fakeSequence()
            ->push(['plaintext' => base64_encode('decrypted')])
            ->push(['valid' => true]);

        $plaintext = $this->client()->decrypt('key-1', 'ciphertext');
        $valid = $this->client()->verify('key-1', 'signature', 'payload');

        $this->assertSame('decrypted', $plaintext);
        $this->assertTrue($valid);
    }

    public function test_it_lists_and_deletes_keys(): void
    {
        Http::fakeSequence()
            ->push(['data' => [['kid' => 'key-1']]])
            ->push('', 204);

        $this->assertSame(['data' => [['kid' => 'key-1']]], $this->client()->listKeys());
        $this->assertTrue($this->client()->deleteKey('key/with spaces'));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === 'https://kms.example.test/v1/crypto/keys/key%2Fwith%20spaces');
    }

    public function test_it_retries_and_throws_an_exception_with_the_api_message(): void
    {
        Http::fakeSequence()
            ->push(['message' => 'temporary failure'], 500)
            ->push(['message' => 'key not found'], 404);

        try {
            $this->client()->listKeys();
            $this->fail('Expected CosmianKmsException was not thrown.');
        } catch (CosmianKmsException $exception) {
            $this->assertSame('key not found', $exception->getMessage());
            $this->assertSame(404, $exception->statusCode());
        }

        Http::assertSentCount(2);
    }

    private function client(): CosmianKmsClient
    {
        return $this->app->make(CosmianKmsClient::class);
    }
}