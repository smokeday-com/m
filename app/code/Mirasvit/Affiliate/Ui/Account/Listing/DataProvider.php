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

namespace Mirasvit\Affiliate\Ui\Account\Listing;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\RequestInterface;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Magento\Framework\Api\Filter;
use Mirasvit\Affiliate\Repository\GroupRepository;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    private $groupRepository;

    /**
     * @SuppressWarnings(PHPMD)
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        GroupRepository $groupRepository,
        array $meta = [],
        array $data = []
    ) {
        $this->groupRepository = $groupRepository;

        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
    }

    protected function searchResultToOutput(SearchResultInterface $searchResult): array
    {
        $result = [
            'items'        => [],
            'totalRecords' => $searchResult->getTotalCount(),
        ];

        /** @var AccountInterface|\Magento\Framework\DataObject $item */
        foreach ($searchResult->getItems() as $item) {
            $group = $this->groupRepository->get((int)$item->getGroupId());

            $data = [
                AccountInterface::ID                => $item->getId(),
                AccountInterface::CUSTOMER_ID       => $item->getCustomerId(),
                AccountInterface::IDENTIFIER        => $item->getIdentifier(),
                AccountInterface::WEBSITE           => $item->getWebsite(),
                AccountInterface::IS_SUBSCRIBED     => $item->getIsSubscribed(),
                AccountInterface::STATUS            => $item->getStatus(),
                AccountInterface::PAYMENT_METHOD    => $item->getPaymentMethod(),
                AccountInterface::BALANCE           => $item->getBalance(),
                AccountInterface::TOTAL_COMMISSIONS => $item->getTotalCommissions(),
                AccountInterface::TOTAL_WITHDRAWALS => $item->getTotalWithdrawals(),
                AccountInterface::CREATED_AT        => $item->getCreatedAt(),
                'name'                              => $item->getData('name'),
                'email'                             => $item->getData('email'),
                'in_hold'                           => $item->getData('in_hold') ? : 0,
                AccountInterface::GROUP_ID          => $item->getGroupId(),
            ];

            $result['items'][] = $data;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function addFilter(Filter $filter)
    {
        $filter->setField('main_table.' . $filter->getField());

        parent::addFilter($filter);
    }
}
