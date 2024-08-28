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
use Triangle\Exception\HttpClientFailureException;
use Triangle\Exception\HttpRequestFailedException;
use Triangle\Exception\InvalidAccessTokenException;
use Triangle\Exception\UnexpectedApiResponseException;
use Triangle\OAuth\Adapter\OAuth2;
use Triangle\OAuth\Model\Profile;

/**
 * GitLab OAuth2 provider adapter.
 */
class Strava extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'profile:read_all';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://www.strava.com/api/v3/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://www.strava.com/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://www.strava.com/oauth/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developers.strava.com/docs/reference/';

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
        $response = $this->apiRequest('athlete');

        $data = new Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->firstName = $data->get('firstname');
        $userProfile->lastName = $data->get('lastname');
        $userProfile->gender = $data->get('sex');
        $userProfile->country = $data->get('country');
        $userProfile->city = $data->get('city');
        $userProfile->email = $data->get('email');

        $userProfile->displayName = $userProfile->displayName ?: $data->get('username');

        return $userProfile;
    }
}
