<?php declare(strict_types=1);

/**
 * @package     Localzet OAuth
 * @link        https://github.com/localzet/OAuth
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2018-2023 Localzet Group
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
    protected $storeNamespace = 'OAuth::STORAGE';

    /**
     * Key prefix
     *
     * @var string
     */
    protected $keyPrefix = '';

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $key = $this->keyPrefix . strtolower($key);

        if (session($this->storeNamespace) && session("$this->storeNamespace.$key")) {
            $value = session("$this->storeNamespace.$key");

            if (is_array($value) && array_key_exists('lateObject', $value)) {
                $value = unserialize($value['lateObject']);
            }

            return $value;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $key = $this->keyPrefix . strtolower($key);

        if (is_object($value)) {
            // We encapsulate as our classes may be defined after session is initialized.
            $value = ['lateObject' => serialize($value)];
        }

        $tmp = session($this->storeNamespace);
        $tmp[$key] = $value;
        session()->set($this->storeNamespace, $tmp);
        session()->save();
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        session()->set($this->storeNamespace, []);
        session()->save();
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $key = $this->keyPrefix . strtolower($key);

        if (session($this->storeNamespace) && session("$this->storeNamespace.$key")) {
            $tmp = session($this->storeNamespace);

            unset($tmp[$key]);

            session()->set($this->storeNamespace, $tmp);
            session()->save();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMatch($key)
    {
        $key = $this->keyPrefix . strtolower($key);

        if (session($this->storeNamespace) && count(session($this->storeNamespace))) {
            $tmp = session($this->storeNamespace);

            foreach ($tmp as $k => $v) {
                if (strstr($k, $key)) {
                    unset($tmp[$k]);
                }
            }

            session()->set($this->storeNamespace, $tmp);
            session()->save();
        }
    }
}
