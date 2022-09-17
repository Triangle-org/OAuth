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
 * Amazon OAuth2 provider adapter.
 */
class Amazon extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'profile';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.amazon.com/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://www.amazon.com/ap/oa';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.amazon.com/auth/o2/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.amazon.com/docs/login-with-amazon/documentation-overview.html';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('user/profile');

        $data = new Collection($response);

        if (!$data->exists('user_id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new entity\Profile();

        $userProfile->identifier = $data->get('user_id');
        $userProfile->displayName = $data->get('name');
        $userProfile->email = $data->get('email');

        return $userProfile;
    }
}
