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
 * TwitchTV OAuth2 provider adapter.
 */
class TwitchTV extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'user:read:email';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.twitch.tv/helix/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://id.twitch.tv/oauth2/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://id.twitch.tv/oauth2/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://dev.twitch.tv/docs/authentication/';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $this->apiRequestHeaders['Client-ID'] = $this->clientId;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile(): Profile
    {
        $response = $this->apiRequest('users');

        $data = new Collection($response);

        if (!$data->exists('data')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $users = $data->filter('data')->values();
        $user = new Collection($users[0]);

        $userProfile = new Profile();

        $userProfile->identifier = $user->get('id');
        $userProfile->displayName = $user->get('display_name');
        $userProfile->photoURL = $user->get('profile_image_url');
        $userProfile->email = $user->get('email');
        $userProfile->description = strip_tags($user->get('description'));
        $userProfile->profileURL = "https://www.twitch.tv/{$userProfile->displayName}";

        return $userProfile;
    }
}
