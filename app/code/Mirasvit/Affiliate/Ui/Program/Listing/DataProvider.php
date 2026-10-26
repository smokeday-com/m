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

namespace Mirasvit\Affiliate\Ui\Program\Listing;

use Magento\Framework\Api\Search\SearchResultInterface;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    protected function searchResultToOutput(SearchResultInterface $searchResult): array
    {
        $result = [
            'items'        => [],
            'totalRecords' => $searchResult->getTotalCount(),
        ];

        /** @var ProgramInterface $item */
        foreach ($searchResult->getItems() as $item) {
            $activeFrom = $item->getActiveFrom();
            $activeTo   = $item->getActiveTo();

            $result['items'][] = [
                ProgramInterface::ID          => $item->getId(),
                ProgramInterface::NAME        => $item->getName(),
                ProgramInterface::DESCRIPTION => $item->getDescription(),
                ProgramInterface::TYPE        => $item->getType(),
                ProgramInterface::IS_ACTIVE   => $item->getIsActive() ? '1' : '0',
                ProgramInterface::ACTIVE_FROM => ($activeFrom === '0000-00-00 00:00:00') ? null : $activeFrom,
                ProgramInterface::ACTIVE_TO   => ($activeTo === '0000-00-00 00:00:00') ? null : $activeTo,
                ProgramInterface::IS_VISIBLE  => $item->getIsVisible() ? '1' : '0',
                ProgramInterface::PRIORITY    => $item->getPriority(),

                ProgramInterface::COUPON_IS_ACTIVE   => $item->getCouponIsActive(),
                ProgramInterface::CART_RULE_ID       => $item->getCartRuleId(),
                ProgramInterface::IS_STOP_PROCESSING => $item->getIsStopProcessing() ? '1' : '0',
            ];
        }

        return $result;
    }
}
