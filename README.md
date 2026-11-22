<p align="center"><a href="https://www.localzet.com" target="_blank">
  <img src="https://static.zorin.space/media/logos/LocalzetGroup.png" width="400">
</a></p>

<p align="center">
  <a href="https://packagist.org/packages/triangle/oauth">
  <img src="https://img.shields.io/packagist/dt/triangle/oauth?label=%D0%A1%D0%BA%D0%B0%D1%87%D0%B8%D0%B2%D0%B0%D0%BD%D0%B8%D1%8F" alt="Скачивания">
</a>
  <a href="https://github.com/Triangle-org/OAuth">
  <img src="https://img.shields.io/github/commit-activity/t/Triangle-org/OAuth?label=%D0%9A%D0%BE%D0%BC%D0%BC%D0%B8%D1%82%D1%8B" alt="Коммиты">
</a>
  <a href="https://packagist.org/packages/triangle/oauth">
  <img src="https://img.shields.io/packagist/v/triangle/oauth?label=%D0%92%D0%B5%D1%80%D1%81%D0%B8%D1%8F" alt="Версия">
</a>
  <a href="https://packagist.org/packages/triangle/oauth">
  <img src="https://img.shields.io/packagist/dependency-v/triangle/oauth/php?label=PHP" alt="Версия PHP">
</a>
  <a href="https://github.com/Triangle-org/OAuth">
  <img src="https://img.shields.io/github/license/Triangle-org/OAuth?label=%D0%9B%D0%B8%D1%86%D0%B5%D0%BD%D0%B7%D0%B8%D1%8F" alt="Лицензия">
</a>
</p>

## Совместимость

- PHP 8.2 или новее.
- Triangle Engine `^9.0` и Localzet HTTP Client `^3.0`; эта ветка рассчитана на Localzet Server 7.

## Установка

```sh
composer require triangle/oauth
```

Добавьте провайдера в конфигурацию Triangle Engine:

```php
$oauth = new \Triangle\OAuth([
    'providers' => [
        'google' => [
            'enabled' => true,
            'callback' => 'https://app.example.com/oauth/google/callback',
            'keys' => [
                'id' => getenv('GOOGLE_CLIENT_ID'),
                'secret' => getenv('GOOGLE_CLIENT_SECRET'),
            ],
        ],
    ],
]);

$result = $oauth->authenticate('google');
if ($result instanceof \Triangle\Engine\Response) {
    return $result; // Redirect the browser to the provider.
}

$profile = $result->getUserProfile();
```

`authenticate()` returns an Engine response while starting authorization and the provider adapter after authentication. Keep client secrets in environment configuration. Provider-specific options and examples are in the [Triangle OAuth documentation](https://docs.localzet.com/triangle/oauth).

For HTTP client settings, use the provider option `http_client_options` supported by Localzet HTTP Client 3, for example `['timeout' => 10]` or a TLS `context`. The old `curl_options` setting is unsupported by HTTP Client 3.

## Compatibility

- PHP 8.2 or newer.
- Triangle Engine `^9.0` and Localzet HTTP Client `^3.0`; this branch targets Localzet Server 7.

## Installation

```sh
composer require triangle/oauth
```

Add a provider to your Triangle Engine configuration:

```php
$oauth = new \Triangle\OAuth([
    'providers' => [
        'google' => [
            'enabled' => true,
            'callback' => 'https://app.example.com/oauth/google/callback',
            'keys' => [
                'id' => getenv('GOOGLE_CLIENT_ID'),
                'secret' => getenv('GOOGLE_CLIENT_SECRET'),
            ],
        ],
    ],
]);

$result = $oauth->authenticate('google');
if ($result instanceof \Triangle\Engine\Response) {
    return $result; // Redirect the browser to the provider.
}

$profile = $result->getUserProfile();
```

`authenticate()` returns an Engine response to start authorization and the provider adapter after authentication. Keep client secrets in environment configuration. See the [Triangle OAuth documentation](https://docs.localzet.com/triangle/oauth) for provider-specific options and examples.

Use the provider option `http_client_options` for Localzet HTTP Client 3, such as `['timeout' => 10]` or TLS `context` settings. The old `curl_options` option is not supported by HTTP Client 3.
