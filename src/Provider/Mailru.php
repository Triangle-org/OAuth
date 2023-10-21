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
 * Mailru OAuth2 provider adapter.
 */
class Mailru extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'http://www.appsmail.ru/platform/api';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://connect.mail.ru/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://connect.mail.ru/oauth/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://api.mail.ru/docs/guides/oauth/';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile(): Profile
    {
        $params = [
            'app_id' => $this->clientId,
            'method' => 'users.getInfo',
            'secure' => 1,
            'session_key' => $this->getStoredData('access_token'),
        ];
        $sign = md5(http_build_query($params, '', '') . $this->clientSecret);

        $param = [
            'app_id' => $this->clientId,
            'method' => 'users.getInfo',
            'secure' => 1,
            'session_key' => $this->getStoredData('access_token'),
            'sig' => $sign,
        ];

        $response = $this->apiRequest('', 'GET', $param);

        $data = new Collection($response[0]);

        if (!$data->exists('uid')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $userProfile->identifier = $data->get('uid');
        $userProfile->email = $data->get('email');
        $userProfile->firstName = $data->get('first_name');
        $userProfile->lastName = $data->get('last_name');
        $userProfile->displayName = $data->get('nick');
        $userProfile->photoURL = $data->get('pic');
        $userProfile->profileURL = $data->get('link');
        $userProfile->gender = $data->get('sex');
        $userProfile->age = $data->get('age');

        return $userProfile;
    }
}
