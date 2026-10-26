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

namespace Mirasvit\Affiliate\Ui\Group\Listing;

use Magento\Framework\Api\Search\SearchResultInterface;
use Mirasvit\Affiliate\Api\Data\GroupInterface;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    protected function searchResultToOutput(SearchResultInterface $searchResult): array
    {
        $result = [
            'items'        => [],
            'totalRecords' => $searchResult->getTotalCount(),
        ];

        /** @var GroupInterface $item */
        foreach ($searchResult->getItems() as $item) {
            $result['items'][] = [
                GroupInterface::ID             => $item->getId(),
                GroupInterface::NAME           => $item->getName(),
                GroupInterface::PRIORITY       => $item->getPriority(),
                GroupInterface::IS_DEFAULT     => $item->getIsDefault() ? '1' : '0',
                GroupInterface::IS_AUTO_UPDATE => $item->getIsAutoUpdate()  ? '1' : '0',
                GroupInterface::IS_VISIBLE     => $item->getIsVisible() ? '1' : '0'
            ];
        }

        return $result;
    }
}
