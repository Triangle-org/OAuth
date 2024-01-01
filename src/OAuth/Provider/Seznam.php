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

namespace Triangle\OAuth\Provider;

use Support\Collection;
use Triangle\Engine\Exception\HttpClientFailureException;
use Triangle\Engine\Exception\HttpRequestFailedException;
use Triangle\Engine\Exception\InvalidAccessTokenException;
use Triangle\Engine\Exception\UnexpectedApiResponseException;
use Triangle\OAuth\Adapter\OAuth2;
use Triangle\OAuth\Model\Profile;

/**
 * Seznam OAuth2 provider adapter.
 */
class Seznam extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://login.szn.cz/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://login.szn.cz/api/v1/oauth/auth';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://login.szn.cz/api/v1/oauth/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://vyvojari.seznam.cz/oauth/doc';

    /**
     * {@inheritdoc}
     * @return Profile
     * @throws UnexpectedApiResponseException
     * @throws HttpClientFailureException
     * @throws HttpRequestFailedException
     * @throws InvalidAccessTokenException
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('api/v1/user', 'GET', ['format' => 'json']);

        $data = new Collection($response);

        if (!$data->exists('oauth_user_id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $userProfile->identifier = $data->get('oauth_user_id');
        $userProfile->email = $data->get('account_name');
        $userProfile->firstName = $data->get('firstname');
        $userProfile->lastName = $data->get('lastname');
        $userProfile->photoURL = $data->get('avatar_url');

        return $userProfile;
    }
}
