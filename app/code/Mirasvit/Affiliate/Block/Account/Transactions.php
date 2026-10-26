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

namespace Mirasvit\Affiliate\Block\Account;

use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magento\Theme\Block\Html\Pager;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Model\ResourceModel\Transaction\Collection;
use Mirasvit\Affiliate\Repository\TransactionRepository;
use Mirasvit\Affiliate\Service\SessionService;

class Transactions extends Template
{
    private $transactionRepository;

    private $sessionService;

    private $pager;

    public function __construct(
        TransactionRepository $eventRepository,
        SessionService        $sessionService,
        Template\Context      $context
    ) {
        $this->transactionRepository = $eventRepository;
        $this->sessionService        = $sessionService;

        parent::__construct($context);
    }

    public function getTransactionCollection(): ?Collection
    {
        $account = $this->getAccount();
        if (!$account) {
            return null;
        }

        $pager = $this->getPagerBlock();

        return $this->transactionRepository->getCollection()
            ->addFieldToFilter('account_id', $account->getId())
            ->setPageSize((int)$pager->getLimit())
            ->setCurPage((int)$pager->getCurrentPage())
            ->setOrder('created_at', 'desc');
    }

    public function getAccount(): ?AccountInterface
    {
        return $this->sessionService->getAccount();
    }

    public function getPagerHtml(): string
    {
        return $this->getChildHtml('pager');
    }

    protected function _prepareLayout(): Transactions
    {

        if ($this->getTransactionCollection()) {
            $pager = $this->getPagerBlock()
                ->setShowPerPage(true)
                ->setCollection($this->getTransactionCollection());
            $this->setChild('pager', $this->pager);
            $this->getTransactionCollection()->load();
        }

        return parent::_prepareLayout();
    }

    /**
     * @return Pager
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getPagerBlock(): BlockInterface
    {
        if (!$this->pager) {
            $this->pager = $this->getLayout()->createBlock(Pager::class, 'affiliate.transaction.pager');
        }

        return $this->pager;
    }
}
