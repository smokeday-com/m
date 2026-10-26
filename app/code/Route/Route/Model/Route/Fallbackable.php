<?php
/**
 * A Route Magento Extension that adds secure shipping
 * insurance to your orders
 *
 * Php version 7.0^
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */

namespace Route\Route\Model\Route;

use Route\Route\Logger\Sentry;
use Route\Route\Model\Route\Operation\FallbackFactory;

/**
 * Generic Route API client
 *
 * Php version 7.0^
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
abstract class Fallbackable
{

    protected FallbackFactory $fallback;
    protected Sentry $logger;
    private bool $isRetrying = false;

    public function __construct(
        Sentry $logger,
        FallbackFactory $fallback,
    ) {
        $this->fallback = $fallback;
        $this->logger = $logger;
    }

    abstract public function enqueueOperation($object, $operation);

    public function isRetrying() : bool
    {
        return $this->isRetrying;
    }

    public function setRetrying() : void
    {
        $this->isRetrying = true;
    }
}
