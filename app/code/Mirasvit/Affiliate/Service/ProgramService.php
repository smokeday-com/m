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

namespace Mirasvit\Affiliate\Service;

use Magento\Framework\Data\Collection;
use Magento\SalesRule\Api\Data\RuleInterface as SalesRuleInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface as SalesRuleRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\EventInterface;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Model\ResourceModel\Program\Collection as ProgramCollection;
use Mirasvit\Affiliate\Repository\ProgramRepository;

class ProgramService
{
    private $salesRuleRepository;

    private $storeManager;

    private $programRepository;

    public function __construct(
        ProgramRepository            $programRepository,
        SalesRuleRepositoryInterface $salesRuleRepository,
        StoreManagerInterface        $storeManager
    ) {
        $this->programRepository   = $programRepository;
        $this->salesRuleRepository = $salesRuleRepository;
        $this->storeManager        = $storeManager;
    }

    public function verifyProgram(ProgramInterface $program): bool
    {
        try {
            if ($program->getType() != ProgramInterface::TYPE_PAY_PER_SALE) {
                return true;
            }
            $salesRule = $this->salesRuleRepository->getById($program->getCartRuleId());

            return $this->checkBelongingSameSite($salesRule, $program);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function checkBelongingSameSite(SalesRuleInterface $salesRule, ProgramInterface $program): bool
    {
        if (in_array(0, $program->getStoreIds())) {
            return true;
        }

        foreach ($program->getStoreIds() as $storeId) {
            $programWebsiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
            if (in_array($programWebsiteId, $salesRule->getWebsiteIds())) {
                return true;
            }
        }

        return false;
    }

    public function isApplicableForAccount(ProgramInterface $program, AccountInterface $account): bool
    {
        $tiers = $program->getTiers();

        // if account have directly assigned program and it assigned or if doesn't have
        if ((count($account->getProgramIds()) && in_array($program->getId(), $account->getProgramIds()))
            || (count($account->getProgramIds()) == 0 && $program->getIsVisible())
        ) {
            // if program has commission for group to which account assigned
            foreach ($tiers as $tier) {
                if ($account->getGroupId() === $tier->getGroupId()) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getProgramCollection(EventInterface $event): ProgramCollection
    {
        $collection = $this->programRepository->getCollection()
            ->addStoreFilter($event->getStoreId())
            ->addActiveFilter()
            ->addFieldToFilter(ProgramInterface::TYPE, $event->getType())
            ->setOrder(ProgramInterface::PRIORITY, Collection::SORT_ORDER_ASC);

        if ($event->getType() != ProgramInterface::TYPE_PAY_PER_SALE) {
            $collection->setPageSize(1)->setCurPage(1);
        }

        return $collection;
    }
}
