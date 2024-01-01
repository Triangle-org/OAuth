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

namespace Triangle\OAuth\Storage;

use Exception;

/**
 * Triangle\OAuth storage manager
 */
class Session implements StorageInterface
{
    /**
     * Namespace
     *
     * @var string
     */
    protected string $namespace = 'OAuthStorage';

    /**
     * Key prefix
     *
     * @var string
     */
    protected string $prefix = '';

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function get($key): mixed
    {
        $key = $this->prefix . strtolower($key);

        if (session()->get($this->namespace) && isset(session()->get($this->namespace)[$key])) {
            $value = session()->get($this->namespace)[$key];

            if (is_array($value) && array_key_exists('lateObject', $value)) {
                $value = unserialize($value['lateObject']);
            }

            return $value;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function set($key, $value): void
    {
        $key = $this->prefix . strtolower($key);

        if (is_object($value)) {
            // We encapsulate as our classes may be defined after session is initialized.
            $value = ['lateObject' => serialize($value)];
        }

        $storage = session()->get($this->namespace);
        $storage[$key] = $value;
        session()->set($this->namespace, $storage);
        session()->save();
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function clear(): void
    {
        session()->set($this->namespace, []);
        session()->save();
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function delete($key): void
    {
        $key = $this->prefix . strtolower($key);

        if (session()->get($this->namespace) && isset(session()->get($this->namespace)[$key])) {
            $storage = session()->get($this->namespace);

            unset($storage[$key]);

            session()->set($this->namespace, $storage);
            session()->save();
        }
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function deleteMatch($key): void
    {
        $key = $this->prefix . strtolower($key);

        if (session()->get($this->namespace) && count(session()->get($this->namespace))) {
            $storage = session()->get($this->namespace);

            foreach ($storage as $k => $v) {
                if (strstr($k, $key)) {
                    unset($storage[$k]);
                }
            }

            session()->set($this->namespace, $storage);
            session()->save();
        }
    }
}
