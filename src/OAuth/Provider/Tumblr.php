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
use Triangle\Exception\UnexpectedApiResponseException;
use Triangle\OAuth\Adapter\OAuth1;
use Triangle\OAuth\Model\Profile;

/**
 * Tumblr OAuth1 provider adapter.
 */
class Tumblr extends OAuth1
{
    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.tumblr.com/v2/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://www.tumblr.com/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $requestTokenUrl = 'https://www.tumblr.com/oauth/request_token';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://www.tumblr.com/oauth/access_token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://www.tumblr.com/docs/en/api/v2';

    /**
     * {@inheritdoc}
     * @return Profile
     * @throws UnexpectedApiResponseException
     * @throws HttpClientFailureException
     * @throws HttpRequestFailedException
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('user/info');

        $data = new Collection($response);

        if (!$data->exists('response')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $userProfile->displayName = $data->filter('response')->filter('user')->get('name');

        foreach ($data->filter('response')->filter('user')->filter('blogs')->toArray() as $blog) {
            $blog = new Collection($blog);

            if ($blog->get('primary') && $blog->exists('url')) {
                $userProfile->identifier = $blog->get('url');
                $userProfile->profileURL = $blog->get('url');
                $userProfile->webSiteURL = $blog->get('url');
                $userProfile->description = strip_tags($blog->get('description'));

                $bloghostname = explode('://', $blog->get('url'));
                $bloghostname = substr($bloghostname[1], 0, -1);

                // store user's primary blog which will be used as target by setUserStatus
                $this->storeData('primary_blog', $bloghostname);

                break;
            }
        }

        return $userProfile;
    }

    /**
     * {@inheritdoc}
     */
    public function setUserStatus($status)
    {
        $status = is_string($status)
            ? ['type' => 'text', 'body' => $status]
            : $status;

        return $this->apiRequest('blog/' . $this->getStoredData('primary_blog') . '/post', 'POST', $status);
    }
}
