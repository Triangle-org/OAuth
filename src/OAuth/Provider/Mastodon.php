<?php declare(strict_types=1);

/**
 * @package     Localzet OAuth
 * @link        https://github.com/localzet/OAuth
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2018-2023 Localzet Group
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

use CurlFile;
use Support\Collection;
use Triangle\OAuth\Adapter\OAuth2;
use Triangle\OAuth\Exception\HttpClientFailureException;
use Triangle\OAuth\Exception\HttpRequestFailedException;
use Triangle\OAuth\Exception\InvalidAccessTokenException;
use Triangle\OAuth\Exception\InvalidApplicationCredentialsException;
use Triangle\OAuth\Exception\UnexpectedApiResponseException;
use Triangle\OAuth\Model\Profile;

class Mastodon extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    public $scope = 'read';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://docs.joinmastodon.org/spec/oauth/';

    /**
     * {@inheritdoc}
     * @throws InvalidApplicationCredentialsException
     * @throws InvalidApplicationCredentialsException
     */
    protected function configure()
    {
        parent::configure();

        if (!$this->config->exists('url')) {
            throw new InvalidApplicationCredentialsException(
                'You must define a Mastodon instance url'
            );
        }
        $url = $this->config->get('url');

        $this->apiBaseUrl = $url . '/api/v1';

        $this->authorizeUrl = $url . '/oauth/authorize';
        $this->accessTokenUrl = $url . '/oauth/token';
    }

    /**
     * {@inheritdoc}
     * @return Profile
     * @throws HttpClientFailureException
     * @throws HttpRequestFailedException
     * @throws InvalidAccessTokenException
     * @throws UnexpectedApiResponseException
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('accounts/verify_credentials');

        $data = new Collection($response);

        if (!$data->exists('id') || !$data->get('id')) {
            throw new UnexpectedApiResponseException(
                'Provider API returned an unexpected response.'
            );
        }

        $userProfile = new Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('username');
        $userProfile->photoURL =
            $data->get('avatar') ?: $data->get('avatar_static');
        $userProfile->webSiteURL = $data->get('url');
        $userProfile->description = $data->get('note');
        $userProfile->firstName = $data->get('display_name');

        return $userProfile;
    }

    /**
     * @throws HttpRequestFailedException
     * @throws HttpClientFailureException
     * @throws InvalidAccessTokenException
     */
    public function setUserStatus($status)
    {
        // Prepare request parameters.
        $params = [];
        if (isset($status['message'])) {
            $params['status'] = $status['message'];
        }

        if (isset($status['picture'])) {
            $headers = [
                'Content-Type' => 'multipart/form-data',
            ];

            $pictures = $status['picture'];

            $ids = [];

            foreach ($pictures as $picture) {
                $images = $this->apiRequest(
                    $this->config->get('url') . '/api/v2/media',
                    'POST',
                    [
                        'file' => new CurlFile(
                            $picture,
                            'image/jpg',
                            'filename'
                        ),
                    ],
                    $headers,
                    true
                );

                $ids[] = $images->id;
            }

            $params['media_ids'] = $ids;
        }

        $headers = [
            'Content-Type' => 'application/json',
        ];

        return $this->apiRequest(
            'statuses',
            'POST',
            $params,
            $headers
        );
    }
}
