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
 * Класс OAuthSignatureMethod_HMAC_SHA1
 *
 * Этот класс представляет собой метод подписи HMAC-SHA1 для OAuth.
 *
 * @author Ivan Zorin
 * @author Andy Smith
 *
 * @link https://code.google.com/archive/p/oauth
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class OAuthSignatureMethod_HMAC_SHA1 extends OAuthSignatureMethod
{
    /**
     * Должен возвращать имя метода подписи (то есть HMAC-SHA1).
     *
     * @return string Имя метода подписи.
     */
    public function get_name(): string
    {
        return "HMAC-SHA1";
    }

    /**
     * Создает подпись.
     * ПРИМЕЧАНИЕ: вывод этой функции НЕ ДОЛЖЕН быть закодирован в URL.
     * Кодирование обрабатывается в OAuthRequest, когда окончательный
     * запрос сериализуется.
     *
     * @param OAuthRequest $request Запрос OAuth.
     * @param OAuthConsumer $consumer Потребитель OAuth.
     * @param OAuthToken $token Токен OAuth.
     * @return string Подпись.
     */
    public function build_signature($request, $consumer, $token): string
    {
        $base_string = $request->get_signature_base_string();
        $request->base_string = $base_string;

        $key_parts = array($consumer->secret, $token ? $token->secret : '');

        $key_parts = OAuthUtil::urlencode_rfc3986($key_parts);
        $key = implode('&', $key_parts);

        return base64_encode(hash_hmac('sha1', $base_string, $key, true));
    }
}
