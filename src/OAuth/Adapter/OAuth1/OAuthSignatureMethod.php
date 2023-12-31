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
 * Абстрактный класс для реализации метода подписи.
 * См. раздел 9 ("Подписание запросов") в спецификации.
 *
 * @author Ivan Zorin
 * @author Andy Smith
 *
 * @link https://code.google.com/archive/p/oauth
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
abstract class OAuthSignatureMethod
{
    /**
     * Должен возвращать имя метода подписи (например, HMAC-SHA1).
     *
     * @return string Имя метода подписи.
     */
    abstract public function get_name(): string;

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
    abstract public function build_signature($request, $consumer, $token): string;

    /**
     * Проверяет, что данная подпись верна.
     *
     * @param OAuthRequest $request Запрос OAuth.
     * @param OAuthConsumer $consumer Потребитель OAuth.
     * @param OAuthToken $token Токен OAuth.
     * @param string $signature Подпись.
     * @return bool Верна ли подпись.
     */
    public function check_signature($request, $consumer, $token, $signature): bool
    {
        $built = $this->build_signature($request, $consumer, $token);

        // Проверка на нулевую длину, хотя здесь это маловероятно
        if (strlen($built) == 0 || strlen($signature) == 0) {
            return false;
        }

        if (strlen($built) != strlen($signature)) {
            return false;
        }

        // Избегаем утечки времени с (надеемся) нечувствительным к времени сравнением
        $result = 0;
        for ($i = 0; $i < strlen($signature); $i++) {
            $result |= ord($built[$i]) ^ ord($signature[$i]);
        }

        return $result == 0;
    }
}
