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

namespace Triangle\OAuth\Adapter\OAuth1;

/**
 * Класс OAuthConsumer
 *
 * Этот класс представляет собой потребителя OAuth, который содержит ключ, секрет и URL обратного вызова.
 *
 * @author Ivan Zorin
 * @author Andy Smith
 *
 * @link https://code.google.com/archive/p/oauth
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class OAuthConsumer
{
    /**
     * @var string $key Ключ потребителя OAuth.
     */
    public $key;

    /**
     * @var string $secret Секрет потребителя OAuth.
     */
    public $secret;

    /**
     * @var string|null $callback_url URL обратного вызова OAuth.
     */
    public $callback_url;

    /**
     * Конструктор OAuthConsumer.
     *
     * @param string $key Ключ потребителя OAuth.
     * @param string $secret Секрет потребителя OAuth.
     * @param string|null $callback_url URL обратного вызова OAuth (необязательно).
     */
    public function __construct($key, $secret, $callback_url = null)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->callback_url = $callback_url;
    }

    /**
     * Преобразование объекта в строку.
     *
     * @return string Строковое представление объекта OAuthConsumer.
     */
    public function __toString()
    {
        return "OAuthConsumer[key=$this->key,secret=$this->secret]";
    }
}
