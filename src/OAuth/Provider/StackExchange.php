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
 * StackExchange OAuth2 provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => 'https:' . request()?->url(),
 *       'keys' => ['id' => '', 'secret' => ''],
 *       'site' => 'stackoverflow' // required parameter to call getUserProfile()
 *       'api_key' => '...' // that thing to receive a higher request quota.
 *   ];
 *
 *   $adapter = new Triangle\OAuth\Provider\StackExchange($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *       $tokens = $adapter->getAccessToken();
 *   } catch (\Exception $e ){
 *       echo $e->getMessage() ;
 *   }
 */
class StackExchange extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = null;

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.stackexchange.com/2.2/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://stackexchange.com/oauth';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://stackexchange.com/oauth/access_token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://api.stackexchange.com/docs/authentication';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $apiKey = $this->config->get('api_key');

        $this->apiRequestParameters = ['key' => $apiKey];
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
        $site = $this->config->get('site');

        $response = $this->apiRequest('me', 'GET', [
            'site' => $site,
            'access_token' => $this->getStoredData('access_token'),
        ]);

        if (!$response || !isset($response->items) || !isset($response->items[0])) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $data = new Collection($response->items[0]);

        $userProfile = new Profile();

        $userProfile->identifier = strval($data->get('user_id'));
        $userProfile->displayName = $data->get('display_name');
        $userProfile->photoURL = $data->get('profile_image');
        $userProfile->profileURL = $data->get('link');
        $userProfile->region = $data->get('location');
        $userProfile->age = $data->get('age');

        return $userProfile;
    }
}
