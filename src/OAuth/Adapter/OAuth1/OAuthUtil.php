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

namespace Triangle\OAuth\Adapter\OAuth1;

/**
 * Класс OAuthUtil
 *
 * Этот класс содержит утилиты для работы с OAuth.
 *
 * @author Ivan Zorin
 * @author Andy Smith
 *
 * @link https://code.google.com/archive/p/oauth
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class OAuthUtil
{
    /**
     * Кодирует входные данные в соответствии с RFC3986.
     *
     * @param mixed $input Входные данные для кодирования.
     * @return array|string Кодированные входные данные.
     */
    public static function urlencode_rfc3986(mixed $input): array|string
    {
        if (is_array($input)) {
            return array_map(array(
                '\Triangle\OAuth\Adapter\OAuth1\OAuthUtil',
                'urlencode_rfc3986'
            ), $input);
        } elseif (is_scalar($input)) {
            return str_replace('+', ' ', str_replace('%7E', '~', rawurlencode($input)));
        } else {
            return '';
        }
    }

    /**
     * Декодирует строку в соответствии с RFC3986.
     *
     * @param string $string Строка для декодирования.
     * @return string Декодированная строка.
     */
    public static function urldecode_rfc3986(string $string): string
    {
        return urldecode($string);
    }

    /**
     * Разбирает параметры из строки запроса.
     *
     * @param string $input Строка запроса.
     * @return array Массив параметров.
     */
    public static function parse_parameters(string $input): array
    {
        if (!isset($input) || !$input) {
            return array();
        }

        $pairs = explode('&', $input);

        $parsed_parameters = array();
        foreach ($pairs as $pair) {
            $split = explode('=', $pair, 2);
            $parameter = OAuthUtil::urldecode_rfc3986($split[0]);
            $value = isset($split[1]) ? OAuthUtil::urldecode_rfc3986($split[1]) : '';

            if (isset($parsed_parameters[$parameter])) {
                if (is_scalar($parsed_parameters[$parameter])) {
                    $parsed_parameters[$parameter] = array(
                        $parsed_parameters[$parameter]
                    );
                }

                $parsed_parameters[$parameter][] = $value;
            } else {
                $parsed_parameters[$parameter] = $value;
            }
        }
        return $parsed_parameters;
    }

    /**
     * Строит HTTP-запрос из массива параметров.
     *
     * @param array $params Массив параметров.
     * @return string Строка HTTP-запроса.
     */
    public static function build_http_query(array $params): string
    {
        if (!$params) {
            return '';
        }

        $keys = OAuthUtil::urlencode_rfc3986(array_keys($params));
        $values = OAuthUtil::urlencode_rfc3986(array_values($params));
        $params = array_combine($keys, $values);

        uksort($params, 'strcmp');

        $pairs = array();
        foreach ($params as $parameter => $value) {
            if (is_array($value)) {
                sort($value, SORT_STRING);
                foreach ($value as $duplicate_value) {
                    $pairs[] = $parameter . '=' . $duplicate_value;
                }
            } else {
                $pairs[] = $parameter . '=' . $value;
            }
        }
        return implode('&', $pairs);
    }
}
