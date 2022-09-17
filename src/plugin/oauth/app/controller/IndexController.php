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

namespace plugin\oauth\app\controller;

use support\Request;

class IndexController
{
    function index(Request $request)
    {
        return response("Ой, а что вы тут делаете?", 400);
    }

    function provider(Request $request, $provider = null)
    {
        if (empty($provider)) {
            return response("Ой, а что вы тут делаете?", 404);
        } else {
            // return response($provider);
        }

        $adapter = getAdapter($provider);

        $redirect = $adapter->authenticate();

        if ($redirect === true || empty($redirect)) {
            /** @var \plugin\oauth\app\entity\Profile $user */
            $user = $adapter->getUserProfile();
            $user->provider = $provider;
            return config('plugin.oauth.app.callback')($user);
        } else {
            return $redirect;
        }
    }
}
