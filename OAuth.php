<?php

namespace Triangle;

use FilesystemIterator;
use InvalidArgumentException;
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
        $adapter = $config['adapter'] ?? sprintf('plugin\\oauth\\app\\provider\\%s', $name);

        if (!class_exists($adapter)) {
            $adapter = null;
            $fs = new FilesystemIterator(__DIR__ . '/Provider/');
            /** @var SplFileInfo $file */
            foreach ($fs as $file) {
                if (!$file->isDir()) {
                    $provider = strtok($file->getFilename(), '.');
                    if (mb_strtolower($name) === mb_strtolower($provider)) {
                        $adapter = sprintf('plugin\\oauth\\app\\provider\\%s', $provider);
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

        $config['callback'] = request()->host(true) . '/callback';

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
        return self::getAdapter($name)->isConnected();
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
            if ($config['enabled'] === true) {
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

        foreach (self::getProviders() as $name) {
            if (self::isConnectedWith($name)) {
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

        foreach (self::getProviders() as $name) {
            $adapter = self::getAdapter($name);

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
        foreach (self::getProviders() as $name) {
            $adapter = self::getAdapter($name);

            if ($adapter->isConnected()) {
                $adapter->disconnect();
            }
        }
    }
}