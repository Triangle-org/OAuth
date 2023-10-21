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
use Triangle\OAuth\Model\Profile;

/**
 * Medium OAuth2 provider adapter.
 */
class Medium extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'basicProfile';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.medium.com/v1/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://medium.com/m/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.medium.com/v1/tokens';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://github.com/Medium/medium-api-docs';

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
     *
     * See: https://github.com/Medium/medium-api-docs#getting-the-authenticated-users-details
     */
    public function getUserProfile(): Profile
    {
        $response = $this->apiRequest('me');

        $data = new Collection($response);

        $userProfile = new Profile();
        $data = $data->filter('data');

        $full_name = explode(' ', $data->get('name'));
        if (count($full_name) < 2) {
            $full_name[1] = '';
        }

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('username');
        $userProfile->profileURL = $data->get('imageUrl');
        $userProfile->firstName = $full_name[0];
        $userProfile->lastName = $full_name[1];
        $userProfile->profileURL = $data->get('url');

        return $userProfile;
    }
}
