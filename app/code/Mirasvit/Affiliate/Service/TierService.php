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

use Magento\Framework\DataObject;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Api\Data\TierInterface;
use Mirasvit\Affiliate\Api\Data\TierInterfaceFactory;

class TierService
{
    private $tierFactory;

    public function __construct(TierInterfaceFactory $tierFactory)
    {
        $this->tierFactory = $tierFactory;
    }

    /**
     * @param array $tierData
     *
     * @return TierInterface[]
     */
    public function toTierObjectArray(array $tierData): array
    {
        $tiers = [];

        foreach ($tierData as $data) {
            $tier = $this->tierFactory->create();
            $tier->loadArray($data);

            $tiers[] = $tier;
        }

        return $tiers;
    }

    /**
     * @param TierInterface[]|DataObject[] $tiers
     *
     * @return array
     */
    public function toArray(array $tiers): array
    {
        $data = [];

        foreach ($tiers as $tier) {
            $data[] = $tier->toArray();
        }

        return $data;
    }

    public function getAmount(TierInterface $tier, int $eventsCount = null): float
    {
        if ($eventsCount===null) {
            return $tier->getAmount();
        }

        return $eventsCount > 0 ? $tier->getAmountOther() : $tier->getAmountFirst();
    }

    public function getCommissionAmountForGroup(AccountInterface $account, ProgramInterface $program): float
    {
        $amount = 0;
        $tiers  = $program->getTiers();
        foreach ($tiers as $tier) {
            if ($account->getGroupId() === $tier->getGroupId()) {
                $amount = $this->getAmount($tier);
            }
        }

        return (float)$amount;
    }
}
