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

namespace plugin\oauth\app\provider;

use plugin\oauth\app\adapter\OAuth2;
use plugin\oauth\app\exception\UnexpectedApiResponseException;

use plugin\oauth\app\entity;
use support\Collection;

/**
 * ORCID OAuth2 provider adapter.
 */
class ORCID extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = '/authenticate';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://pub.orcid.org/v2.1/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://orcid.org/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://orcid.org/oauth/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://members.orcid.org/api/';

    /**
     * {@inheritdoc}
     */
    protected function validateAccessTokenExchange($response)
    {
        $data = parent::validateAccessTokenExchange($response);
        $this->storeData('orcid', $data->get('orcid'));
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest($this->getStoredData('orcid') . '/record');
        $data = new Collection($response['record']);

        if (!$data->exists('orcid-identifier')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $profile = new entity\Profile();

        $profile = $this->getDetails($profile, $data);
        $profile = $this->getBiography($profile, $data);
        $profile = $this->getWebsite($profile, $data);
        $profile = $this->getName($profile, $data);
        $profile = $this->getEmail($profile, $data);
        $profile = $this->getLanguage($profile, $data);
        $profile = $this->getAddress($profile, $data);

        return $profile;
    }

    /**
     * Get profile details.
     *
     * @param entity\Profile $profile
     * @param Collection $data
     *
     * @return entity\Profile
     */
    protected function getDetails(entity\Profile $profile, Collection $data)
    {
        $data = new Collection($data->get('orcid-identifier'));

        $profile->identifier = $data->get('path');
        $profile->profileURL = $data->get('uri');

        return $profile;
    }

    /**
     * Get profile biography.
     *
     * @param entity\Profile $profile
     * @param Collection $data
     *
     * @return entity\Profile
     */
    protected function getBiography(entity\Profile $profile, Collection $data)
    {
        $data = new Collection($data->get('person'));
        $data = new Collection($data->get('biography'));

        $profile->description = $data->get('content');

        return $profile;
    }

    /**
     * Get profile website.
     *
     * @param entity\Profile $profile
     * @param Collection $data
     *
     * @return entity\Profile
     */
    protected function getWebsite(entity\Profile $profile, Collection $data)
    {
        $data = new Collection($data->get('person'));
        $data = new Collection($data->get('researcher-urls'));
        $data = new Collection($data->get('researcher-url'));

        if ($data->exists(0)) {
            $data = new Collection($data->get(0));
        }

        $profile->webSiteURL = $data->get('url');

        return $profile;
    }

    /**
     * Get profile name.
     *
     * @param entity\Profile $profile
     * @param Collection $data
     *
     * @return entity\Profile
     */
    protected function getName(entity\Profile $profile, Collection $data)
    {
        $data = new Collection($data->get('person'));
        $data = new Collection($data->get('name'));

        if ($data->exists('credit-name')) {
            $profile->displayName = $data->get('credit-name');
        } else {
            $profile->displayName = $data->get('given-names') . ' ' . $data->get('family-name');
        }

        $profile->firstName = $data->get('given-names');
        $profile->lastName = $data->get('family-name');

        return $profile;
    }

    /**
     * Get profile email.
     *
     * @param entity\Profile $profile
     * @param Collection $data
     *
     * @return entity\Profile
     */
    protected function getEmail(entity\Profile $profile, Collection $data)
    {
        $data = new Collection($data->get('person'));
        $data = new Collection($data->get('emails'));
        $data = new Collection($data->get('email'));

        if (!$data->exists(0)) {
            $email = $data;
        } else {
            $email = new Collection($data->get(0));

            $i = 1;
            while ($email->get('@attributes')['primary'] == 'false') {
                $email = new Collection($data->get($i));
                $i++;
            }
        }

        if ($email->get('@attributes')['primary'] == 'false') {
            return $profile;
        }

        $profile->email = $email->get('email');

        if ($email->get('@attributes')['verified'] == 'true') {
            $profile->emailVerified = $email->get('email');
        }

        return $profile;
    }

    /**
     * Get profile language.
     *
     * @param entity\Profile $profile
     * @param Collection $data
     *
     * @return entity\Profile
     */
    protected function getLanguage(entity\Profile $profile, Collection $data)
    {
        $data = new Collection($data->get('preferences'));

        $profile->language = $data->get('locale');

        return $profile;
    }

    /**
     * Get profile address.
     *
     * @param entity\Profile $profile
     * @param Collection $data
     *
     * @return entity\Profile
     */
    protected function getAddress(entity\Profile $profile, Collection $data)
    {
        $data = new Collection($data->get('person'));
        $data = new Collection($data->get('addresses'));
        $data = new Collection($data->get('address'));

        if ($data->exists(0)) {
            $data = new Collection($data->get(0));
        }

        $profile->country = $data->get('country');

        return $profile;
    }
}
