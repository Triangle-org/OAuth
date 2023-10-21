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
use support\Parser;
use Triangle\OAuth\Adapter\OAuth2;
use Triangle\OAuth\Exception\UnexpectedApiResponseException;
use Triangle\OAuth\Model\Profile;

/**
 * Spotify OAuth2 provider adapter.
 */
class Spotify extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'user-read-email';

    /**
     * {@inheritdoc}
     */
    public $apiBaseUrl = 'https://api.spotify.com/v1/';

    /**
     * {@inheritdoc}
     */
    public $authorizeUrl = 'https://accounts.spotify.com/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://accounts.spotify.com/api/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.spotify.com/documentation/general/guides/authorization-guide/';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile(): Profile
    {
        $response = $this->apiRequest('me');

        $data = new Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('display_name');
        $userProfile->email = $data->get('email');
        $userProfile->emailVerified = $data->get('email');
        $userProfile->profileURL = $data->filter('external_urls')->get('spotify');
        $userProfile->photoURL = $data->filter('images')->get('url');
        $userProfile->country = $data->get('country');

        if ($data->exists('birthdate')) {
            $this->fetchBirthday($userProfile, $data->get('birthdate'));
        }

        return $userProfile;
    }

    /**
     * Fetch use birthday
     *
     * @param Profile $userProfile
     * @param              $birthday
     *
     * @return Profile
     */
    protected function fetchBirthday(Profile $userProfile, $birthday)
    {
        $result = (new Parser())->parseBirthday($birthday, '-');

        $userProfile->birthDay = (int)$result[0];
        $userProfile->birthMonth = (int)$result[1];
        $userProfile->birthYear = (int)$result[2];

        return $userProfile;
    }
}
