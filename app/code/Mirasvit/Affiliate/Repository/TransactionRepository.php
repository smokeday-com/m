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

namespace Mirasvit\Affiliate\Repository;

use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Mirasvit\Affiliate\Api\Data\TransactionInterface;
use Mirasvit\Affiliate\Model\Transaction;
use Mirasvit\Affiliate\Model\TransactionFactory;
use Mirasvit\Affiliate\Model\ResourceModel\Transaction\CollectionFactory as TransactionCollectionFactory;

class TransactionRepository
{
    private $entityManager;

    private $transactionFactory;

    private $transactionCollectionFactory;

    public function __construct(
        EntityManager $entityManager,
        TransactionFactory $transactionFactory,
        TransactionCollectionFactory $transactionCollectionFactory
    ) {
        $this->entityManager                = $entityManager;
        $this->transactionFactory           = $transactionFactory;
        $this->transactionCollectionFactory = $transactionCollectionFactory;
    }

    /**
     * @return TransactionInterface[]|\Mirasvit\Affiliate\Model\ResourceModel\Transaction\Collection
     */
    public function getCollection()
    {
        return $this->transactionCollectionFactory->create();
    }

    public function create(): TransactionInterface
    {
        return $this->transactionFactory->create();
    }

    /**
     * @param int $id
     *
     * @return TransactionInterface|Transaction
     * @throws NoSuchEntityException
     */
    public function get(int $id)
    {
        $transaction = $this->create();

        $this->entityManager->load($transaction, $id);

        if (!$transaction->getId()) {
            throw NoSuchEntityException::singleField('id', $id);
        }

        return $transaction;
    }

    public function save(TransactionInterface $transaction): TransactionInterface
    {
        return $this->entityManager->save($transaction);
    }
}
