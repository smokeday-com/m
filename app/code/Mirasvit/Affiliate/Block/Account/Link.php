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

namespace Mirasvit\Affiliate\Block\Account;

use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\View\Element\Html\Link\Current as CurrentLink;
use Magento\Framework\View\Element\Template\Context;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Service\SessionService;

class Link extends CurrentLink
{
    private $configProvider;

    private $sessionService;

    public function __construct(
        SessionService $sessionService,
        ConfigProvider $configProvider,
        Context $context,
        DefaultPathInterface $defaultPath,
        array $data = []
    ) {
        $this->configProvider = $configProvider;
        $this->sessionService = $sessionService;

        parent::__construct($context, $defaultPath, $data);
    }

    protected function _toHtml(): string
    {
        $isAffiliate = $this->sessionService->getAccount() ? true : false;

        if (!$this->configProvider->isEnabled()) {
            return '';
        }

        if (!$isAffiliate && $this->configProvider->isShowLinkInAccount()) {
            return '';
        }

        return parent::_toHtml();
    }
}
