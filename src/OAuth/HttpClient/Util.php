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

namespace Triangle\OAuth\HttpClient;

/**
 * HttpClient\Util home to a number of utility functions.
 */
class Util
{
    /**
     * Redirect handler.
     *
     * @var callable|null
     */
    protected static $redirectHandler;

    /**
     * Exit handler.
     *
     * @var callable|null
     */
    protected static $exitHandler;

    /**
     * Redirect to a given URL.
     *
     * In case your application need to perform certain required actions before OAuth redirect users
     * to IDPs websites, the default behaviour can be altered in one of two ways:
     *   If callable $redirectHandler is defined, it will be called instead.
     *   If callable $exitHandler is defined, it will be called instead of exit().
     *
     * @param string $url
     *
     * @return mixed
     */
    public static function redirect($url)
    {
        if (static::$redirectHandler) {
            return call_user_func(static::$redirectHandler, $url);
        }

        header(sprintf('Location: %s', $url));

        if (static::$exitHandler) {
            return call_user_func(static::$exitHandler);
        }

        exit(1);
    }

    /**
     * Returns the Current URL.
     *
     * @param bool $requestUri TRUE to use $_SERVER['REQUEST_URI'], FALSE for $_SERVER['PHP_SELF']
     *
     * @return string
     */
    public static function getCurrentUrl($requestUri = false)
    {
        return 'https:' . $requestUri ? request()?->fullUrl() : request()?->url();
    }
}
