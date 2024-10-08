<?php declare(strict_types=1);

/**
 * @package     Localzet OAuth
 * @link        https://github.com/localzet/OAuth
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2018-2024 Localzet Group
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License v3.0
 *
 *              This program is free software: you can redistribute it and/or modify
 *              it under the terms of the GNU Affero General Public License as published
 *              by the Free Software Foundation, either version 3 of the License, or
 *              (at your option) any later version.
 *
 *              This program is distributed in the hope that it will be useful,
 *              but WITHOUT ANY WARRANTY; without even the implied warranty of
 *              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *              GNU Affero General Public License for more details.
 *
 *              You should have received a copy of the GNU Affero General Public License
 *              along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 *              For any questions, please contact <creator@localzet.com>
 */

namespace Triangle\OAuth\Provider;

use Support\Collection;
use Throwable;
use Triangle\Exception\InvalidApplicationCredentialsException;
use Triangle\Exception\InvalidAuthorizationCodeException;
use Triangle\Exception\UnexpectedApiResponseException;
use Triangle\OAuth\Adapter\AbstractAdapter;
use Triangle\OAuth\Adapter\AdapterInterface;
use Triangle\OAuth\Model\Profile;

/**
 * Telegram provider adapter.
 *
 * To set up Telegram you need to interactively create a bot using the
 * Telegram mobile app, talking to botfather. The minimum conversation
 * will look like:
 *
 * /newbot
 * My Bot Title
 * nameofmynewbot
 * /setdomain
 * @nameofmynewbot
 * mydomain.com
 *
 * Example:
 *
 *   $config = [
 *       'callback' => 'https:' . request()?->url(),
 *       'keys' => ['id' => 'your_bot_name', 'secret' => 'your_bot_token'],
 *   ];
 *
 *   $adapter = new Triangle\OAuth\Provider\Telegram($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *   } catch (\Exception $e) {
 *       print $e->getMessage();
 *   }
 */
class Telegram extends AbstractAdapter implements AdapterInterface
{
    protected $botId = '';

    protected $botSecret = '';

    protected $callbackUrl = '';

    /**
     * IPD API Documentation
     *
     * OPTIONAL.
     *
     * @var string
     */
    protected $apiDocumentation = 'https://core.telegram.org/bots';

    /**
     * {@inheritdoc}
     * @throws InvalidApplicationCredentialsException
     */
    protected function configure()
    {
        $this->botId = $this->config->filter('keys')->get('id');
        $this->botSecret = $this->config->filter('keys')->get('secret');
        $this->callbackUrl = $this->config->get('callback');

        if (!$this->botId || !$this->botSecret) {
            throw new InvalidApplicationCredentialsException(
                'Your application id is required in order to connect to ' . $this->providerId
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
    }

    /**
     * {@inheritdoc}
     * @throws InvalidAuthorizationCodeException
     */
    public function authenticate()
    {
        $this->logger->info(sprintf('%s::authenticate()', get_class($this)));
        if (!request()->get('hash')) {
            return $this->authenticateBegin();
        } else {
            $this->authenticateCheckError();
            $this->authenticateFinish();
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        $authData = $this->getStoredData('auth_data');
        return !empty($authData);
    }

    /**
     * {@inheritdoc}
     * @throws UnexpectedApiResponseException
     */
    public function getUserProfile()
    {
        $data = new Collection($this->getStoredData('auth_data'));

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->firstName = $data->get('first_name');
        $userProfile->lastName = $data->get('last_name');
        $userProfile->displayName = $data->get('username');
        $userProfile->photoURL = $data->get('photo_url');
        $username = $data->get('username');
        if (!empty($username)) {
            // Only some accounts have usernames.
            $userProfile->profileURL = "https://t.me/{$username}";
        }

        return $userProfile;
    }

    /**
     * See: https://telegram.im/widget-login.php
     * See: https://gist.github.com/anonymous/6516521b1fb3b464534fbc30ea3573c2
     * @throws InvalidAuthorizationCodeException
     * @throws InvalidAuthorizationCodeException
     */
    protected function authenticateCheckError()
    {
        $auth_data = $this->parseAuthData();

        $check_hash = $auth_data['hash'];
        unset($auth_data['hash']);
        $data_check_arr = [];

        foreach ($auth_data as $key => $value) {
            if (!empty($value)) {
                $data_check_arr[] = $key . '=' . $value;
            }
        }
        sort($data_check_arr);

        $data_check_string = implode("\n", $data_check_arr);
        $secret_key = hash('sha256', $this->botSecret, true);
        $hash = hash_hmac('sha256', $data_check_string, $secret_key);

        if (strcmp($hash, $check_hash) !== 0) {
            throw new InvalidAuthorizationCodeException(
                sprintf('Provider returned an error: %s', 'Data is NOT from Telegram')
            );
        }

        if ((time() - $auth_data['auth_date']) > 86400) {
            throw new InvalidAuthorizationCodeException(
                sprintf('Provider returned an error: %s', 'Data is outdated')
            );
        }
    }

    /**
     * See: https://telegram.im/widget-login.php
     * @throws Throwable
     */
    protected function authenticateBegin()
    {
        $this->logger->debug(sprintf('%s::authenticateBegin(), redirecting user to:', get_class($this)));

        $nonce = $this->config->get('nonce');
        $nonce_code = empty($nonce) ? '' : "nonce=\"{$nonce}\"";

        return response(
            <<<HTML
            <center>
                <script async src="https://telegram.org/js/telegram-widget.js?7"
                        {$nonce_code}
                        data-telegram-login="{$this->botId}"
                        data-size="large"
                        data-auth-url="{$this->callbackUrl}"
                        data-request-access="write">
                </script>
            </center>
            HTML
        );
    }

    protected function authenticateFinish()
    {
        $this->logger->debug(
            sprintf('%s::authenticateFinish(), callback url:', get_class($this)),
            [request()?->fullUrl()]
        );

        $this->storeData('auth_data', $this->parseAuthData());

        $this->initialize();
    }

    protected function parseAuthData()
    {
        return [
            'id' => request()->get('id'),
            'first_name' => request()->get('first_name'),
            'last_name' => request()->get('last_name'),
            'username' => request()->get('username'),
            'photo_url' => request()->get('photo_url'),
            'auth_date' => request()->get('auth_date'),
            'hash' => request()->get('hash'),
        ];
    }
}
