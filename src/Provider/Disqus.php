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

namespace Triangle\OAuth\Provider;

use support\Collection;
use Triangle\OAuth\Adapter\OAuth2;
use Triangle\OAuth\Exception\UnexpectedApiResponseException;
use Triangle\OAuth\Model\Profile;

/**
 * Disqus OAuth2 provider adapter.
 */
class Disqus extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'read,email';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://disqus.com/api/3.0/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://disqus.com/api/oauth/2.0/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://disqus.com/api/oauth/2.0/access_token/';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://disqus.com/api/docs/auth/';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $this->apiRequestParameters = [
            'api_key' => $this->clientId, 'api_secret' => $this->clientSecret
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile(): Profile
    {
        $response = $this->apiRequest('users/details');

        $data = new Collection($response);

        if (!$data->filter('response')->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $data = $data->filter('response');

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('name');
        $userProfile->description = $data->get('bio');
        $userProfile->profileURL = $data->get('profileUrl');
        $userProfile->email = $data->get('email');
        $userProfile->region = $data->get('location');
        $userProfile->description = $data->get('about');

        $userProfile->photoURL = $data->filter('avatar')->get('permalink');

        $userProfile->displayName = $userProfile->displayName ?: $data->get('username');

        return $userProfile;
    }
}
