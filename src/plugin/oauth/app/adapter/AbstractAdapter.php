<?php

/**
 * @package     FrameX (FX) OAuth Plugin
 * @link        https://localzet.gitbook.io
 * 
 * @author      localzet <creator@localzet.ru>
 * 
 * @copyright   Copyright (c) 2018-2020 Zorin Projects 
 * @copyright   Copyright (c) 2020-2022 NONA Team
 * 
 * @license     https://www.localzet.ru/license GNU GPLv3 License
 */

namespace plugin\oauth\app\adapter;

use support\Log;

use plugin\oauth\app\exception\NotImplementedException;
use plugin\oauth\app\exception\InvalidArgumentException;
use plugin\oauth\app\exception\HttpClientFailureException;
use plugin\oauth\app\exception\HttpRequestFailedException;

use FrameX\HttpClient\HttpClientInterface;
use FrameX\HttpClient\Curl;
use support\Collection;
use support\Storage;

/**
 * Абстрактный адаптер
 */
abstract class AbstractAdapter implements AdapterInterface
{
    use DataStoreTrait;

    /**
     * ID Провайдера
     *
     * @var string
     */
    protected $providerId = '';

    /**
     * Конфигурация провайдера
     *
     * @var mixed
     */
    protected $config = [];

    /**
     * Параметры провайдера
     *
     * @var array
     */
    protected $params;

    /**
     * Callback URL
     *
     * @var string
     */
    protected $callback = '';

    /**
     * Хранилище
     *
     * @var Storage
     */
    public $storage;

    /**
     * HttpClient
     *
     * @var HttpClientInterface
     */
    public $httpClient;

    /**
     * Логер
     *
     * @var Log
     */
    public $logger;

    /**
     * Проверять ли коды HTTP ответов
     *
     * @var bool
     */
    protected $validateApiResponseHttpCode = true;

    /**
     * Конструктор всех адаптеров
     *
     * @param array $config
     */
    public function __construct($config)
    {
        $this->config = new Collection(['callback_uri' => 'https:' . request()->url()] + $config + config('plugin.oauth.app'));

        $this->providerId = (new \ReflectionClass($this))->getShortName();

        $this->httpClient = new Curl();

        if ($this->config->exists('curl_options') && method_exists($this->httpClient, 'setCurlOptions')) {
            $this->httpClient->setCurlOptions($this->config->get('curl_options'));
        }

        $this->storage = new Storage();

        $this->logger = Log::channel('plugin.oauth.default');

        $this->configure();

        $this->logger->debug(sprintf('Инициализация %s: ', get_class($this)));

        $this->initialize();
    }

    /**
     * Load adapter's configuration
     */
    abstract protected function configure();

    /**
     * Adapter initializer
     */
    abstract protected function initialize();

    /**
     * {@inheritdoc}
     */
    abstract public function isConnected();

    /**
     * {@inheritdoc}
     */
    public function apiRequest($url, $method = 'GET', $parameters = [], $headers = [], $multipart = false)
    {
        throw new NotImplementedException('Провайдер не поддерживает эту функцию');
    }

    /**
     * {@inheritdoc}
     */
    public function maintainToken()
    {
        // Для Facebook и Instagram
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        throw new NotImplementedException('Провайдер не поддерживает эту функцию');
    }

    /**
     * {@inheritdoc}
     */
    public function getUserContacts()
    {
        throw new NotImplementedException('Провайдер не поддерживает эту функцию');
    }

    /**
     * {@inheritdoc}
     */
    public function getUserPages()
    {
        throw new NotImplementedException('Провайдер не поддерживает эту функцию');
    }

    /**
     * {@inheritdoc}
     */
    public function getUserActivity($stream)
    {
        throw new NotImplementedException('Провайдер не поддерживает эту функцию');
    }

    /**
     * {@inheritdoc}
     */
    public function setUserStatus($status)
    {
        throw new NotImplementedException('Провайдер не поддерживает эту функцию');
    }

    /**
     * {@inheritdoc}
     */
    public function setPageStatus($status, $pageId)
    {
        throw new NotImplementedException('Провайдер не поддерживает эту функцию');
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        $this->clearStoredData();
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessToken()
    {
        $tokenNames = [
            'access_token',
            'access_token_secret',
            'token_type',
            'refresh_token',
            'expires_in',
            'expires_at',
        ];

        $tokens = [];

        foreach ($tokenNames as $name) {
            if ($this->getStoredData($name)) {
                $tokens[$name] = $this->getStoredData($name);
            }
        }

        return $tokens;
    }

    /**
     * {@inheritdoc}
     */
    public function setAccessToken($tokens = [])
    {
        $this->clearStoredData();

        foreach ($tokens as $token => $value) {
            $this->storeData($token, $value);
        }

        // Реинициализируем параметры токена
        $this->initialize();
    }

    /**
     * Установка callback URL
     *
     * @param string $callback
     *
     * @throws InvalidArgumentException
     */
    protected function setCallback($callback)
    {
        if (!filter_var($callback, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Требуется действительный URL-адрес обратного вызова');
        }

        $this->callback = $callback;
    }

    /**
     * Установка конечных точек API
     *
     * @param array $endpoints
     */
    protected function setApiEndpoints($endpoints = null)
    {
        if (empty($endpoints)) {
            return;
        }

        $this->apiBaseUrl = $endpoints['api_base_url'] ?: $this->apiBaseUrl;
        $this->authorizeUrl = $endpoints['authorize_url'] ?: $this->authorizeUrl;
        $this->accessTokenUrl = $endpoints['access_token_url'] ?: $this->accessTokenUrl;
    }


    /**
     * Validate signed API responses Http status code.
     *
     * Since the specifics of error responses is beyond the scope of RFC6749 and OAuth Core specifications,
     * OAuth will consider any HTTP status code that is different than '200 OK' as an ERROR.
     *
     * @param string $error String to pre append to message thrown in exception
     *
     * @throws HttpClientFailureException
     * @throws HttpRequestFailedException
     */
    protected function validateApiResponse($error = '')
    {
        $error .= !empty($error) ? '. ' : '';

        if ($this->httpClient->getResponseClientError()) {
            throw new HttpClientFailureException(
                $error . 'Ошибка HTTP: ' . $this->httpClient->getResponseClientError() . '.'
            );
        }

        // if validateApiResponseHttpCode is set to false, we by pass verification of http status code
        if (!$this->validateApiResponseHttpCode) {
            return;
        }

        $status = $this->httpClient->getResponseHttpCode();

        if ($status < 200 || $status > 299) {
            throw new HttpRequestFailedException(
                $error . 'Ошибка HTTP ' . $this->httpClient->getResponseHttpCode() .
                    '. Ответ провайдера: ' . $this->httpClient->getResponseBody() . '.'
            );
        }
    }
}
