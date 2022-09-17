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

/**
 * Trait DataStoreTrait
 */
trait DataStoreTrait
{
    /**
     * Store a piece of data in storage.
     *
     * This method is mainly used for OAuth tokens (access, secret, refresh, and whatnot), but it
     * can be also used by providers to store any other useful data (i.g., user_id, auth_nonce, etc.)
     *
     * @param string $name
     * @param mixed $value
     */
    protected function storeData($name, $value = null)
    {
        // if empty, we simply delete the thing as we'd want to only store necessary data
        if (empty($value)) {
            $this->deleteStoredData($name);
        }

        $this->storage->set($this->providerId . '.' . $name, $value);
    }

    /**
     * Retrieve a piece of data from storage.
     *
     * This method is mainly used for OAuth tokens (access, secret, refresh, and whatnot), but it
     * can be also used by providers to retrieve from store any other useful data (i.g., user_id,
     * auth_nonce, etc.)
     *
     * @param string $name
     *
     * @return mixed
     */
    protected function getStoredData($name)
    {
        return $this->storage->get($this->providerId . '.' . $name);
    }

    /**
     * Delete a stored piece of data.
     *
     * @param string $name
     */
    protected function deleteStoredData($name)
    {
        $this->storage->delete($this->providerId . '.' . $name);
    }

    /**
     * Delete all stored data of the instantiated adapter
     */
    protected function clearStoredData()
    {
        $this->storage->deleteMatch($this->providerId . '.');
    }
}
