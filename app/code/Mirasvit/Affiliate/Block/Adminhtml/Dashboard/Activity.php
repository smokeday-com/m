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

namespace Mirasvit\Affiliate\Block\Adminhtml\Dashboard;

use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\EventInterface;
use Mirasvit\Affiliate\Api\Data\WithdrawInterface;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Repository\EventRepository;
use Mirasvit\Affiliate\Repository\TransactionRepository;
use Mirasvit\Affiliate\Repository\WithdrawRepository;
use Mirasvit\Affiliate\Service\AccountService;
use Mirasvit\Affiliate\Service\EventService;
use Mirasvit\Affiliate\Service\TransactionService;
use Mirasvit\Affiliate\Service\WithdrawService;

/**
 * @SuppressWarnings(PHPMD)
 */
class Activity extends Template
{
    private $accountRepository;

    private $transactionRepository;

    private $transactionService;

    private $accountService;

    private $withdrawRepository;

    private $withdrawService;

    private $eventService;

    private $eventRepository;

    private $pricingHelper;

    public function __construct(
        AccountRepository     $accountRepository,
        AccountService        $accountService,
        TransactionRepository $transactionRepository,
        TransactionService    $transactionService,
        EventRepository       $eventRepository,
        EventService          $eventService,
        WithdrawRepository    $withdrawRepository,
        WithdrawService       $withdrawService,
        PricingHelper         $pricingHelper,
        Context               $context
    ) {
        $this->accountRepository     = $accountRepository;
        $this->accountService        = $accountService;
        $this->transactionRepository = $transactionRepository;
        $this->transactionService    = $transactionService;
        $this->eventRepository       = $eventRepository;
        $this->eventService          = $eventService;
        $this->withdrawRepository    = $withdrawRepository;
        $this->withdrawService       = $withdrawService;
        $this->pricingHelper         = $pricingHelper;

        parent::__construct($context);
    }

    public function getActivity(): array
    {
        $activity = [];

        $this->joinAccounts($activity);
        $this->joinEvents($activity);
        $this->joinWithdraws($activity);

        krsort($activity);

        $result = [];

        $i = 0;
        foreach ($activity as $time => $data) {
            if ($i++ < 10) {
                $result[$time] = $data;
            }
        }

        return $result;
    }

    private function joinAccounts(array &$activity): void
    {
        $collection = $this->accountRepository->getCollection();

        $collection->setOrder(AccountInterface::CREATED_AT, 'desc')
            ->setPageSize(10);

        foreach ($collection as $account) {
            $accountUrl = $this->_urlBuilder->getUrl('affiliate/account/edit', [AccountInterface::ID => $account->getId()]);

            $link = "<a href='$accountUrl'>{$this->escapeHtml($account->getName())}</a>";

            $activity[strtotime($account->getCreatedAt())] = [
                'type' => 'account',
                'text' => __(
                    '%1 was joined to affiliate program',
                    $link
                ),
            ];
        }
    }

    private function joinEvents(array &$activity): void
    {
        $collection = $this->eventRepository->getCollection();
        $collection
            ->addFieldToFilter(EventInterface::TYPE, EventInterface::TYPE_SALE)
            ->addFieldToFilter(EventInterface::IS_PROCESSED, true)
            ->setOrder(EventInterface::CREATED_AT, 'desc')
            ->setPageSize(10);

        foreach ($collection as $event) {
            $account = $this->accountRepository->get($event->getAccountId());

            if (!$account) {
                continue;
            }

            $order = $this->eventService->getOrder($event);
            if (!$order) {
                continue;
            }
            $accountUrl = $this->_urlBuilder->getUrl('affiliate/account/edit', [AccountInterface::ID => $account->getId()]);
            $orderUrl   = $this->_urlBuilder->getUrl('sales/order/view', ['order_id' => $event->getEntityId()]);

            $accountLink = "<a href='$accountUrl'>{$this->escapeHtml($account->getName())}</a>";
            $orderLink   = "<a href='$orderUrl'>{$order->getIncrementId()}</a>";

            if ($event->getCommission() > 0) {
                $activity[strtotime($event->getCreatedAt())] = [
                    'type' => 'transaction',
                    'text' => __(
                        '%1 referred new order %2 (%3) with commission %4',
                        $accountLink,
                        $orderLink,
                        $this->pricingHelper->currency($event->getAmount()),
                        $this->pricingHelper->currency($event->getCommission())
                    ),
                ];
            } else {
                $activity[strtotime($event->getCreatedAt())] = [
                    'type' => 'transaction',
                    'text' => __(
                        '%1 referred new order %2 (%3)',
                        $accountLink,
                        $orderLink,
                        $order->getIncrementId(),
                        $this->pricingHelper->currency($event->getAmount())
                    ),
                ];
            }
        }
    }

    private function joinWithdraws(array &$activity): void
    {
        $collection = $this->withdrawRepository->getCollection();

        $collection->setPageSize(10)
            ->setOrder(WithdrawInterface::CREATED_AT, 'desc');

        foreach ($collection as $withdraw) {
            $account = $this->withdrawService->getAccount($withdraw);

            if (!$account) {
                continue; // account removed
            }

            $accountUrl  = $this->_urlBuilder->getUrl('affiliate/account/edit', [AccountInterface::ID => $account->getId()]);
            $accountLink = "<a href='$accountUrl'>{$this->escapeHtml($account->getName())}</a>";

            $activity[strtotime($withdraw->getCreatedAt())] = [
                'type' => 'withdraw',
                'text' => __(
                    '%1 submitted withdrawal request (%2)',
                    $accountLink,
                    $this->pricingHelper->currency($withdraw->getAmount())
                ),
            ];
        }
    }
}
