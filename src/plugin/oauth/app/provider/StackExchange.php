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
 * StackExchange OAuth2 provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => '',
 *       'keys' => ['id' => '', 'secret' => ''],
 *       'site' => 'stackoverflow' // required parameter to call getUserProfile()
 *       'api_key' => '...' // that thing to receive a higher request quota.
 *   ];
 *
 *   $adapter = new plugin\oauth\app\provider\StackExchange($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *       $tokens = $adapter->getAccessToken();
 *   } catch (\Exception $e ){
 *       echo $e->getMessage() ;
 *   }
 */
class StackExchange extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = null;

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.stackexchange.com/2.2/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://stackexchange.com/oauth';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://stackexchange.com/oauth/access_token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://api.stackexchange.com/docs/authentication';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $apiKey = $this->config->get('api_key');

        $this->apiRequestParameters = ['key' => $apiKey];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $site = $this->config->get('site');

        $response = $this->apiRequest('me', 'GET', [
            'site' => $site,
            'access_token' => $this->getStoredData('access_token'),
        ]);

        if (!$response || !isset($response->items) || !isset($response->items[0])) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $data = new Collection($response->items[0]);

        $userProfile = new entity\Profile();

        $userProfile->identifier = strval($data->get('user_id'));
        $userProfile->displayName = $data->get('display_name');
        $userProfile->photoURL = $data->get('profile_image');
        $userProfile->profileURL = $data->get('link');
        $userProfile->region = $data->get('location');
        $userProfile->age = $data->get('age');

        return $userProfile;
    }
}
