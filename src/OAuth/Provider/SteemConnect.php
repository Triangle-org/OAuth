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
 * Instagram OAuth2 provider adapter.
 */
class SteemConnect extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'login,vote';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://v2.steemconnect.com/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://v2.steemconnect.com/oauth2/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://v2.steemconnect.com/api/oauth2/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://steemconnect.com/';

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
        $response = $this->apiRequest('api/me');

        $data = new Collection($response);

        if (!$data->exists('result')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $data = $data->filter('result');

        $userProfile->identifier = $data->get('id');
        $userProfile->description = $data->get('about');
        $userProfile->photoURL = $data->get('profile_image');
        $userProfile->webSiteURL = $data->get('website');
        $userProfile->displayName = $data->get('name');

        return $userProfile;
    }
}
