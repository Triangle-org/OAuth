<?php

/**
 * @package     Triangle OAuth Plugin
 * @link        https://github.com/Triangle-org/OAuth
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2018-2023 Localzet Group
 * @license     GNU Affero General Public License, version 3
 *
 *              This program is free software: you can redistribute it and/or modify
 *              it under the terms of the GNU Affero General Public License as
 *              published by the Free Software Foundation, either version 3 of the
 *              License, or (at your option) any later version.
 *
 *              This program is distributed in the hope that it will be useful,
 *              but WITHOUT ANY WARRANTY; without even the implied warranty of
 *              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *              GNU Affero General Public License for more details.
 *
 *              You should have received a copy of the GNU Affero General Public License
 *              along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Triangle\OAuth\Adapter;

use Triangle\Engine\Http\Response;
use Triangle\OAuth\Model\Activity;
use Triangle\OAuth\Model\Contact;
use Triangle\OAuth\Model\Profile;

/**
 * Interface AdapterInterface
 */
interface AdapterInterface
{
    /**
     * Initiate the appropriate protocol and process/automate the authentication or authorization flow.
     *
     * @return bool|Response|null
     */
    public function authenticate(): bool|Response|null;

    /**
     * Returns TRUE if the user is connected
     *
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * Clear all access token in storage
     */
    public function disconnect();

    /**
     * Retrieve the connected user profile
     *
     * @return Profile
     */
    public function getUserProfile(): Profile;

    /**
     * Retrieve the connected user contacts list
     *
     * @return Contact[]
     */
    public function getUserContacts(): array;

    /**
     * Retrieve the connected user pages|companies|groups list
     *
     * @return array
     */
    public function getUserPages(): array;

    /**
     * Retrieve the user activity stream
     *
     * @param string $stream
     *
     * @return Activity[]
     */
    public function getUserActivity(string $stream): array;

    /**
     * Post a status on user wall|timeline|blog|website|etc.
     *
     * @param array|string $status
     *
     * @return mixed API response
     */
    public function setUserStatus(array|string $status): mixed;

    /**
     * Post a status on page|company|group wall.
     *
     * @param array|string $status
     * @param string $pageId
     *
     * @return mixed API response
     */
    public function setPageStatus(array|string $status, string $pageId): mixed;

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
    public function apiRequest(string $url, string $method = 'GET', array $parameters = [], array $headers = [], bool $multipart = false): mixed;

    /**
     * Do whatever may be necessary to make sure tokens do not expire.
     * Intended to be called frequently, e.g. via Cron.
     */
    public function maintainToken();

    /**
     * Return oauth access tokens.
     *
     * @return array
     */
    public function getAccessToken(): array;

    /**
     * Set oauth access tokens.
     *
     * @param array $tokens
     */
    public function setAccessToken(array $tokens = []);
}
