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
use Triangle\Exception\UnexpectedApiResponseException;
use Triangle\OAuth\Adapter\OAuth2;
use Triangle\OAuth\Model\Profile;

/**
 * LinkedIn OAuth2 provider adapter.
 */
class LinkedIn extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'r_liteprofile r_emailaddress';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.linkedin.com/v2/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://www.linkedin.com/oauth/v2/authorization';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://www.linkedin.com/oauth/v2/accessToken';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://docs.microsoft.com/en-us/linkedin/shared/authentication/authentication';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        if ($this->isRefreshTokenAvailable()) {
            $this->tokenRefreshParameters += [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret
            ];
        }
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     * @throws UnexpectedApiResponseException
     */
    public function getUserProfile()
    {
        $fields = [
            'id',
            'firstName',
            'lastName',
            'profilePicture(displayImage~:playableStreams)',
        ];


        $response = $this->apiRequest('me', 'GET', ['projection' => '(' . implode(',', $fields) . ')']);
        $data = new Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        // Handle localized names.
        $userProfile->firstName = $data
            ->filter('firstName')
            ->filter('localized')
            ->get($this->getPreferredLocale($data, 'firstName'));

        $userProfile->lastName = $data
            ->filter('lastName')
            ->filter('localized')
            ->get($this->getPreferredLocale($data, 'lastName'));

        $userProfile->identifier = $data->get('id');
        $userProfile->email = $this->getUserEmail();
        $userProfile->emailVerified = $userProfile->email;
        $userProfile->displayName = trim($userProfile->firstName . ' ' . $userProfile->lastName);

        $photo_elements = $data
            ->filter('profilePicture')
            ->filter('displayImage~')
            ->get('elements');
        $userProfile->photoURL = $this->getUserPhotoUrl($photo_elements);

        return $userProfile;
    }

    /**
     * Returns a user photo.
     *
     * @param array $elements
     *   List of file identifiers related to this artifact.
     *
     * @return string
     *   The user photo URL.
     *
     * @see https://docs.microsoft.com/en-us/linkedin/shared/references/v2/profile/profile-picture
     */
    public function getUserPhotoUrl($elements)
    {
        if (is_array($elements)) {
            // Get the largest picture from the list which is the last one.
            $element = end($elements);
            if (!empty($element->identifiers)) {
                return reset($element->identifiers)->identifier;
            }
        }

        return null;
    }

    /**
     * Returns an email address of user.
     *
     * @return string
     *   The user email address.
     *
     * @throws Exception
     */
    public function getUserEmail()
    {
        $response = $this->apiRequest('emailAddress', 'GET', [
            'q' => 'members',
            'projection' => '(elements*(handle~))',
        ]);
        $data = new Collection($response);

        foreach ($data->filter('elements')->toArray() as $element) {
            $item = new Collection($element);

            if ($email = $item->filter('handle~')->get('emailAddress')) {
                return $email;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @see https://docs.microsoft.com/en-us/linkedin/consumer/integrations/self-serve/share-on-linkedin
     * @throws Exception
     */
    public function setUserStatus($status, $userID = null)
    {
        if (!str_contains($this->scope, 'w_member_social')) {
            throw new Exception('Set user status requires w_member_social permission!');
        }

        if (is_string($status)) {
            $status = [
                'author' => 'urn:li:person:' . $userID,
                'lifecycleState' => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary' => [
                            'text' => $status,
                        ],
                        'shareMediaCategory' => 'NONE',
                    ],
                ],
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
                ],
            ];
        }


        $headers = [
            'Content-Type' => 'application/json',
            'x-li-format' => 'json',
            'X-Restli-Protocol-Version' => '2.0.0',
        ];

        return $this->apiRequest("ugcPosts", 'POST', $status, $headers);
    }

    /**
     * Returns a preferred locale for given field.
     *
     * @param Collection $data
     *   A data to check.
     * @param string $field_name
     *   A field name to perform.
     *
     * @return string
     *   A field locale.
     */
    protected function getPreferredLocale($data, $field_name)
    {
        $locale = $data->filter($field_name)->filter('preferredLocale');
        if ($locale) {
            return $locale->get('language') . '_' . $locale->get('country');
        }

        return 'en_US';
    }
}
