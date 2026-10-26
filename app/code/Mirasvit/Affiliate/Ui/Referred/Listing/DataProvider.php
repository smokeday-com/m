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

namespace Mirasvit\Affiliate\Ui\Referred\Listing;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\DataObject;
use Mirasvit\Affiliate\Api\Data\AccountCustomerInterface;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        if (AccountCustomerInterface::CREATED_AT == (string)$filter->getField()) {
            $filter->setField('main_table.' . AccountCustomerInterface::CREATED_AT);
        }
        parent::addFilter($filter);
    }

    protected function searchResultToOutput(SearchResultInterface $searchResult): array
    {
        $result = [
            'items'        => [],
            'totalRecords' => $searchResult->getTotalCount(),
        ];

        /** @var AccountCustomerInterface|DataObject $item */
        foreach ($searchResult->getItems() as $item) {
            $result['items'][] = [
                AccountCustomerInterface::ID             => $item->getId(),
                AccountCustomerInterface::ACCOUNT_ID     => $item->getAccountId(),
                AccountCustomerInterface::CUSTOMER_ID    => $item->getCustomerId(),
                AccountCustomerInterface::CREATED_AT     => $item->getCreatedAt(),
                'affiliate_firstname'                    => $item->getData('affiliate_firstname'),
                'affiliate_lastname'                     => $item->getData('affiliate_lastname'),
                'affiliate_email'                        => $item->getData('affiliate_email'),
                'customer_firstname'                     => $item->getData('customer_firstname'),
                'customer_lastname'                      => $item->getData('customer_lastname'),
                'customer_email'                         => $item->getData('customer_email'),
                AccountCustomerInterface::SUM_COMMISSION => $item->getSumCommission(),
            ];
        }

        return $result;
    }
}
