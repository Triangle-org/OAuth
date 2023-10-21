<?php

/**
 * @package     Triangle OAuth Plugin
 * @link        https://github.com/Triangle-org/OAuth
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2018-2023 Localzet Group
 * @license     GNU Affero General Public License, version 3
 *
 *              This program is free software: you can redistribute it and/or modify
 *              it under the terms of the GNU Affero General Public License as
 *              published by the Free Software Foundation, either version 3 of the
 *              License, or (at your option) any later version.
 *
 *              This program is distributed in the hope that it will be useful,
 *              but WITHOUT ANY WARRANTY; without even the implied warranty of
 *              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *              GNU Affero General Public License for more details.
 *
 *              You should have received a copy of the GNU Affero General Public License
 *              along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Triangle;

use FilesystemIterator;
use InvalidArgumentException;
use Monolog\Logger;
use SplFileInfo;
use Triangle\OAuth\Adapter\AdapterInterface;
use UnexpectedValueException;

class OAuth
{
    protected $config;

    public function __construct($config)
    {
        if (is_string($config) && file_exists($config)) {
            $config = include $config;
        } elseif (!is_array($config)) {
            throw new InvalidArgumentException('OAuth Config не существует на данном пути');
        }

        $this->config = $config + [
                'debug_mode' => Logger::DEBUG,
                'debug_file' => '',
                'curl_options' => null,
                'providers' => []
            ];
    }

    public function authenticate($name)
    {
        $adapter = $this->getAdapter($name);

        $adapter->authenticate();

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
    public function getAdapter($name)
    {
        $config = self::getProviderConfig($name);
        $adapter = $config['adapter'] ?? sprintf('Triangle\\OAuth\\Provider\\%s', $name);

        if (!class_exists($adapter)) {
            $adapter = null;
            $fs = new FilesystemIterator(__DIR__ . '/src/Provider/');
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

        return new $adapter($config);
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

        $providersConfig = array_change_key_case($this->config['providers'], CASE_LOWER);

        if (!isset($providersConfig[$name])) {
            throw new InvalidArgumentException('Неизвестный провайдер');
        }

        if (!$providersConfig[$name]['enabled']) {
            throw new UnexpectedValueException('Провайдер отключён');
        }

        $config = $providersConfig[$name];
        $config += [
            'debug_mode' => $this->config['debug_mode'],
            'debug_file' => $this->config['debug_file'],
        ];

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
     * Список названий активных провайдеров
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