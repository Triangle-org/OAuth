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

namespace plugin\oauth\app\entity;

use plugin\oauth\app\exception\UnexpectedValueException;

/**
 * plugin\oauth\app\entity\Contact
 */
final class Contact
{
    /**
     * The Unique contact user ID
     *
     * @var string
     */
    public $identifier = null;

    /**
     * User website, blog, web page
     *
     * @var string
     */
    public $webSiteURL = null;

    /**
     * URL link to profile page on the IDp web site
     *
     * @var string
     */
    public $profileURL = null;

    /**
     * URL link to user photo or avatar
     *
     * @var string
     */
    public $photoURL = null;

    /**
     * User displayName provided by the IDp or a concatenation of first and last name
     *
     * @var string
     */
    public $displayName = null;

    /**
     * A short about_me
     *
     * @var string
     */
    public $description = null;

    /**
     * User email. Not all of IDp grant access to the user email
     *
     * @var string
     */
    public $email = null;

    /**
     * Prevent the providers adapters from adding new fields.
     *
     * @param string $name
     * @param mixed $value
     *
     * @throws UnexpectedValueException
     */
    public function __set($name, $value)
    {
        throw new UnexpectedValueException(sprintf('Добавление нового свойства "%s" в %s не поддерживается.', $name, __CLASS__));
    }
}
