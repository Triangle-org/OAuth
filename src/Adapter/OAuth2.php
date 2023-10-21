<?php

/**
 * @package     Triangle OAuth Plugin
 * @link        https://github.com/Triangle-org/OAuth
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2018-2023 Localzet Group
 * @license     GNU Affero General Public License, version 3
 *
 *              This program is free software: you can redistribute it and/or modify
 *              it under the terms of the GNU Affero General Public License as
 *              published by the Free Software Foundation, either version 3 of the
 *              License, or (at your option) any later version.
 *
 *              This program is distributed in the hope that it will be useful,
 *              but WITHOUT ANY WARRANTY; without even the implied warranty of
 *              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *              GNU Affero General Public License for more details.
 *
 *              You should have received a copy of the GNU Affero General Public License
 *              along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Triangle\OAuth\Adapter;

use Exception;
use support\Collection;
use support\Parser;
use Triangle\OAuth\Exception\AuthorizationDeniedException;
use Triangle\OAuth\Exception\HttpClientFailureException;
use Triangle\OAuth\Exception\HttpRequestFailedException;
use Triangle\OAuth\Exception\InvalidAccessTokenException;
use Triangle\OAuth\Exception\InvalidApplicationCredentialsException;
use Triangle\OAuth\Exception\InvalidAuthorizationCodeException;
use Triangle\OAuth\Exception\InvalidAuthorizationStateException;

/**
 * This class  can be used to simplify the authorization flow of OAuth 2 based service providers.
 *
 * Subclasses (i.e., providers adapters) can either use the already provided methods or override
 * them when necessary.
 */
class OAuth2 extends AbstractAdapter implements AdapterInterface
{
    /**
     * Client Identifier
     *
     * RFC6749: client_id REQUIRED. The client identifier issued to the client during
     * the registration process described by Section 2.2.
     *
     * http://tools.ietf.org/html/rfc6749#section-2.2
     *
     * @var string
     */
    protected $clientId = '';

    /**
     * Client Secret
     *
     * RFC6749: client_secret REQUIRED. The client secret. The client MAY omit the
     * parameter if the client secret is an empty string.
     *
     * http://tools.ietf.org/html/rfc6749#section-2.2
     *
     * @var string
     */
    protected $clientSecret = '';

    /**
     * Access Token Scope
     *
     * RFC6749: The authorization and token endpoints allow the client to specify the
     * scope of the access request using the "scope" request parameter.
     *
     * http://tools.ietf.org/html/rfc6749#section-3.3
     *
     * @var string
     */
    protected $scope = '';

    /**
     * Base URL to provider API
     *
     * This var will be used to build urls when sending signed requests
     *
     * @var string
     */
    protected $apiBaseUrl = '';

    /**
     * Authorization Endpoint
     *
     * RFC6749: The authorization endpoint is used to interact with the resource
     * owner and obtain an authorization grant.
     *
     * http://tools.ietf.org/html/rfc6749#section-3.1
     *
     * @var string
     */
    protected $authorizeUrl = '';

    /**
     * Access Token Endpoint
     *
     * RFC6749: The token endpoint is used by the client to obtain an access token by
     * presenting its authorization grant or refresh token.
     *
     * http://tools.ietf.org/html/rfc6749#section-3.2
     *
     * @var string
     */
    protected $accessTokenUrl = '';

    /**
     * TokenInfo endpoint
     *
     * Access token validation. OPTIONAL.
     *
     * @var string
     */
    protected $accessTokenInfoUrl = '';

    /**
     * IPD API Documentation
     *
     * OPTIONAL.
     *
     * @var string
     */
    protected $apiDocumentation = '';

    /**
     * Redirection Endpoint or Callback
     *
     * RFC6749: After completing its interaction with the resource owner, the
     * authorization server directs the resource owner's user-agent back to
     * the client.
     *
     * http://tools.ietf.org/html/rfc6749#section-3.1.2
     *
     * @var string
     */
    protected $callback = '';

    /**
     * Authorization Url Parameters
     *
     * @var array
     */
    protected $AuthorizeUrlParameters = [];


