<?php declare(strict_types=1);

/**
 * Copyright © 2016-2026 Localzet Group. All rights reserved.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use Monolog\Handler\NullHandler;
use Monolog\Logger;
use localzet\HTTP\Client as HttpClient;
use localzet\HTTP\Client\Response as HttpResponse;
use Psr\Http\Message\ResponseInterface;
use Triangle\Engine\Config;
use Triangle\Engine\Request;
use Triangle\Engine\Response;
use Triangle\Exception\HttpRequestFailedException;
use Triangle\OAuth;
use Triangle\OAuth\Storage\StorageInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

final class OAuthTestHandler
{
    public static function request(): Request
    {
        return new Request("GET /oauth HTTP/1.1\r\nHost: app.example.test\r\n\r\n");
    }
}

final class OAuthTestStorage implements StorageInterface
{
    private array $values = [];

    public function get($key): mixed
    {
        return $this->values[$key] ?? null;
    }

    public function set($key, $value)
    {
        $this->values[$key] = $value;
    }

    public function delete($key)
    {
        unset($this->values[$key]);
    }

    public function deleteMatch($key)
    {
        foreach (array_keys($this->values) as $name) {
            if (str_starts_with($name, $key)) {
                unset($this->values[$name]);
            }
        }
    }

    public function clear()
    {
        $this->values = [];
    }
}

final class OAuthTestHttpClient extends HttpClient
{
    public function __construct(private ResponseInterface $response)
    {
    }

    public function request(
        string $url,
        string $method = 'GET',
        array $data = [],
        array $headers = [],
        array $options = [],
    ) {
        return $this->response;
    }
}

Config::set([
    'server' => ['handler' => OAuthTestHandler::class],
    'app' => ['http_headers' => [], 'http_always_200' => false],
]);

$oauth = new OAuth(
    [
        'providers' => [
            'google' => [
                'enabled' => true,
                'callback' => 'https://app.example.test/oauth/callback',
                'keys' => ['id' => 'test-client', 'secret' => 'test-secret'],
                'supportRequestState' => false,
            ],
        ],
    ],
    new OAuthTestStorage(),
    new Logger('oauth-test', [new NullHandler()]),
);

$response = $oauth->authenticate('Google');

if (!$response instanceof Response) {
    throw new RuntimeException('OAuth::authenticate() must return an Engine response for a redirect.');
}

if ($response->getStatusCode() !== 302) {
    throw new RuntimeException('OAuth authorization must use an HTTP 302 response.');
}

$location = $response->getHeader('Location');
if (!is_string($location) || !str_starts_with($location, 'https://accounts.google.com/o/oauth2/v2/auth?')) {
    throw new RuntimeException('OAuth authorization must redirect to the configured provider endpoint.');
}

echo "OAuth authorization redirect integration passed.\n";

$adapter = $oauth->getAdapter('Google');
$adapter->setHttpClient(new OAuthTestHttpClient(new HttpResponse(503, [], 'upstream unavailable')));

try {
    $adapter->getUserProfile();
    throw new RuntimeException('OAuth must reject a non-success HTTP API response.');
} catch (HttpRequestFailedException $exception) {
    if (!str_contains($exception->getMessage(), '503')) {
        throw $exception;
    }
}

echo "OAuth API response validation passed.\n";
