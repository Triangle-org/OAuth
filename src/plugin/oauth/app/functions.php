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

/**
 * Экземпляр адаптера провайдера
 *
 * @param string $name Название адаптера
 *
 * @return AdapterInterface
 * @throws InvalidArgumentException
 * @throws UnexpectedValueException
 */
function getAdapter($name)
{
    $config = getProviderConfig($name);
    $adapter = isset($config['adapter']) ? $config['adapter'] : sprintf('plugin\\oauth\\app\\provider\\%s', $name);

    if (!class_exists($adapter)) {
        $adapter = null;
        $fs = new \FilesystemIterator(__DIR__ . '/Provider/');
        /** @var \SplFileInfo $file */
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
function getProviderConfig($name)
{
    $name = strtolower($name);

    $providersConfig = array_change_key_case(config('plugin.oauth.app.providers'), CASE_LOWER);

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
function isConnectedWith($name)
{
    return getAdapter($name)->isConnected();
}

/**
 * Список названий активных провайдеров
 *
 * @return array
 */
function getProviders()
{
    $providers = [];

    foreach (config('plugin.oauth.app.providers') as $name => $config) {
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
function getConnectedProviders()
{
    $providers = [];

    foreach (getProviders() as $name) {
        if (isConnectedWith($name)) {
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
function getConnectedAdapters()
{
    $adapters = [];

    foreach (getProviders() as $name) {
        $adapter = getAdapter($name);

        if ($adapter->isConnected()) {
            $adapters[$name] = $adapter;
        }
    }

    return $adapters;
}

/**
 * Disconnect all currently connected adapters at once
 */
function disconnectAllAdapters()
{
    foreach (getProviders() as $name) {
        $adapter = getAdapter($name);

        if ($adapter->isConnected()) {
            $adapter->disconnect();
        }
    }
}