    /**
     * Authorization Url Parameter encoding type
     * @see https://www.php.net/manual/de/function.http-build-query.php
     *
     * @var string
     */
    protected $AuthorizeUrlParametersEncType = PHP_QUERY_RFC1738;

    /**
     * Authorization Request State
     *
     * @var bool
     */
    protected $supportRequestState = true;

    /**
     * Access Token name
     *
     * While most providers will use 'access_token' as name for the Access Token attribute, other do not.
     * On the latter case, this should be set by sub classes.
     *
     * @var string
     */
    protected $accessTokenName = 'access_token';

    /**
     * Authorization Request HTTP method.
     *
     * @see exchangeCodeForAccessToken()
     *
     * @var string
     */
    protected $tokenExchangeMethod = 'POST';

    /**
     * Authorization Request URL parameters.
     *
     * Sub classes may change add any additional parameter when necessary.
     *
     * @see exchangeCodeForAccessToken()
     *
     * @var array
     */
    protected $tokenExchangeParameters = [];

    /**
     * Authorization Request HTTP headers.
     *
     * Sub classes may add any additional header when necessary.
     *
     * @see exchangeCodeForAccessToken()
     *
     * @var array
     */
    protected $tokenExchangeHeaders = [];

    /**
     * Refresh Token Request HTTP method.
     *
     * @see refreshAccessToken()
     *
     * @var string
     */
    protected $tokenRefreshMethod = 'POST';

    /**
     * Refresh Token Request URL parameters.
     *
     * Sub classes may change add any additional parameter when necessary.
     *
     * @see refreshAccessToken()
     *
     * @var array|null
     */
    protected $tokenRefreshParameters = null;

    /**
     * Refresh Token Request HTTP headers.
     *
     * Sub classes may add any additional header when necessary.
     *
     * @see refreshAccessToken()
     *
     * @var array
     */
    protected $tokenRefreshHeaders = [];

    /**
     * Authorization Request URL parameters.
     *
     * Sub classes may change add any additional parameter when necessary.
     *
     * @see apiRequest()
     *
     * @var array
     */
    protected $apiRequestParameters = [];

    /**
     * Authorization Request HTTP headers.
     *
     * Sub classes may add any additional header when necessary.
     *
     * @see apiRequest()
     *
     * @var array
     */
    protected $apiRequestHeaders = [];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->clientId = $this->config->filter('keys')->get('id') ?: $this->config->filter('keys')->get('key');
        $this->clientSecret = $this->config->filter('keys')->get('secret');

        if (!$this->clientId || !$this->clientSecret) {
            throw new InvalidApplicationCredentialsException(
                'Ваш идентификатор приложения требуется для подключения к ' . $this->providerId
            );
        }

        $this->scope = $this->config->exists('scope') ? $this->config->get('scope') : $this->scope;

        if ($this->config->exists('tokens')) {
            $this->setAccessToken($this->config->get('tokens'));
        }

