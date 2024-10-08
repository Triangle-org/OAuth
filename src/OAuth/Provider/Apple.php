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

use Composer\InstalledVersions;
use Exception;
use localzet\JWT;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Math\BigInteger;
use Support\Collection;
use Triangle\Exception\HttpClientFailureException;
use Triangle\Exception\HttpRequestFailedException;
use Triangle\Exception\InvalidAccessTokenException;
use Triangle\Exception\InvalidApplicationCredentialsException;
use Triangle\OAuth\Adapter\OAuth2;
use Triangle\OAuth\Model\Profile;
use UnexpectedValueException;

/**
 * Apple OAuth2 provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => 'https:' . request()?->url(),
 *       'keys' => ['id' => '', 'team_id' => '', 'key_id' => '', 'key_file' => '', 'key_content' => ''],
 *       'scope' => 'name email',
 *
 *        // Apple's custom auth url params
 *       'authorize_url_parameters' => [
 *              'response_mode' => 'form_post'
 *       ]
 *   ];
 *
 *   $adapter = new Triangle\OAuth\Provider\Apple($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *       $tokens = $adapter->getAccessToken();
 *       $response = $adapter->setUserStatus("OAuth test message..");
 *   } catch (\Exception $e) {
 *       echo $e->getMessage() ;
 *   }
 *
 * Requires:
 *
 * composer require codercat/jwk-to-pem
 * composer require localzet/jwt
 *
 * @see https://developer.apple.com/documentation/sign_in_with_apple/sign_in_with_apple_rest_api
 */
