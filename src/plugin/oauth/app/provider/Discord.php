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
 * Discord OAuth2 provider adapter.
 */
class Discord extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'identify email';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://discordapp.com/api/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://discordapp.com/api/oauth2/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://discordapp.com/api/oauth2/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://discordapp.com/developers/docs/topics/oauth2';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        if ($this->isRefreshTokenAvailable()) {
            $this->tokenRefreshParameters += [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('users/@me');

        $data = new Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        // Makes display name more unique.
        $displayName = $data->get('username') ?: $data->get('login');
        if ($discriminator = $data->get('discriminator')) {
            $displayName .= "#{$discriminator}";
        }

        $userProfile = new entity\Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $displayName;
        $userProfile->email = $data->get('email');

        if ($data->get('verified')) {
            $userProfile->emailVerified = $data->get('email');
        }

        if ($data->get('avatar')) {
            $userProfile->photoURL = 'https://cdn.discordapp.com/avatars/';
            $userProfile->photoURL .= $data->get('id') . '/' . $data->get('avatar') . '.png';
        }

        return $userProfile;
    }
}
