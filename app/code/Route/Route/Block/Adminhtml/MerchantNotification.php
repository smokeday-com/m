<?php
/**
 * A Route Magento Extension that adds secure shipping
 * insurance to your orders
 *
 * Php version 7.0^
 *
 * @category
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
namespace Route\Route\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\UrlInterface;
use Route\Route\Helper\Setup;

/**
 *
 * Php version 7.0^
 *
 * @category
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class MerchantNotification extends \Magento\Backend\Block\Template
{
    private $setupHelper;
    private $backendUrl;

    /**
     * Notification constructor.
     *
     * @param Context $context
     * @param Setup $setupHelper
     * @param UrlInterface $backendUrl
     * @param array $data
     */
    public function __construct(
        Context $context,
        Setup $setupHelper,
        UrlInterface $backendUrl,
        array $data = []
    ) {
        $this->setupHelper = $setupHelper;
        $this->backendUrl = $backendUrl;
        parent::__construct($context, $data);
    }

    protected function _toHtml()
    {
        if ($this->setupHelper->hasMerchantCreationFailed() && $this->setupHelper->isRouteModuleEnable()) {
            return parent::_toHtml();
        }
    }

    public function tryRecreateMerchant()
    {
        return $this->backendUrl->getUrl('route/index/merchant');
    }
}
