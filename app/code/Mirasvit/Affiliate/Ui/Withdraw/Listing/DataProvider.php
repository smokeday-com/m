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

namespace Mirasvit\Affiliate\Ui\Withdraw\Listing;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\RequestInterface;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\WithdrawInterface;
use Mirasvit\Affiliate\Repository\AccountRepository;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{

    private $accountRepository;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        AccountRepository     $accountRepository,
        string                $name,
        string                $primaryFieldName,
        string                $requestFieldName,
        ReportingInterface    $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface      $request,
        FilterBuilder         $filterBuilder,
        array                 $meta = [],
        array                 $data = []
    ) {
        $this->accountRepository = $accountRepository;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $reporting, $searchCriteriaBuilder, $request, $filterBuilder, $meta, $data);
    }

    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        if (WithdrawInterface::CREATED_AT == (string)$filter->getField()) {
            $filter->setField('main_table.' . WithdrawInterface::CREATED_AT);
        }
        parent::addFilter($filter);
    }

    public function getSearchCriteria(): SearchCriteria
    {
        if ($this->request->getParam(AccountInterface::ID)) {
            $accountId = (int)$this->request->getParam(AccountInterface::ID);

            $filter = $this->filterBuilder
                ->setField('main_table.' . AccountInterface::ID)
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

        /** @var WithdrawInterface $item */
        foreach ($searchResult->getItems() as $item) {
            $account = $this->accountRepository->get($item->getAccountId());

            $result['items'][] = [
                WithdrawInterface::ID         => $item->getId(),
                WithdrawInterface::AMOUNT     => $item->getAmount(),
                WithdrawInterface::FEE        => $item->getFee(),
                WithdrawInterface::MESSAGE    => $item->getMessage(),
                WithdrawInterface::STATUS     => $item->getStatus(),
                WithdrawInterface::CREATED_AT => $item->getCreatedAt(),
                'account'                     => $account ? $account->getEmail() : '',
            ];
        }

        return $result;
    }
}
