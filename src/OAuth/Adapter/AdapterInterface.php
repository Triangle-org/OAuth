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

namespace Triangle\OAuth\Adapter;

use localzet\HTTP\Client as HttpClient;
use Monolog\Logger;
use Triangle\OAuth\Model\Activity;
use Triangle\OAuth\Model\Contact;
use Triangle\OAuth\Model\Profile;
use Triangle\OAuth\Storage\StorageInterface;

/**
 * Interface AdapterInterface
 */
interface AdapterInterface
{
    /**
     * Initiate the appropriate protocol and process/automate the authentication or authorization flow.
     *
     * @return bool|null
     */
    public function authenticate();

    /**
     * Returns TRUE if the user is connected
     *
     * @return bool
     */
    public function isConnected();

    /**
     * Clear all access token in storage
     */
    public function disconnect();

    /**
     * Retrieve the connected user profile
     *
     * @return Profile
     */
    public function getUserProfile();

    /**
     * Retrieve the connected user contacts list
     *
     * @return Contact[]
     */
    public function getUserContacts();

    /**
     * Retrieve the connected user pages|companies|groups list
     *
     * @return array
     */
    public function getUserPages();

    /**
     * Retrieve the user activity stream
     *
     * @param string $stream
     *
     * @return Activity[]
     */
    public function getUserActivity($stream);

    /**
     * Post a status on user wall|timeline|blog|website|etc.
     *
     * @param string|array $status
     *
     * @return mixed API response
     */
    public function setUserStatus($status);

    /**
     * Post a status on page|company|group wall.
     *
     * @param string|array $status
     * @param string $pageId
     *
     * @return mixed API response
     */
    public function setPageStatus($status, $pageId);

    /**
     * Send a signed request to provider API
     *
     * @param string $url
     * @param string $method
     * @param array $parameters
     * @param array $headers
     * @param bool $multipart
     *
     * @return mixed
     */
    public function apiRequest($url, $method = 'GET', $parameters = [], $headers = [], $multipart = false);

    /**
     * Do whatever may be necessary to make sure tokens do not expire.
     * Intended to be be called frequently, e.g. via Cron.
     */
    public function maintainToken();

    /**
     * Return oauth access tokens.
     *
     * @return array
     */
    public function getAccessToken();

    /**
     * Set oauth access tokens.
     *
     * @param array $tokens
     */
    public function setAccessToken($tokens = []);

    /**
     * Set http client instance.
     *
     * @param HttpClient|null $httpClient
     */
    public function setHttpClient(HttpClient $httpClient = null);

    /**
     * Return http client instance.
     */
    public function getHttpClient();

    /**
     * Set storage instance.
     *
     * @param StorageInterface|null $storage
     */
    public function setStorage(StorageInterface $storage = null);

    /**
     * Return storage instance.
     */
    public function getStorage();

    /**
     * Set Logger instance.
     *
     * @param Logger|null $logger
     */
    public function setLogger(?Logger $logger = null);

    /**
     * Return logger instance.
     */
    public function getLogger();
}
