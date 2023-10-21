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

namespace Triangle\OAuth\Adapter;

use support\Collection;
use Triangle\Engine\Http\Response;
use Triangle\OAuth\Base\LightOpenID;
use Triangle\OAuth\Exception\AuthorizationDeniedException;
use Triangle\OAuth\Exception\InvalidOpenidIdentifierException;
use Triangle\OAuth\Exception\UnexpectedApiResponseException;
use Triangle\OAuth\Model\Profile;

/**
 * This class can be used to simplify the authentication flow of OpenID based service providers.
 *
 * Subclasses (i.e., providers adapters) can either use the already provided methods or override
 * them when necessary.
 */
abstract class OpenID extends AbstractAdapter implements AdapterInterface
{
    /**
     * LightOpenID instance
     *
     * @var object
     */
    protected $openIdClient = null;

    /**
     * Openid provider identifier
     *
     * @var string
     */
    protected $openidIdentifier = '';

    /**
     * IPD API Documentation
     *
     * OPTIONAL.
     *
     * @var string
     */
    protected $apiDocumentation = '';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        if ($this->config->exists('openid_identifier')) {
            $this->openidIdentifier = $this->config->get('openid_identifier');
        }

        if (empty($this->openidIdentifier)) {
            throw new InvalidOpenidIdentifierException('OpenID adapter requires an openid_identifier.', 4);
        }

        $this->setCallback($this->config->get('callback'));
        $this->setApiEndpoints($this->config->get('endpoints'));
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        $hostPort = parse_url($this->callback, PHP_URL_PORT);
        $hostUrl = parse_url($this->callback, PHP_URL_HOST);

        if ($hostPort) {
            $hostUrl .= ':' . $hostPort;
        }

        // @fixme: add proxy
        $this->openIdClient = new LightOpenID($hostUrl, null);
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(): bool|Response|null
    {
        $this->logger->info(sprintf('%s::authenticate()', get_class($this)));

        if ($this->isConnected()) {
            return true;
        }

        if (empty(request()->all('openid_mode'))) {
            return $this->authenticateBegin();
        } else {
            $this->authenticateFinish();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected(): bool
    {
        return (bool)$this->storage->get($this->providerId . '.user');
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        $this->storage->delete($this->providerId . '.user');

        return true;
    }

    /**
     * Initiate the authorization protocol
     *
     * Include and instantiate LightOpenID
     */
    protected function authenticateBegin()
    {
        $this->openIdClient->identity = $this->openidIdentifier;
        $this->openIdClient->returnUrl = $this->callback;
        $this->openIdClient->required = [
            'namePerson/first',
            'namePerson/last',
            'namePerson/friendly',
            'namePerson',
            'contact/email',
            'birthDate',
            'birthDate/birthDay',
            'birthDate/birthMonth',
            'birthDate/birthYear',
            'person/gender',
            'pref/language',
            'contact/postalCode/home',
            'contact/city/home',
            'contact/country/home',

            'media/image/default',
        ];

        $authUrl = $this->openIdClient->authUrl();

        $this->logger->debug(sprintf('%s::authenticateBegin(), redirecting user to:', get_class($this)), [$authUrl]);

        redirect($authUrl);
    }

    /**
     * Finalize the authorization process.
     *
     * @throws AuthorizationDeniedException
     * @throws UnexpectedApiResponseException
     */
    protected function authenticateFinish()
    {
        $this->logger->debug(sprintf('%s::authenticateFinish(), callback url:', get_class($this)));

        if ($this->openIdClient->mode == 'cancel') {
            throw new AuthorizationDeniedException('User has cancelled the authentication.');
        }

        if (!$this->openIdClient->validate()) {
            throw new UnexpectedApiResponseException('Invalid response received.');
        }

        $openidAttributes = $this->openIdClient->getAttributes();

        if (!$this->openIdClient->identity) {
            throw new UnexpectedApiResponseException('Provider returned an unexpected response.');
        }

        $userProfile = $this->fetchUserProfile($openidAttributes);

        /* with openid providers we only get user profiles once, so we store it */
        $this->storage->set($this->providerId . '.user', $userProfile);
    }

    /**
     * Fetch user profile from received openid attributes
     *
     * @param array $openidAttributes
     *
     * @return Profile
     */
    protected function fetchUserProfile($openidAttributes)
    {
        $data = new Collection($openidAttributes);

        $userProfile = new Profile();

        $userProfile->identifier = $this->openIdClient->identity;

        $userProfile->firstName = $data->get('namePerson/first');
        $userProfile->lastName = $data->get('namePerson/last');
        $userProfile->email = $data->get('contact/email');
        $userProfile->language = $data->get('pref/language');
        $userProfile->country = $data->get('contact/country/home');
        $userProfile->zip = $data->get('contact/postalCode/home');
        $userProfile->gender = $data->get('person/gender');
        $userProfile->photoURL = $data->get('media/image/default');
        $userProfile->birthDay = $data->get('birthDate/birthDay');
        $userProfile->birthMonth = $data->get('birthDate/birthMonth');
        $userProfile->birthYear = $data->get('birthDate/birthDate');

        $userProfile = $this->fetchUserGender($userProfile, $data->get('person/gender'));

        $userProfile = $this->fetchUserDisplayName($userProfile, $data);

        return $userProfile;
    }

    /**
     * Extract users display names
     *
     * @param Profile $userProfile
     * @param Collection $data
     *
     * @return Profile
     */
    protected function fetchUserDisplayName(Profile $userProfile, Collection $data)
    {
        $userProfile->displayName = $data->get('namePerson');

        $userProfile->displayName = $userProfile->displayName
            ? $userProfile->displayName
            : $data->get('namePerson/friendly');

        $userProfile->displayName = $userProfile->displayName
            ? $userProfile->displayName
            : trim($userProfile->firstName . ' ' . $userProfile->lastName);

        return $userProfile;
    }

    /**
     * Extract users gender
     *
     * @param Profile $userProfile
     * @param string $gender
     *
     * @return Profile
     */
    protected function fetchUserGender(Profile $userProfile, $gender)
    {
        $gender = strtolower($gender);

        if ('f' == $gender) {
            $gender = 'female';
        }

        if ('m' == $gender) {
            $gender = 'male';
        }

        $userProfile->gender = $gender;

        return $userProfile;
    }

    /**
     * OpenID only provide the user profile one. This method will attempt to retrieve the profile from storage.
     */
    public function getUserProfile(): Profile
    {
        $userProfile = $this->storage->get($this->providerId . '.user');

        if (!is_object($userProfile)) {
            throw new UnexpectedApiResponseException('Provider returned an unexpected response.');
        }

        return $userProfile;
    }
}
