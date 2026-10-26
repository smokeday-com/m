<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-affiliate
 * @version   1.5.2
 * @copyright Copyright (C) 2024 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Affiliate\Block;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\Element\Template;
use Mirasvit\Affiliate\Model\ConfigProvider;

class Js extends Template
{
    private $urlBuilder;

    private $configProvider;

    public function __construct(
        ConfigProvider $configProvider,
        Context        $context
    ) {
        $this->configProvider = $configProvider;
        $this->urlBuilder     = $context->getUrlBuilder();

        parent::__construct($context);
    }

    public function _toHtml(): string
    {
        $baseUrl  = $this->urlBuilder->getUrl('affiliate/event/click');
        $resetUrl = $this->urlBuilder->getUrl('affiliate/cookie/reset');

        $jsName     = $this->isM245orGreater() ? 'click245' : 'click';
        $initObject = [
            'Mirasvit_Affiliate/js/' . $jsName => [
                'urlCookieReset' => $resetUrl,
                'url'            => $baseUrl,
                'param'          => $this->configProvider->getReferralParam(),
                'cookieLifetime' => $this->configProvider->getCookieLifetimeDays(),
            ],
        ];

        return '<div data-mage-init=\'' . json_encode($initObject) . '\'></div>';
    }

    public function isM245orGreater(): bool
    {
        $version = \Mirasvit\Core\Service\CompatibilityService::getVersion();

        return version_compare($version, '2.4.5', '>=');
    }
}
