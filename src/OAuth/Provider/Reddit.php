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
 * Reddit OAuth2 provider adapter.
 */
class Reddit extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'identity';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://oauth.reddit.com/api/v1/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://ssl.reddit.com/api/v1/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://ssl.reddit.com/api/v1/access_token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://github.com/reddit/reddit/wiki/OAuth2';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $this->AuthorizeUrlParameters += [
            'duration' => 'permanent'
        ];

        $this->tokenExchangeParameters = [
            'client_id' => $this->clientId,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->callback
        ];

        $this->tokenExchangeHeaders = [
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
        ];

        $this->tokenRefreshHeaders = $this->tokenExchangeHeaders;
    }

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
        $response = $this->apiRequest('me.json');

        $data = new Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('name');
        $userProfile->profileURL = 'https://www.reddit.com/user/' . $data->get('name') . '/';
        $userProfile->photoURL = $data->get('icon_img');

        return $userProfile;
    }
}
