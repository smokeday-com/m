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
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Service\AnalyticsService;

class Top extends Template
{
    private $accountRepository;

    private $analyticsService;

    public function __construct(
        AccountRepository $accountRepository,
        AnalyticsService $analyticsService,
        Context $context
    ) {
        $this->accountRepository = $accountRepository;
        $this->analyticsService  = $analyticsService;

        parent::__construct($context);
    }

    /**
     * @return AccountInterface[]
     */
    public function getList(): array
    {
        $collection = $this->accountRepository->getCollection();

        $collection->setOrder(AccountInterface::TOTAL_COMMISSIONS, 'desc')
            ->setPageSize(10);

        return $collection->getItems();
    }

    public function getClicks(AccountInterface $account): int
    {
        return $this->analyticsService->getAccountClicks($account);
    }
}
