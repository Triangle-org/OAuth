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
 * Reddit OAuth2 provider adapter.
 */
class Reddit extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'identity';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://oauth.reddit.com/api/v1/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://ssl.reddit.com/api/v1/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://ssl.reddit.com/api/v1/access_token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://github.com/reddit/reddit/wiki/OAuth2';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $this->AuthorizeUrlParameters += [
            'duration' => 'permanent'
        ];

        $this->tokenExchangeParameters = [
            'client_id' => $this->clientId,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->callback
        ];

        $this->tokenExchangeHeaders = [
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
        ];

        $this->tokenRefreshHeaders = $this->tokenExchangeHeaders;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('me.json');

        $data = new Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new entity\Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('name');
        $userProfile->profileURL = 'https://www.reddit.com/user/' . $data->get('name') . '/';
        $userProfile->photoURL = $data->get('icon_img');

        return $userProfile;
    }
}