class Apple extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'name email';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://appleid.apple.com/auth/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://appleid.apple.com/auth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://appleid.apple.com/auth/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.apple.com/documentation/sign_in_with_apple';

    /**
     * {@inheritdoc}
     * The Sign in with Apple servers require percent encoding (or URL encoding)
     * for its query parameters. If you are using the Sign in with Apple REST API,
     * you must provide values with encoded spaces (`%20`) instead of plus (`+`) signs.
     */
    protected $AuthorizeUrlParametersEncType = PHP_QUERY_RFC3986;

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();
        $this->AuthorizeUrlParameters['response_mode'] = 'form_post';

        if ($this->isRefreshTokenAvailable()) {
            $this->tokenRefreshParameters += [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ];
        }
    }

    /**
     * {@inheritdoc}
     * @throws InvalidApplicationCredentialsException
     */
    protected function configure()
    {
        $keys = $this->config->get('keys');
        $keys['secret'] = $this->getSecret();
        $this->config->set('keys', $keys);
        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * include id_token $tokenNames
     */
    public function getAccessToken()
    {
        $tokenNames = [
            'access_token',
            'id_token',
            'access_token_secret',
            'token_type',
            'refresh_token',
            'expires_in',
            'expires_at',
        ];

        $tokens = [];

        foreach ($tokenNames as $name) {
            if ($this->getStoredData($name)) {
                $tokens[$name] = $this->getStoredData($name);
            }
        }

        return $tokens;
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAccessTokenExchange($response)
    {
        $collection = parent::validateAccessTokenExchange($response);

        $this->storeData('id_token', $collection->get('id_token'));

        return $collection;
    }

    /**
     * Get the user profile
     *
     * @throws HttpClientFailureException
     * @throws InvalidAccessTokenException
     * @throws UnexpectedValueException
     * @throws HttpRequestFailedException
     * @throws Exception
     */
    public function getUserProfile()
    {
        $id_token = $this->getStoredData('id_token');

        $verifyTokenSignature =
            $this->config->exists('verifyTokenSignature') ? $this->config->get('verifyTokenSignature') : true;

        if (!$verifyTokenSignature) {
            // JWT splits the string to 3 components 1) first is header 2) is payload 3) is signature
            $payload = explode('.', $id_token)[1];
            $payload = json_decode(base64_decode($payload));
        } else {
            // validate the token signature and get the payload
            $publicKeys = $this->apiRequest('keys');

            $error = false;
            $payload = null;

            foreach ($publicKeys->keys as $publicKey) {
                try {
                    $jwk = (array)$publicKey;

                    $key = PublicKeyLoader::load(
                        [
                            'e' => new BigInteger(base64_decode($jwk['e']), 256),
                            'n' => new BigInteger(base64_decode(strtr($jwk['n'], '-_', '+/'), true), 256)
                        ]
                    )
                        ->withHash('sha1')
                        ->withMGFHash('sha1');

                    $pem = (string)$key;

                    $payload = JWT::decode($id_token, $pem, 'RS256');
                    break;
                } catch (Exception $e) {
                    $error = $e->getMessage();
                    break;
                }
            }

            if ($error && !$payload) {
                throw new Exception($error);
            }
        }

        $data = new Collection($payload);

        if (!$data->exists('sub')) {
            throw new UnexpectedValueException('Missing token payload.');
        }

        $userProfile = new Profile();
        $userProfile->identifier = $data->get('sub');
        $userProfile->email = $data->get('email');
        $this->storeData('expires_at', $data->get('exp'));

        if (request()->input('user')) {
            $objUser = json_decode(request()->input('user'));
            $user = new Collection($objUser);
            if (!$user->isEmpty()) {
                $name = $user->get('name');
                if (!empty($name->firstName)) {
                    $userProfile->firstName = $name->firstName;
                    $userProfile->lastName = $name->lastName;
                    $userProfile->displayName = join(' ', [$userProfile->firstName, $userProfile->lastName]);
                }
            }
        }

        return $userProfile;
    }

    /**
     * Get the Apple secret as a JWT token
     *
     * @return string secret token
     * @throws InvalidApplicationCredentialsException
     */
    private function getSecret()
    {
        // Your 10-character Team ID
        $team_id = $this->config->filter('keys')->get('team_id');

        if (!$team_id) {
            throw new InvalidApplicationCredentialsException(
                'Missing parameter team_id: your team id is required to generate the JWS token.'
            );
        }

        // Your Services ID, e.g. com.aaronparecki.services
        $client_id = $this->config->filter('keys')->get('id') ?: $this->config->filter('keys')->get('key');

        if (!$client_id) {
            throw new InvalidApplicationCredentialsException(
                'Missing parameter id: your client id is required to generate the JWS token.'
            );
        }

        // Find the 10-char Key ID value from the portal
        $key_id = $this->config->filter('keys')->get('key_id');

        if (!$key_id) {
            throw new InvalidApplicationCredentialsException(
                'Missing parameter key_id: your key id is required to generate the JWS token.'
            );
        }

        // Find the 10-char Key ID value from the portal
        $key_content = $this->config->filter('keys')->get('key_content');

        // Save your private key from Apple in a file called `key.txt`
        if (!$key_content) {
            $key_file = $this->config->filter('keys')->get('key_file');

            if (!$key_file) {
                throw new InvalidApplicationCredentialsException(
                    'Missing parameter key_content or key_file: your key is required to generate the JWS token.'
                );
            }

            if (!file_exists($key_file)) {
                throw new InvalidApplicationCredentialsException(
                    "Your key file $key_file does not exist."
                );
            }

            $key_content = file_get_contents($key_file);
        }

        $data = [
            'iat' => time(),
            'exp' => time() + 86400 * 180,
            'iss' => $team_id,
            'aud' => 'https://appleid.apple.com',
            'sub' => $client_id
        ];

        return JWT::encode($data, $key_content, 'ES256', $key_id);
    }

    /**
     * Try to get the installed JWT version
     *
     * If composer 2 is installed use InstalledVersions::getVersion,
     * otherwise return an empty string because no version check is available
     *
     * @return string|null
     */
    private function getJwtVersion()
    {
        // assume old JWT version if no version check is possible because composer 1 is installed
        return class_exists('Composer\InstalledVersions') ?
            InstalledVersions::getVersion('firebase/php-jwt') :
            '';
    }
}
