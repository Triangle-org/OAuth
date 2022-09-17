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

namespace plugin\oauth\app\exception;

/**
 * OAuth Exceptions Interface
 */
interface ExceptionInterface
{
    /*
    ExceptionInterface
    Exception                                             extends \Exception implements ExceptionInterface
    |   RuntimeException                                  extends Exception
    |   |    UnexpectedValueException                     extends RuntimeException
    |   |    |    AuthorizationDeniedException            extends UnexpectedValueException
    |   |    |    HttpClientFailureException              extends UnexpectedValueException
    |   |    |    HttpRequestFailedException              extends UnexpectedValueException
    |   |    |    InvalidAuthorizationCodeException       extends UnexpectedValueException
    |   |    |    InvalidAuthorizationStateException      extends UnexpectedValueException
    |   |    |    InvalidOauthTokenException              extends UnexpectedValueException
    |   |    |    InvalidAccessTokenException             extends UnexpectedValueException
    |   |    |    UnexpectedApiResponseException          extends UnexpectedValueException
    |   |
    |   |    BadMethodCallException                       extends RuntimeException
    |   |    |   NotImplementedException                  extends BadMethodCallException
    |   |
    |   |    InvalidArgumentException                     extends RuntimeException
    |   |    |   InvalidApplicationCredentialsException   extends InvalidArgumentException
    |   |    |   InvalidOpenidIdentifierException         extends InvalidArgumentException
*/
}
