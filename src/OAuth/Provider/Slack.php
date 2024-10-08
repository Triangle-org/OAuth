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
 * Slack OAuth2 provider adapter.
 */
class Slack extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'identity.basic identity.email identity.avatar';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://slack.com/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://slack.com/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://slack.com/api/oauth.access';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://api.slack.com/docs/sign-in-with-slack';

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
        $response = $this->apiRequest('api/users.identity');

        $data = new Collection($response);

        if (!$data->exists('ok') || !$data->get('ok')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $userProfile->identifier = $data->filter('user')->get('id');
        $userProfile->displayName = $data->filter('user')->get('name');
        $userProfile->email = $data->filter('user')->get('email');
        $userProfile->photoURL = $this->findLargestImage($data);

        return $userProfile;
    }

    /**
     * Returns the url of the image with the highest resolution in the user
     * object.
     *
     * Slack sends multiple image urls with different resolutions. As they make
     * no guarantees which resolutions will be included we have to search all
     * <code>image_*</code> properties for the one with the highest resolution.
     * The resolution is attached to the property name such as
     * <code>image_32</code> or <code>image_192</code>.
     *
     * @param Collection $data response object as returned by
     *     <code>api/users.identity</code>
     *
     * @return string|null the value of the <code>image_*</code> property with
     *     the highest resolution.
     */
    private function findLargestImage(Collection $data)
    {
        $maxSize = 0;
        foreach ($data->filter('user')->properties() as $property) {
            if (preg_match('/^image_(\d+)$/', $property, $matches) === 1) {
                $availableSize = (int)$matches[1];
                if ($maxSize < $availableSize) {
                    $maxSize = $availableSize;
                }
            }
        }
        if ($maxSize > 0) {
            return $data->filter('user')->get('image_' . $maxSize);
        }
        return null;
    }
}
