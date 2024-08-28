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

use Exception;
use Support\Collection;
use Triangle\Exception\HttpClientFailureException;
use Triangle\Exception\HttpRequestFailedException;
use Triangle\Exception\InvalidAccessTokenException;
use Triangle\Exception\UnexpectedApiResponseException;
use Triangle\OAuth\Adapter\OAuth2;
use Triangle\OAuth\Model\Profile;

/**
 * Set up your OAuth2 at https://bitbucket.org/<yourusername>/workspace/settings/api
 */

/**
 * BitBucket OAuth2 provider adapter.
 */
class BitBucket extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'email';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.bitbucket.org/2.0/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://bitbucket.org/site/oauth2/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://bitbucket.org/site/oauth2/access_token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.atlassian.com/bitbucket/concepts/oauth2.html';

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
        $response = $this->apiRequest('user');

        $data = new Collection($response);

        if (!$data->exists('uuid')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $userProfile->identifier = $data->get('uuid');
        $userProfile->profileURL = 'https://bitbucket.org/' . $data->get('username') . '/';
        $userProfile->displayName = $data->get('display_name');
        $userProfile->email = $data->get('email');
        $userProfile->webSiteURL = $data->get('website');
        $userProfile->region = $data->get('location');

        $userProfile->displayName = $userProfile->displayName ?: $data->get('username');

        if (empty($userProfile->email) && str_contains($this->scope, 'email')) {
            try {
                // user email is not mandatory so keep it quiet
                $userProfile = $this->requestUserEmail($userProfile);
            } catch (Exception $e) {
            }
        }

        return $userProfile;
    }

    /**
     * Request user email
     *
     * @param $userProfile
     *
     * @return Profile
     *
     * @throws Exception
     */
    protected function requestUserEmail($userProfile)
    {
        $response = $this->apiRequest('user/emails');

        foreach ($response->values as $idx => $item) {
            if ($item->is_primary) {
                $userProfile->email = $item->email;

                if ($item->is_confirmed) {
                    $userProfile->emailVerified = $userProfile->email;
                }

                break;
            }
        }

        return $userProfile;
    }
}
