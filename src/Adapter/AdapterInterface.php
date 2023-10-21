<?php

/**
 * @package     FrameX (FX) OAuth Plugin
 * @link        https://localzet.gitbook.io
 *
 * @author      localzet <creator@localzet.ru>
 *
 * @copyright   Copyright (c) 2018-2020 Zorin Projects
 * @copyright   Copyright (c) 2020-2022 NONA Team
 *
 * @license     https://www.localzet.ru/license GNU GPLv3 License
 */

namespace Triangle\OAuth\Adapter;

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
     * @return bool|null
     */
    public function authenticate(): ?bool;

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
