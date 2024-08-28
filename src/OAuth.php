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

namespace Triangle;

use FilesystemIterator;
use InvalidArgumentException;
use localzet\HTTP\Client as HttpClient;
use Monolog\Logger;
use SplFileInfo;
use support\Log;
use Triangle\Http\Response;
use Triangle\OAuth\Adapter\AdapterInterface;
use Triangle\OAuth\Storage\StorageInterface;
use UnexpectedValueException;

/**
 * Triangle\OAuth
 *
 */
class OAuth
{
    /**
     * OAuth config.
     *
     * @var array
     */
    protected $config;

    /**
     * Storage.
     *
     * @var StorageInterface|null
     */
    protected ?StorageInterface $storage;

    /**
     * HttpClient.
     *
     * @var HttpClient|null
     */
    protected ?HttpClient $httpClient;

    /**
     * Logger.
     *
     * @var Logger|null
     */
    protected ?Logger $logger;

    /**
     * @param array|string $config Массив с конфигурацией или путем к файлу PHP, который вернет массив
     * <code>
     * [
     *  'callback' => '',
     *  'providers' => [
     *      'enabled' => true,
     *      'callback' => '',
     *      'adapter' => 'Triangle\\OAuth\\Provider\\%s',
     *      'keys' => [
     *          'key' => '',
     *          'secret' => '',
     *      ],
     *  ],
     * ]
     * </code>
     *
     * @param StorageInterface|null $storage
     * @param Logger|null $logger
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        array|string     $config,
        StorageInterface $storage = null,
        Logger           $logger = null
    )
    {
        if (is_string($config) && file_exists($config)) {
            $config = include $config;
        } elseif (!is_array($config)) {
            throw new InvalidArgumentException('OAuth Config не существует на данном пути');
        }

        $this->config = $config + ['providers' => []];
        $this->storage = $storage;
        $this->logger = $logger ?? Log::channel();
        $this->httpClient = new HttpClient();
    }

    /**
     * Instantiate the given provider and authentication or authorization protocol.
     *
     * If not authenticated yet, the user will be redirected to the provider's site for
     * authentication/authorization, otherwise it will simply return an instance of
     * provider's adapter.
     *
     * @param string $name adapter's name (case insensitive)
     *
     * @return AdapterInterface|Response
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function authenticate(string $name): AdapterInterface|Response
    {
        $adapter = $this->getAdapter($name);

        $authenticate = $adapter->authenticate();

        if ($authenticate && $authenticate instanceof Response) {
            return $authenticate;
        }

        return $adapter;
    }

    /**
     * Экземпляр адаптера провайдера
     *
     * @param string $name Название адаптера
     *
     * @return AdapterInterface
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function getAdapter(string $name): AdapterInterface
    {
        $config = $this->getProviderConfig($name);

        $adapter = $config['adapter'] ?? sprintf('Triangle\\OAuth\\Provider\\%s', $name);

        if (!class_exists($adapter)) {
            $adapter = null;
            $fs = new FilesystemIterator(__DIR__ . '/OAuth/Provider/');
            /** @var SplFileInfo $file */
            foreach ($fs as $file) {
                if (!$file->isDir()) {
                    $provider = strtok($file->getFilename(), '.');
                    if (mb_strtolower($name) === mb_strtolower($provider)) {
                        $adapter = sprintf('Triangle\\OAuth\\Provider\\%s', $provider);
                        break;
                    }
                }
            }
            if ($adapter === null) {
                throw new InvalidArgumentException('Неизвестный провайдер');
            }
        }

        return new $adapter($config, $this->httpClient, $this->storage, $this->logger);
    }

    /**
     * Get provider config by name.
     *
     * @param string $name adapter's name (case insensitive)
     *
     * @throws UnexpectedValueException
     * @throws InvalidArgumentException
     *
     * @return array
     */
    public function getProviderConfig($name)
    {
        $name = strtolower($name);

        $providersConfig = array_change_key_case($this->config['providers']);

        if (!isset($providersConfig[$name])) {
            throw new InvalidArgumentException('Неизвестный провайдер (' . $name . ')');
        }

        if (!$providersConfig[$name]['enabled']) {
            throw new UnexpectedValueException('Отключенный провайдер');
        }

        $config = $providersConfig[$name];

        if (!isset($config['callback']) && isset($this->config['callback'])) {
            $config['callback'] = $this->config['callback'];
        }

        return $config;
    }

    /**
     * Returns a boolean of whether the user is connected with a provider
     *
     * @param string $name adapter's name (case insensitive)
     *
     * @return bool
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function isConnectedWith($name)
    {
        return $this->getAdapter($name)->isConnected();
    }

    /**
     * Returns a list of enabled adapters names
     *
     * @return array
     */
    public function getProviders()
    {
        $providers = [];

        foreach ($this->config['providers'] as $name => $config) {
            if ($config['enabled']) {
                $providers[] = $name;
            }
        }

        return $providers;
    }

    /**
     * Returns a list of currently connected adapters names
     *
     * @return array
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function getConnectedProviders()
    {
        $providers = [];

        foreach ($this->getProviders() as $name) {
            if ($this->isConnectedWith($name)) {
                $providers[] = $name;
            }
        }

        return $providers;
    }

    /**
     * Returns a list of new instances of currently connected adapters
     *
     * @return AdapterInterface[]
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function getConnectedAdapters()
    {
        $adapters = [];

        foreach ($this->getProviders() as $name) {
            $adapter = $this->getAdapter($name);

            if ($adapter->isConnected()) {
                $adapters[$name] = $adapter;
            }
        }

        return $adapters;
    }

    /**
     * Disconnect all currently connected adapters at once
     */
    public function disconnectAllAdapters()
    {
        foreach ($this->getProviders() as $name) {
            $adapter = $this->getAdapter($name);

            if ($adapter->isConnected()) {
                $adapter->disconnect();
            }
        }
    }
}
