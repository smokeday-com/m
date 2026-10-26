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

namespace Mirasvit\Affiliate\Ui\Transaction\Form;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Mirasvit\Affiliate\Api\Data\TransactionInterface;
use Mirasvit\Affiliate\Repository\TransactionRepository;

class DataProvider extends AbstractDataProvider
{
    public function __construct(
        TransactionRepository $transactionRepository,
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $transactionRepository->getCollection();

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        $result = [];

        foreach ($this->collection as $item) {
            $result[$item->getId()] = [
                TransactionInterface::ID         => $item->getId(),
                TransactionInterface::ACCOUNT_ID => $item->getAccountId(),
                TransactionInterface::STATUS     => $item->getStatus(),
                TransactionInterface::AMOUNT     => $item->getAmount(),
                TransactionInterface::MESSAGE    => $item->getMessage(),
                TransactionInterface::STORE_ID   => $item->getStoreId(),
            ];
        }

        return $result;
    }
}
