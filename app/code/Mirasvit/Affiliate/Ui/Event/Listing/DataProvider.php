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

namespace Mirasvit\Affiliate\Ui\Event\Listing;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\RequestInterface;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\EventInterface;
use Mirasvit\Affiliate\Repository\AccountRepository;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{

    private $accountRepository;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        AccountRepository $accountRepository,
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        array $meta = [],
        array $data = []
    ) {
        $this->accountRepository = $accountRepository;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $reporting, $searchCriteriaBuilder, $request, $filterBuilder, $meta, $data);
    }

    public function getSearchCriteria(): SearchCriteria
    {
        if ($this->request->getParam(AccountInterface::ID)) {
            $accountId = (int)$this->request->getParam(AccountInterface::ID);

            $filter = $this->filterBuilder
                ->setField(AccountInterface::ID)
                ->setValue($accountId)->create();

            $this->searchCriteriaBuilder->addFilter($filter);
        }

        return parent::getSearchCriteria();
    }

    protected function searchResultToOutput(SearchResultInterface $searchResult): array
    {
        $result = [
            'items'        => [],
            'totalRecords' => $searchResult->getTotalCount(),
        ];

        /** @var EventInterface $item */
        foreach ($searchResult->getItems() as $item) {
            $account = $this->accountRepository->get($item->getAccountId());

            $result['items'][] = [
                EventInterface::ID                 => $item->getId(),
                EventInterface::TYPE               => $item->getType(),
                EventInterface::VISITOR_IDENTIFIER => $item->getVisitorIdentifier(),
                EventInterface::IS_PROCESSED       => $item->isProcessed() ? '1' : '0',
                EventInterface::AMOUNT             => $item->getAmount(),
                EventInterface::REFERER            => $item->getReferer(),
                EventInterface::COMMISSION         => $item->getCommission(),
                EventInterface::STORE_ID           => $item->getStoreId(),
                EventInterface::CREATED_AT         => $item->getCreatedAt(),
                'account'                          => $account ? $account->getEmail() : '',
            ];
        }

        return $result;
    }
}
