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
 * Класс OAuthRequest
 *
 * Этот класс представляет собой запрос OAuth.
 *
 * @author Ivan Zorin
 * @author Andy Smith
 *
 * @link https://code.google.com/archive/p/oauth Google Code
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class OAuthRequest
{
    /**
     * @var array $parameters Параметры запроса.
     */
    public $parameters;

    /**
     * @var string $http_method HTTP-метод запроса.
     */
    public $http_method;

    /**
     * @var string $http_url HTTP-URL запроса.
     */
    public $http_url;

    // для целей отладки
    /**
     * @var string $base_string Базовая строка запроса.
     */
    public $base_string;

    /**
     * @var string $version Версия OAuth.
     */
    public static $version = '1.0';

    /**
     * Конструктор OAuthRequest.
     *
     * @param string $http_method HTTP-метод запроса.
     * @param string $http_url HTTP-URL запроса.
     * @param array|null $parameters Параметры запроса.
     */
    public function __construct($http_method, $http_url, $parameters = null)
    {
        $parameters = ($parameters) ?: array();
        $parameters = array_merge(OAuthUtil::parse_parameters(parse_url($http_url, PHP_URL_QUERY)), $parameters);
        $this->parameters = $parameters;
        $this->http_method = $http_method;
        $this->http_url = $http_url;
    }

    /**
     * Вспомогательная функция для настройки запроса.
     *
     * @param OAuthConsumer $consumer Потребитель OAuth.
     * @param OAuthToken $token Токен OAuth.
     * @param string $http_method HTTP-метод запроса.
     * @param string $http_url HTTP-URL запроса.
     * @param array|null $parameters Параметры запроса.
     * @return OAuthRequest Запрос OAuth.
     */
    public static function from_consumer_and_token($consumer, $token, $http_method, $http_url, $parameters = null): OAuthRequest
    {
        $parameters = ($parameters) ?: array();
        $defaults = array(
            "oauth_version" => OAuthRequest::$version,
            "oauth_nonce" => OAuthRequest::generate_nonce(),
            "oauth_timestamp" => OAuthRequest::generate_timestamp(),
            "oauth_consumer_key" => $consumer->key
        );
        if ($token) {
            $defaults['oauth_token'] = $token->key;
        }

        $parameters = array_merge($defaults, $parameters);

        return new OAuthRequest($http_method, $http_url, $parameters);
    }

    /**
     * Устанавливает параметр запроса.
     *
     * @param string $name Имя параметра.
     * @param mixed $value Значение параметра.
     * @param bool $allow_duplicates Разрешить дубликаты.
     */
    public function set_parameter($name, $value, $allow_duplicates = true): void
    {
        if ($allow_duplicates && isset($this->parameters[$name])) {
            if (is_scalar($this->parameters[$name])) {
                $this->parameters[$name] = array(
                    $this->parameters[$name]
                );
            }

            $this->parameters[$name][] = $value;
        } else {
            $this->parameters[$name] = $value;
        }
    }

    /**
     * Возвращает параметры запроса, отсортированные и объединенные в нормализованную строку.
     *
     * @return string Нормализованная строка параметров.
     */
    public function get_signable_parameters()
    {
        $params = [];

        foreach ($this->parameters as $key_param => $value_param) {
            if (is_scalar($value_param)) {
                $params[$key_param] = $value_param;
            }
        }

        if (isset($params['oauth_signature'])) {
            unset($params['oauth_signature']);
        }

        return OAuthUtil::build_http_query($params);
    }

    /**
     * Возвращает базовую строку этого запроса.
     *
     * Базовая строка определяется как метод, URL
     * и параметры (нормализованные), каждый из которых закодирован в URL
     * и объединены с помощью &.
     *
     * @return string Базовая строка запроса.
     */
    public function get_signature_base_string()
    {
        $parts = array(
            $this->get_normalized_http_method(),
            $this->get_normalized_http_url(),
            $this->get_signable_parameters()
        );

        $parts = OAuthUtil::urlencode_rfc3986($parts);

        return implode('&', $parts);
    }

    /**
     * Просто преобразует HTTP-метод в верхний регистр.
     *
     * @return string HTTP-метод в верхнем регистре.
     */
    public function get_normalized_http_method()
    {
        return strtoupper($this->http_method);
    }

    /**
     * Разбирает URL и воссоздает его в формате
     * scheme://host/path
     *
     * @return string Нормализованный HTTP-URL.
     */
    public function get_normalized_http_url()
    {
        $parts = parse_url($this->http_url);

        $scheme = (isset($parts['scheme'])) ? $parts['scheme'] : 'http';
        $port = (isset($parts['port'])) ? $parts['port'] : (($scheme == 'https') ? '443' : '80');
        $host = (isset($parts['host'])) ? strtolower($parts['host']) : '';
        $path = (isset($parts['path'])) ? $parts['path'] : '';

        if (($scheme == 'https' && $port != '443') || ($scheme == 'http' && $port != '80')) {
            $host = "$host:$port";
        }
        return "$scheme://$host$path";
    }

    /**
     * Строит URL, который можно использовать для GET-запроса.
     *
     * @return string URL для GET-запроса.
     */
    public function to_url()
    {
        $post_data = $this->to_postdata();
        $out = $this->get_normalized_http_url();
        if ($post_data) {
            $out .= '?' . $post_data;
        }
        return $out;
    }

    /**
     * Строит данные, которые можно отправить в POST-запросе.
     *
     * @return string Данные для POST-запроса.
     */
    public function to_postdata()
    {
        return OAuthUtil::build_http_query($this->parameters);
    }

    /**
     * Строит заголовок Authorization:.
     *
     * @param string|null $realm Область.
     * @return array Заголовок авторизации.
     */
    public function to_header($realm = null)
    {
        $first = true;
        if ($realm) {
            $out = 'OAuth realm="' . OAuthUtil::urlencode_rfc3986($realm) . '"';
            $first = false;
        } else {
            $out = 'OAuth';
        }

        foreach ($this->parameters as $k => $v) {
            if (!str_starts_with($k, "oauth")) {
                continue;
            }
            if (is_array($v)) {
                continue;
            }
            $out .= ($first) ? ' ' : ',';
            $out .= OAuthUtil::urlencode_rfc3986($k) . '="' . OAuthUtil::urlencode_rfc3986($v) . '"';
            $first = false;
        }

        return array(
            'Authorization' => $out
        );
    }

    /**
     * Преобразование объекта в строку.
     *
     * @return string Строковое представление объекта OAuthRequest.
     */
    public function __toString()
    {
        return $this->to_url();
    }

    /**
     * Подписывает запрос.
     *
     * @param OAuthSignatureMethod $signature_method Метод подписи OAuth.
     * @param OAuthConsumer $consumer Потребитель OAuth.
     * @param OAuthToken $token Токен OAuth.
     */
    public function sign_request($signature_method, $consumer, $token)
    {
        $this->set_parameter("oauth_signature_method", $signature_method->get_name(), false);
        $signature = $this->build_signature($signature_method, $consumer, $token);
        $this->set_parameter("oauth_signature", $signature, false);
    }

    /**
     * Создает подпись.
     *
     * @param OAuthSignatureMethod $signature_method Метод подписи OAuth.
     * @param OAuthConsumer $consumer Потребитель OAuth.
     * @param OAuthToken $token Токен OAuth.
     * @return mixed Подпись.
     */
    public function build_signature($signature_method, $consumer, $token): mixed
    {
        return $signature_method->build_signature($this, $consumer, $token);
    }

    /**
     * Вспомогательная функция: текущая временная метка.
     *
     * @return int Текущая временная метка.
     */
    private static function generate_timestamp()
    {
        return time();
    }

    /**
     * Вспомогательная функция: текущий nonce.
     *
     * @return string Текущий nonce.
     */
    private static function generate_nonce()
    {
        $mt = microtime();
        $rand = mt_rand();

        return md5($mt . $rand); // md5s look nicer than numbers
    }
}
