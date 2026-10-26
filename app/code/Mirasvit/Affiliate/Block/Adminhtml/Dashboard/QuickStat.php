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

namespace Mirasvit\Affiliate\Block\Adminhtml\Dashboard;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mirasvit\Affiliate\Service\AnalyticsService;

class QuickStat extends Template
{
    private $analyticsService;

    public function __construct(
        AnalyticsService $analyticsService,
        Context $context
    ) {
        $this->analyticsService = $analyticsService;

        parent::__construct($context);
    }

    public function getActiveAffiliates(): int
    {
        return $this->analyticsService->getActiveAccounts(date('Y-m-d H:i:s', time() - 7 * 24 * 60 * 60));
    }

    public function getTotalAffiliates(): int
    {
        return $this->analyticsService->getTotalAffiliates();
    }

    public function getVisits(): int
    {
        return $this->analyticsService->getVisits(date('Y-m-d H:i:s', time() - 7 * 24 * 60 * 60));
    }

    public function getRevenue(): float
    {
        return $this->analyticsService->getRevenue(date('Y-m-d H:i:s', time() - 7 * 24 * 60 * 60));
    }

    public function getTotalRevenue(): float
    {
        return $this->analyticsService->getRevenue();
    }

    public function getCommission(): float
    {
        return $this->analyticsService->getCommissions(date('Y-m-d H:i:s', time() - 7 * 24 * 60 * 60));
    }

    public function getTotalCommission(): float
    {
        return $this->analyticsService->getCommissions();
    }

    public function getHoldedCommission(): float
    {
        return $this->analyticsService->getHoldedComissions();
    }

    public function getTotalWithdrawals(): float
    {
        return $this->analyticsService->getTotalWithdrawals();
    }
}
