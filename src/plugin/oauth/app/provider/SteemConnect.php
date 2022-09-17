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
 * Instagram OAuth2 provider adapter.
 */
class SteemConnect extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'login,vote';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://v2.steemconnect.com/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://v2.steemconnect.com/oauth2/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://v2.steemconnect.com/api/oauth2/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://steemconnect.com/';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('api/me');

        $data = new Collection($response);

        if (!$data->exists('result')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new entity\Profile();

        $data = $data->filter('result');

        $userProfile->identifier = $data->get('id');
        $userProfile->description = $data->get('about');
        $userProfile->photoURL = $data->get('profile_image');
        $userProfile->webSiteURL = $data->get('website');
        $userProfile->displayName = $data->get('name');

        return $userProfile;
    }
}
