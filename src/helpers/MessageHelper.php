<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\helpers;

use craft\helpers\StringHelper;
use SAML2\Message as SamlMessage;
use SAML2\Request as SamlRequest;
use SAML2\StatusResponse as SamlResponse;
use SAML2\XML\saml\Issuer;

/**
 * Class MessageHelper
 * @package flipbox\saml\core\helpers
 */
class MessageHelper
{
    const REQUEST_PARAMETER = 'SAMLRequest';
    const RESPONSE_PARAMETER = 'SAMLResponse';
    const ID_LENGTH = 43;

    public static function generateId()
    {
        return '_'.bin2hex(openssl_random_pseudo_bytes((int) ((self::ID_LENGTH - 1) / 2)));
    }

    /**
     * @param SamlMessage $message
     * @return bool
     */
    public static function isResponse(SamlMessage $message)
    {
        return $message instanceof SamlResponse;
    }

    /**
     * @param SamlMessage $message
     * @return bool
     */
    public static function isRequest(SamlMessage $message)
    {
        return $message instanceof SamlRequest;
    }

    /**
     * @param SamlMessage $message
     * @return string
     */
    public static function getParameterKeyByMessage(SamlMessage $message)
    {
        return static::isRequest($message) ? static::REQUEST_PARAMETER : static::RESPONSE_PARAMETER;
    }

    /**
     * @param $issuer
     * @return string|null
     */
    public static function getIssuer($issuer)
    {
        if ($issuer instanceof Issuer) {
            return $issuer->getValue();
        }

        return $issuer;
    }
}