        $this->setCallback($this->config->get('callback_uri'));
        $this->setApiEndpoints($this->config->get('endpoints'));
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        $this->AuthorizeUrlParameters = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->callback,
            'scope' => $this->scope,
        ];

        $this->tokenExchangeParameters = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->callback
        ];

        $refreshToken = $this->getStoredData('refresh_token');
        if (!empty($refreshToken)) {
            $this->tokenRefreshParameters = [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ];
        }

        $this->apiRequestHeaders = [
            'Authorization' => 'Bearer ' . $this->getStoredData('access_token')
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(): ?bool
    {
        $this->logger->info(sprintf('%s::authenticate()', get_class($this)));

        if ($this->isConnected()) {
            return true;
        }

        try {
            $this->authenticateCheckError();

            $code = request()->input('code');

            if (empty($code)) {
                return $this->authenticateBegin();
            } else {
                $this->authenticateFinish();
            }
        } catch (Exception $e) {
            $this->clearStoredData();

            throw $e;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected(): bool
    {
        if ((bool)$this->getStoredData('access_token')) {
            return (!$this->hasAccessTokenExpired() || $this->isRefreshTokenAvailable());
        }
        return false;
    }

    /**
     * If we can use a refresh token, then an expired token does not stop us being connected.
     *
     * @return bool
     */
    public function isRefreshTokenAvailable()
    {
        return is_array($this->tokenRefreshParameters);
    }

    /**
     * Authorization Request Error Response
     *
     * RFC6749: If the request fails due to a missing, invalid, or mismatching
     * redirection URI, or if the client identifier is missing or invalid,
     * the authorization server SHOULD inform the resource owner of the error.
     *
     * http://tools.ietf.org/html/rfc6749#section-4.1.2.1
     *
     * @throws InvalidAuthorizationCodeException
     * @throws AuthorizationDeniedException
     */
    protected function authenticateCheckError()
    {
        // $error = filter_input(INPUT_GET, 'error', FILTER_SANITIZE_SPECIAL_CHARS);
        $error = !empty(request()->get('error', false)) && is_string(request()->get('error', false)) ? request()->get('error', false) : '';

        if (!empty($error)) {
            $error_description = !empty(request()->get('error_description', false)) && is_string(request()->get('error_description', false)) ? request()->get('error_description', false) : '';
            $error_uri = !empty(request()->get('error_uri', false)) && is_string(request()->get('error_uri', false)) ? request()->get('error_uri', false) : '';
            // $error_uri = filter_input(INPUT_GET, 'error_uri', FILTER_SANITIZE_SPECIAL_CHARS);

            $collated_error = sprintf('Провайдер вернул ошибку: %s %s %s', $error, $error_description, $error_uri);

            if ($error == 'access_denied') {
                throw new AuthorizationDeniedException($collated_error);
            }

            throw new InvalidAuthorizationCodeException($collated_error);
        }
    }

    /**
     * Initiate the authorization protocol
     *
     * Build Authorization URL for Authorization Request and redirect the user-agent to the
     * Authorization Server.
     */
    protected function authenticateBegin()
    {
        $authUrl = $this->getAuthorizeUrl();

        $this->logger->debug(sprintf('%s::authenticateBegin(), redirecting user to:', get_class($this)), [$authUrl]);

        return redirect($authUrl);
    }

    /**
     * Finalize the authorization process
     *
     * @throws HttpClientFailureException
     * @throws HttpRequestFailedException
     * @throws InvalidAccessTokenException
     * @throws InvalidAuthorizationStateException
     */
    protected function authenticateFinish()
    {
        $this->logger->debug(sprintf('%s::authenticateFinish(), callback url:', get_class($this)));

        $state = request()->input('state');
        $code = request()->input('code');

        /**
         * Authorization Request State
         *
         * RFC6749: state : RECOMMENDED. An opaque value used by the client to maintain
         * state between the request and callback. The authorization server includes
         * this value when redirecting the user-agent back to the client.
         *
         * http://tools.ietf.org/html/rfc6749#section-4.1.1
         */
        if (
            $this->supportRequestState
            && $this->getStoredData('authorization_state') != $state
        ) {
            throw new InvalidAuthorizationStateException(
                'Состояние авторизации [state=' . substr(htmlentities($state), 0, 100) . '] '
                . 'либо недействительно, либо уже было использовано'
            );
        }

        /**
         * Authorization Request Code
         *
         * RFC6749: If the resource owner grants the access request, the authorization
         * server issues an authorization code and delivers it to the client:
         *
         * http://tools.ietf.org/html/rfc6749#section-4.1.2
         */
        $response = $this->exchangeCodeForAccessToken($code);

        $this->validateAccessTokenExchange($response);

        $this->initialize();
    }

    /**
     * Build Authorization URL for Authorization Request
     *
     * RFC6749: The client constructs the request URI by adding the following
     * $parameters to the query component of the authorization endpoint URI:
     *
     *    - response_type  REQUIRED. Value MUST be set to "code".
     *    - client_id      REQUIRED.
     *    - redirect_uri   OPTIONAL.
     *    - scope          OPTIONAL.
     *    - state          RECOMMENDED.
     *
     * http://tools.ietf.org/html/rfc6749#section-4.1.1
     *
     * Sub classes may redefine this method when necessary.
     *
     * @param array $parameters
     *
     * @return string Authorization URL
     * @throws Exception
     */
    protected function getAuthorizeUrl($parameters = [])
    {
        $this->AuthorizeUrlParameters = !empty($parameters)
            ? $parameters
            : array_replace(
                $this->AuthorizeUrlParameters,
                (array)$this->config->get('authorize_url_parameters')
            );

        if ($this->supportRequestState) {
            if (!isset($this->AuthorizeUrlParameters['state'])) {
                $this->AuthorizeUrlParameters['state'] = 'HA-' . str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');
            }

            $this->storeData('authorization_state', $this->AuthorizeUrlParameters['state']);
        }

        $queryParams = http_build_query($this->AuthorizeUrlParameters, '', '&', $this->AuthorizeUrlParametersEncType);
        return $this->authorizeUrl . '?' . $queryParams;
    }

    /**
     * Access Token Request
     *
     * This method will exchange the received $code in loginFinish() with an Access Token.
     *
     * RFC6749: The client makes a request to the token endpoint by sending the
     * following parameters using the "application/x-www-form-urlencoded"
     * with a character encoding of UTF-8 in the HTTP request entity-body:
     *
     *    - grant_type    REQUIRED. Value MUST be set to "authorization_code".
     *    - code          REQUIRED. The authorization code received from the authorization server.
     *    - redirect_uri  REQUIRED.
     *    - client_id     REQUIRED.
     *
     * http://tools.ietf.org/html/rfc6749#section-4.1.3
     *
     * @param string $code
     *
     * @return string Raw Provider API response
     * @throws HttpClientFailureException
     * @throws HttpRequestFailedException
     */
    protected function exchangeCodeForAccessToken($code)
    {
        $this->tokenExchangeParameters['code'] = $code;

        $response = $this->httpClient->request(
            $this->accessTokenUrl,
            $this->tokenExchangeMethod,
            $this->tokenExchangeParameters,
            $this->tokenExchangeHeaders
        );


        $this->validateApiResponse('Unable to exchange code for API access token');

        return $response;
    }

    /**
     * Validate Access Token Response
     *
     * RFC6749: If the access token request is valid and authorized, the
     * authorization server issues an access token and optional refresh token.
     * If the request client authentication failed or is invalid, the authorization
     * server returns an error response as described in Section 5.2.
     *
     * Example of a successful response:
     *
     *  HTTP/1.1 200 OK
     *  Content-Type: application/json;charset=UTF-8
     *  Cache-Control: no-store
     *  Pragma: no-cache
     *
     *  {
     *      "access_token":"2YotnFZFEjr1zCsicMWpAA",
     *      "token_type":"example",
     *      "expires_in":3600,
     *      "refresh_token":"tGzv3JOkF0XG5Qx2TlKWIA",
     *      "example_parameter":"example_value"
     *  }
     *
     * http://tools.ietf.org/html/rfc6749#section-4.1.4
     *
     * This method uses Data_Parser to attempt to decodes the raw $response (usually JSON)
     * into a data collection.
     *
     * @param string $response
     *
     * @return Collection
     * @throws InvalidAccessTokenException
     */
    protected function validateAccessTokenExchange($response)
    {
        $data = (new Parser())->parse($response);

        $collection = new Collection($data);

        if (!$collection->exists('access_token')) {
            throw new InvalidAccessTokenException(
                'Провайдер вернул не OAuth_token: ' . htmlentities($response)
            );
        }

        $this->storeData('access_token', $collection->get('access_token'));
        $this->storeData('token_type', $collection->get('token_type'));

        if ($collection->get('refresh_token')) {
            $this->storeData('refresh_token', $collection->get('refresh_token'));
        }

        // calculate when the access token expire
        if ($collection->exists('expires_in')) {
            $expires_at = time() + (int)$collection->get('expires_in');

            $this->storeData('expires_in', $collection->get('expires_in'));
            $this->storeData('expires_at', $expires_at);
        }

        $this->deleteStoredData('authorization_state');

        $this->initialize();

        return $collection;
    }

    /**
     * Refreshing an Access Token
     *
     * RFC6749: If the authorization server issued a refresh token to the
     * client, the client makes a refresh request to the token endpoint by
     * adding the following parameters ... in the HTTP request entity-body:
     *
     *    - grant_type     REQUIRED. Value MUST be set to "refresh_token".
     *    - refresh_token  REQUIRED. The refresh token issued to the client.
     *    - scope          OPTIONAL.
     *
     * http://tools.ietf.org/html/rfc6749#section-6
     *
     * This method is similar to exchangeCodeForAccessToken(). The only
     * difference is here we exchange refresh_token for a new access_token.
     *
     * @param array $parameters
     *
     * @return string|null Raw Provider API response, or null if we cannot refresh
     * @throws HttpClientFailureException
     * @throws HttpRequestFailedException
     * @throws InvalidAccessTokenException
     */
    public function refreshAccessToken($parameters = [])
    {
        $this->tokenRefreshParameters = !empty($parameters)
            ? $parameters
            : $this->tokenRefreshParameters;

        if (!$this->isRefreshTokenAvailable()) {
            return null;
        }

        $response = $this->httpClient->request(
            $this->accessTokenUrl,
            $this->tokenRefreshMethod,
            $this->tokenRefreshParameters,
            $this->tokenRefreshHeaders
        );

        $this->validateApiResponse('Unable to refresh the access token');

        $this->validateRefreshAccessToken($response);

        return $response;
    }

    /**
     * Check whether access token has expired
     *
     * @param null $time
     * @return bool|null
     * @throws Exception
     */
    public function hasAccessTokenExpired($time = null)
    {
        if ($time === null) {
            $time = time();
        }

        $expires_at = $this->getStoredData('expires_at');
        if (!$expires_at) {
            return null;
        }

        return $expires_at <= $time;
    }

    /**
     * Validate Refresh Access Token Request
     *
     * RFC6749: If valid and authorized, the authorization server issues an
     * access token as described in Section 5.1.  If the request failed
     * verification or is invalid, the authorization server returns an error
     * response as described in Section 5.2.
     *
     * http://tools.ietf.org/html/rfc6749#section-6
     * http://tools.ietf.org/html/rfc6749#section-5.1
     * http://tools.ietf.org/html/rfc6749#section-5.2
     *
     * This method simply use validateAccessTokenExchange(), however sub
     * classes may redefine it when necessary.
     *
     * @param $response
     *
     * @return Collection
     * @throws InvalidAccessTokenException
     */
    protected function validateRefreshAccessToken($response)
    {
        return $this->validateAccessTokenExchange($response);
    }

    /**
     * Send a signed request to provider API
     *
     * RFC6749: Accessing Protected Resources: The client accesses protected
     * resources by presenting the access token to the resource server. The
     * resource server MUST validate the access token and ensure that it has
     * not expired and that its scope covers the requested resource.
     *
     * Note: Since the specifics of error responses is beyond the scope of
     * RFC6749 and OAuth specifications, OAuth will consider any HTTP
     * status code that is different than '200 OK' as an ERROR.
     *
     * http://tools.ietf.org/html/rfc6749#section-7
     *
     * @param string $url
     * @param string $method
     * @param array $parameters
     * @param array $headers
     * @param bool $multipart
     *
     * @return mixed
     * @throws HttpClientFailureException
     * @throws HttpRequestFailedException
     * @throws InvalidAccessTokenException
     */
    public function apiRequest(string $url, string $method = 'GET', array $parameters = [], array $headers = [], bool $multipart = false): mixed
    {
        // refresh tokens if needed
        $this->maintainToken();
        if ($this->hasAccessTokenExpired() === true) {
            $this->refreshAccessToken();
        }

        if (strrpos($url, 'http://') !== 0 && strrpos($url, 'https://') !== 0) {
            $url = rtrim($this->apiBaseUrl, '/') . '/' . ltrim($url, '/');
        }

        $parameters = array_replace($this->apiRequestParameters, $parameters);
        $headers = array_replace($this->apiRequestHeaders, $headers);

        $response = $this->httpClient->request(
            $url,
            $method,     // HTTP Request Method. Defaults to GET.
            $parameters, // Request Parameters
            $headers,    // Request Headers
            $multipart   // Is request multipart
        );

        $this->validateApiResponse('Signed API request to ' . $url . ' has returned an error');

        $response = (new Parser())->parse($response);

        return $response;
    }
}
