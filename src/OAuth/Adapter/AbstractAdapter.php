<?php declare(strict_types=1);

/**
 * @package     Localzet OAuth
 * @link        https://github.com/localzet/OAuth
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2018-2024 Localzet Group
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License v3.0
 *
 *              This program is free software: you can redistribute it and/or modify
 *              it under the terms of the GNU Affero General Public License as published
 *              by the Free Software Foundation, either version 3 of the License, or
 *              (at your option) any later version.
 *
 *              This program is distributed in the hope that it will be useful,
 *              but WITHOUT ANY WARRANTY; without even the implied warranty of
 *              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *              GNU Affero General Public License for more details.
 *
 *              You should have received a copy of the GNU Affero General Public License
 *              along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 *              For any questions, please contact <creator@localzet.com>
 */

namespace Triangle\OAuth\Adapter;

use AllowDynamicProperties;
use InvalidArgumentException;
use localzet\HTTP\Client as HttpClient;
use Monolog\Logger;
use ReflectionClass;
use Support\Collection;
use support\Log;
use Triangle\Exception\HttpClientFailureException;
use Triangle\Exception\HttpRequestFailedException;
use Triangle\Exception\NotImplementedException;
use Triangle\OAuth\Storage\Session;
use Triangle\OAuth\Storage\StorageInterface;

/**
 * Class AbstractAdapter
 */
#[AllowDynamicProperties]
abstract class AbstractAdapter implements AdapterInterface
{
    use DataStoreTrait;

    /**
     * Provider ID (unique name).
     *
     * @var string
     */
    protected string $providerId = '';

    /**
     * Specific Provider config.
     *
     * @var mixed
     */
    protected mixed $config = [];

    /**
     * Extra Provider parameters.
     *
     * @var array
     */
    protected array $params;

    /**
     * Callback url
     *
     * @var string
     */
    protected string $callback = '';

    /**
     * Storage.
     *
     * @var StorageInterface
     */
    public $storage;

    /**
     * HttpClient.
     *
     * @var HttpClient
     */
    public $httpClient;

    /**
     * Logger.
     *
     * @var Logger
     */
    public $logger;

    /**
     * Whether to validate API status codes of http responses
     *
     * @var bool
     */
    protected bool $validateApiResponseHttpCode = true;

    /**
     * Common adapters constructor.
     *
     * @param array $config
     * @param HttpClient|null $httpClient
     * @param StorageInterface|null $storage
     * @param Logger|null $logger
     */
    public function __construct(
        array            $config = [],
        HttpClient       $httpClient = null,
        StorageInterface $storage = null,
        Logger           $logger = null
    )
    {
        $this->providerId = (new ReflectionClass($this))->getShortName();

        $this->config = new Collection($config);

        $this->setHttpClient($httpClient);

        $this->setStorage($storage);

        $this->setLogger($logger);

        $this->configure();

        $this->logger->debug(sprintf('Initialize %s, config: ', get_class($this)), $config);

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
     * @throws NotImplementedException
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
        // Nothing needed for most providers
    }

    /**
     * {@inheritdoc}
     * @throws NotImplementedException
     */
    public function getUserProfile()
    {
        throw new NotImplementedException('Провайдер не поддерживает эту функцию');
    }

    /**
     * {@inheritdoc}
     * @throws NotImplementedException
     */
    public function getUserContacts()
    {
        throw new NotImplementedException('Провайдер не поддерживает эту функцию');
    }

    /**
     * {@inheritdoc}
     * @throws NotImplementedException
     */
    public function getUserPages()
    {
        throw new NotImplementedException('Провайдер не поддерживает эту функцию');
    }

    /**
     * {@inheritdoc}
     * @throws NotImplementedException
     */
    public function getUserActivity($stream)
    {
        throw new NotImplementedException('Провайдер не поддерживает эту функцию');
    }

    /**
     * {@inheritdoc}
     * @throws NotImplementedException
     */
    public function setUserStatus($status)
    {
        throw new NotImplementedException('Провайдер не поддерживает эту функцию');
    }

    /**
     * {@inheritdoc}
     * @throws NotImplementedException
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

        // Re-initialize token parameters.
        $this->initialize();
    }

    /**
     * {@inheritdoc}
     */
    public function setHttpClient(HttpClient $httpClient = null)
    {

        $this->httpClient = $httpClient ?: new HttpClient();

        if ($this->config->exists('curl_options') && method_exists($this->httpClient, 'setCurlOptions')) {
            $this->httpClient->setCurlOptions($this->config->get('curl_options'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * {@inheritdoc}
     */
    public function setStorage(StorageInterface $storage = null)
    {
        $this->storage = $storage ?: new Session();
    }

    /**
     * {@inheritdoc}
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(?Logger $logger = null)
    {
        $this->logger = $logger ?: Log::channel();

        if (method_exists($this->httpClient, 'setLogger')) {
            $this->httpClient->setLogger($this->logger);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Set Adapter's API callback url
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
     * Overwrite Adapter's API endpoints
     *
     * @param array|Collection $endpoints
     */
    protected function setApiEndpoints($endpoints = null)
    {
        if (empty($endpoints)) {
            return;
        }

        $collection = is_array($endpoints) ? new Collection($endpoints) : $endpoints;

        $this->apiBaseUrl = $collection->get('api_base_url') ?: $this->apiBaseUrl;
        $this->authorizeUrl = $collection->get('authorize_url') ?: $this->authorizeUrl;
        $this->accessTokenUrl = $collection->get('access_token_url') ?: $this->accessTokenUrl;
    }


    /**
     * Validate signed API responses Http status code.
     *
     * Since the specifics of error responses is beyond the scope of RFC6749 and OAuth Core specifications,
     * Triangle\OAuth will consider any HTTP status code that is different than '200 OK' as an ERROR.
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
                $error . 'HTTP Client Error.: ' . $this->httpClient->getResponseClientError() . '.'
            );
        }

        // if validateApiResponseHttpCode is set to false, we by pass verification of http status code
        if (!$this->validateApiResponseHttpCode) {
            return;
        }

        $status = $this->httpClient->getResponseHttpCode();

        if ($status < 200 || $status > 299) {
            throw new HttpRequestFailedException(
                $error . 'HTTP-ошибка ' . $this->httpClient->getResponseHttpCode() .
                '. Raw Provider API response: ' . $this->httpClient->getResponseBody() . '.'
            );
        }
    }
}
