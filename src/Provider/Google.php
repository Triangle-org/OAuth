<?php

/**
 * @package     FrameX (FX) OAuth Plugin
 * @link        https://localzet.gitbook.io
 *
 * @author      localzet <creator@localzet.ru>
 *
 * @copyright   Copyright (c) 2018-2020 Zorin Projects
 * @copyright   Copyright (c) 2020-2022 NONA Team
 *
 * @license     https://www.localzet.ru/license GNU GPLv3 License
 */

namespace Triangle\OAuth\Provider;

use Exception;
use plugin\oauth\app\entity;
use support\Collection;
use Triangle\OAuth\Adapter\OAuth2;
use Triangle\OAuth\Exception\UnexpectedApiResponseException;
use Triangle\OAuth\Model\Contact;
use Triangle\OAuth\Model\Profile;

/**
 * Google OAuth2 provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => '',
 *       'keys' => ['id' => '', 'secret' => ''],
 *       'scope' => 'https://www.googleapis.com/auth/userinfo.profile',
 *
 *        // google's custom auth url params
 *       'authorize_url_parameters' => [
 *              'approval_prompt' => 'force', // to pass only when you need to acquire a new refresh token.
 *              'access_type' => ..,      // is set to 'offline' by default
 *              'hd' => ..,
 *              'state' => ..,
 *              // etc.
 *       ]
 *   ];
 *
 *   $adapter = new plugin\oauth\app\provider\Google($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *       $tokens = $adapter->getAccessToken();
 *       $contacts = $adapter->getUserContacts(['max-results' => 75]);
 *   } catch (\Exception $e) {
 *       echo $e->getMessage() ;
 *   }
 */
class Google extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    // phpcs:ignore
    protected $scope = 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://www.googleapis.com/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://accounts.google.com/o/oauth2/auth';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://accounts.google.com/o/oauth2/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developers.google.com/identity/protocols/OAuth2';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        /**
         * Для Google Suite (ограничение по домену)
         * используй в конфигурации провайдера
         *  [
         *      'authorize_url_parameters' => [
         *          'hd' => 'gs.domain.com'
         *      ]
         *  ]
         */
        $this->AuthorizeUrlParameters += [
            'access_type' => 'offline'
        ];

        if ($this->isRefreshTokenAvailable()) {
            $this->tokenRefreshParameters += [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret
            ];
        }
    }

    /**
     * {@inheritdoc}
     *
     * See: https://developers.google.com/identity/protocols/OpenIDConnect#obtainuserinfo
     */
    public function getUserProfile(): Profile
    {
        $response = $this->apiRequest('oauth2/v3/userinfo');

        $data = new Collection($response);

        if (!$data->exists('sub')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $userProfile->identifier = $data->get('sub');
        $userProfile->firstName = $data->get('given_name');
        $userProfile->lastName = $data->get('family_name');
        $userProfile->displayName = $data->get('name');
        $userProfile->photoURL = $data->get('picture');
        $userProfile->profileURL = $data->get('profile');
        $userProfile->gender = $data->get('gender');
        $userProfile->language = $data->get('locale');
        $userProfile->email = $data->get('email');

        $userProfile->emailVerified = $data->get('email_verified') ? $userProfile->email : '';

        if ($this->config->get('photo_size')) {
            $userProfile->photoURL .= '?sz=' . $this->config->get('photo_size');
        }

        return $userProfile;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserContacts($parameters = []): array
    {
        $parameters = ['max-results' => 500] + $parameters;

        // Google Gmail and Android contacts
        if (str_contains($this->scope, '/m8/feeds/') || str_contains($this->scope, '/auth/contacts.readonly')) {
            return $this->getGmailContacts($parameters);
        }

        return [];
    }

    /**
     * Retrieve Gmail contacts
     *
     * @param array $parameters
     *
     * @return array
     *
     * @throws Exception
     */
    protected function getGmailContacts($parameters = [])
    {
        $url = 'https://www.google.com/m8/feeds/contacts/default/full?'
            . http_build_query(array_replace(['alt' => 'json', 'v' => '3.0'], (array)$parameters));

        $response = $this->apiRequest($url);

        if (!$response) {
            return [];
        }

        $contacts = [];

        if (isset($response->feed->entry)) {
            foreach ($response->feed->entry as $idx => $entry) {
                $uc = new Contact();

                $uc->email = isset($entry->{'gd$email'}[0]->address)
                    ? (string)$entry->{'gd$email'}[0]->address
                    : '';

                $uc->displayName = isset($entry->title->{'$t'}) ? (string)$entry->title->{'$t'} : '';
                $uc->identifier = ($uc->email != '') ? $uc->email : '';
                $uc->description = '';

                if (property_exists($response, 'website')) {
                    if (is_array($response->website)) {
                        foreach ($response->website as $w) {
                            if ($w->primary == true) {
                                $uc->webSiteURL = $w->value;
                            }
                        }
                    } else {
                        $uc->webSiteURL = $response->website->value;
                    }
                } else {
                    $uc->webSiteURL = '';
                }

                $contacts[] = $uc;
            }
        }

        return $contacts;
    }
}
