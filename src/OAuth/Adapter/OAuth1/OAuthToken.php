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
 * Класс OAuthToken
 *
 * Этот класс представляет собой токен OAuth, который содержит ключ и секрет.
 *
 * @author Ivan Zorin
 * @author Andy Smith
 *
 * @link https://code.google.com/archive/p/oauth
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class OAuthToken {
    /**
     * @var string $key Ключ токена OAuth.
     */
    public string $key;

    /**
     * @var string $secret Секрет токена OAuth.
     */
    public string $secret;

    /**
     * Конструктор OAuthToken.
     *
     * @param string $key Ключ токена OAuth.
     * @param string $secret Секрет токена OAuth.
     */
    function __construct(string $key, string $secret) {
        $this->key = $key;
        $this->secret = $secret;
    }

    /**
     * Генерирует базовую строковую сериализацию токена, которую сервер
     * будет отвечать на вызовы request_token и access_token.
     *
     * @return string Строковое представление токена OAuth.
     */
    function to_string(): string
    {
        return "oauth_token=" .
            OAuthUtil::urlencode_rfc3986($this->key) .
            "&oauth_token_secret=" .
            OAuthUtil::urlencode_rfc3986($this->secret);
    }

    /**
     * Преобразование объекта в строку.
     *
     * @return string Строковое представление объекта OAuthToken.
     */
    function __toString() {
        return $this->to_string();
    }
}
