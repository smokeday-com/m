<?php
/**
 * ToggleState
 *
 * PHP version 8
 *
 * @category Class
 * @package  Route\Route\Service
 * @author   Route Protect team
 * @link     https://docs.route.com/docs/
 */

/**
 * Protect
 *
 * The Protect provides endpoints to maintain coverage data and also to provide a quote for a pre-order and a policy during order creation or update.
 *
 * @category Class
 * @package  Route\Route\Service
 * @author   Route Protect team
 * @link     https://docs.route.com/docs/ */


namespace Route\Route\Service\Model;

use \Route\Route\Service\ObjectSerializer;

/**
 * ToggleState Class Doc Comment
 *
 * @category Class
 * @package  Route\Route\Service
 * @author   Route Protect team
 * @link     https://docs.route.com/docs/
 */
class ToggleState
{
    /**
     * Possible values of this enum
     */
    const CHECKED = 'checked';
    const UNCHECKED = 'unchecked';
    /**
     * Gets allowable values of the enum
     * @return string[]
     */
    public static function getAllowableEnumValues()
    {
        return [
            self::CHECKED,
            self::UNCHECKED
        ];
    }
}
