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

use plugin\oauth\app\entity;
use support\Collection;
use Triangle\OAuth\Adapter\OAuth2;
use Triangle\OAuth\Exception\UnexpectedApiResponseException;
use Triangle\OAuth\Model\Contact;
use Triangle\OAuth\Model\Profile;

/**
 * Microsoft Graph OAuth2 provider adapter.
 *
 * Create an "Azure Active Directory" resource at https://portal.azure.com/
 * (not from the Visual Studio site).
 *
 * The "Supported account types" choice maps to the 'tenant' setting, see "Authority" @
 * https://docs.microsoft.com/en-us/azure/active-directory/develop/msal-client-application-configuration
 *
 * Example:
 *
 *   $config = [
 *       'callback' => '',
 *       'keys' => ['id' => '', 'secret' => ''],
 *       'tenant' => 'user',
 *         // ^ May be 'common', 'organizations' or 'consumers' or a specific tenant ID or a domain
 *   ];
 *
 *   $adapter = new plugin\oauth\app\provider\MicrosoftGraph($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *       $tokens = $adapter->getAccessToken();
 *   } catch (\Exception $e) {
 *       echo $e->getMessage() ;
 *   }
 */
class MicrosoftGraph extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'openid user.read contacts.read';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://graph.microsoft.com/v1.0/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.microsoft.com/en-us/graph/docs/concepts/php';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $tenant = $this->config->get('tenant');
        if (!empty($tenant)) {
            $adjustedEndpoints = [
                'authorize_url' => str_replace('/common/', '/' . $tenant . '/', $this->authorizeUrl),
                'access_token_url' => str_replace('/common/', '/' . $tenant . '/', $this->accessTokenUrl),
            ];

            $this->setApiEndpoints($adjustedEndpoints);
        }
    }

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
        $userProfile->displayName = $data->get('displayName');
        $userProfile->firstName = $data->get('givenName');
        $userProfile->lastName = $data->get('surname');
        $userProfile->language = $data->get('preferredLanguage');

        $userProfile->phone = $data->get('mobilePhone');
        if (empty($userProfile->phone)) {
            $businessPhones = $data->get('businessPhones');
            if (isset($businessPhones[0])) {
                $userProfile->phone = $businessPhones[0];
            }
        }

        $userProfile->email = $data->get('mail');
        if (empty($userProfile->email)) {
            $email = $data->get('userPrincipalName');
            if (str_contains($email, '@')) {
                $userProfile->email = $email;
            }
        }

        return $userProfile;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserContacts(): array
    {
        $apiUrl = 'me/contacts?$top=50';
        $contacts = [];

        do {
            $response = $this->apiRequest($apiUrl);
            $data = new Collection($response);
            if (!$data->exists('value')) {
                throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
            }
            foreach ($data->filter('value')->toArray() as $entry) {
                $entry = new Collection($entry);
                $userContact = new Contact();
                $userContact->identifier = $entry->get('id');
                $userContact->displayName = $entry->get('displayName');
                $emailAddresses = $entry->get('emailAddresses');
                if (!empty($emailAddresses)) {
                    $userContact->email = $emailAddresses[0]->address;
                }
                // only add to collection if we have usefull data
                if (!empty($userContact->displayName) || !empty($userContact->email)) {
                    $contacts[] = $userContact;
                }
            }

            if ($data->exists('@odata.nextLink')) {
                $apiUrl = $data->get('@odata.nextLink');

                $pagedList = true;
            } else {
                $pagedList = false;
            }
        } while ($pagedList);

        return $contacts;
    }
}
