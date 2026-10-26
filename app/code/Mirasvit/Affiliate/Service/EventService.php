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

namespace Mirasvit\Affiliate\Service;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Affiliate\Api\Data\BannerInterface;
use Mirasvit\Affiliate\Api\Data\EventInterface;
use Mirasvit\Affiliate\Repository\EventRepository;

class EventService
{
    private $eventRepository;

    private $accountService;

    private $cookieManager;

    private $orderRepository;

    private $request;

    private $storeManager;

    private $customerSession;

    public function __construct(
        EventRepository          $eventRepository,
        AccountService           $accountService,
        CookieManagerInterface   $cookieManager,
        OrderRepositoryInterface $orderRepository,
        RequestInterface         $request,
        StoreManagerInterface    $storeManager,
        CustomerSession          $customerSession
    ) {
        $this->eventRepository = $eventRepository;
        $this->accountService  = $accountService;
        $this->cookieManager   = $cookieManager;
        $this->orderRepository = $orderRepository;
        $this->request         = $request;
        $this->storeManager    = $storeManager;
        $this->customerSession = $customerSession;
    }

    public function registerClick(BannerInterface $banner = null): ?EventInterface
    {
        $customerId = (int)$this->customerSession->getId();
        $account    = $this->accountService->findAccount($customerId);
        if (!$account) {
            return null;
        }

        $event = $this->eventRepository->create();

        $event->setType(EventInterface::TYPE_CLICK)
            ->setStoreId((int)$this->storeManager->getStore()->getId())
            ->setAccountId((int)$account->getId())
            ->setCustomerId($customerId ? (int)$customerId : null)
            ->setVisitorIdentifier((string)$this->getVisitorIdentifier())
            ->setReferer((string)$this->getReferer());

        if ($banner) {
            $event->setEntityId((int)$banner->getId());
        }

        return $this->eventRepository->save($event);
    }

    public function registerLead(int $customerId): ?EventInterface
    {
        $account = $this->accountService->findAccount($customerId);
        if (!$account) {
            return null;
        }

        $event = $this->eventRepository->create();

        $event->setType(EventInterface::TYPE_LEAD)
            ->setStoreId((int)$this->storeManager->getStore()->getId())
            ->setAccountId((int)$account->getId())
            ->setCustomerId((int)$customerId)
            ->setVisitorIdentifier((string)$this->getVisitorIdentifier())
            ->setReferer((string)$this->getReferer());

        return $this->eventRepository->save($event);
    }

    public function registerImpression(BannerInterface $banner): ?EventInterface
    {
        $customerId = (int)$this->customerSession->getId();
        $account    = $this->accountService->findAccount($customerId);
        if (!$account) {
            return null;
        }

        $collection = $this->eventRepository->getCollection();
        $collection
            ->addFieldToFilter(EventInterface::TYPE, EventInterface::TYPE_IMPRESSION)
            ->addFieldToFilter(EventInterface::ACCOUNT_ID, $account->getId())
            ->addFieldToFilter(EventInterface::ENTITY_ID, $banner->getId())
            ->addFieldToFilter(EventInterface::VISITOR_IDENTIFIER, $this->getVisitorIdentifier());

        $referer = $this->getReferer();
        if (!$referer) {
            return null;
        }

        if ($collection->getSize() == 0) {
            $event = $this->eventRepository->create();

            $event->setType(EventInterface::TYPE_IMPRESSION)
                ->setStoreId((int)$this->storeManager->getStore()->getId())
                ->setAccountId((int)$account->getId())
                ->setCustomerId((int)$this->customerSession->getId())
                ->setEntityId((int)$banner->getId())
                ->setVisitorIdentifier((string)$this->getVisitorIdentifier())
                ->setReferer((string)$this->getReferer());

            return $this->eventRepository->save($event);
        }

        return null;
    }

    public function registerSale(OrderInterface $order): ?EventInterface
    {
        $events = $this->eventRepository->getCollection()
            ->addFieldToFilter(EventInterface::TYPE, EventInterface::TYPE_SALE)
            ->addFieldToFilter(EventInterface::ENTITY_ID, $order->getEntityId());

        if ($events->count()) {
            /** @var EventInterface $event */
            $event = $events->getFirstItem();
            $event->setIsProcessed(false);
        } else {
            $customerId = (int)$order->getCustomerId();
            $account    = $this->accountService->findAccount($customerId);

            if (!$account && $order->getCouponCode()) {
                $account = $this->accountService->getAccountByCoupon($order->getCouponCode());
                if ($account) {
                    $this->accountService->ensureCustomerRelation($account->getIdentifier(), $customerId);
                }
            }

            if (!$account) {
                return null;
            }

            $event = $this->eventRepository->create();
            $event->setType(EventInterface::TYPE_SALE)
                ->setStoreId((int)$order->getStoreId())
                ->setAccountId((int)$account->getId())
                ->setCustomerId($customerId)
                ->setEntityId((int)$order->getEntityId())
                ->setAmount((float)$order->getBaseGrandTotal())
                ->setVisitorIdentifier((string)$this->getVisitorIdentifier())
                ->setReferer((string)$this->getReferer());

            $orderTs = strtotime($order->getCreatedAt());
            if ($orderTs && time() - $orderTs > 60) {
                // An event will be created for an order that was created some time ago.
                // Such an event does not need to be processed, it seems that records were deleted from the mst_affiliate_event table.
                $event->setIsProcessed(true);
            }
        }

        return $this->eventRepository->save($event);
    }

    public function getOrder(EventInterface $event): ?OrderInterface
    {
        try {
            return $this->orderRepository->get($event->getEntityId());
        } catch (\Exception $e) {
            return null;
        }
    }

    public function isUniqueEvent(EventInterface $event): bool
    {
        $collection = $this->eventRepository->getCollection();
        $collection
            ->addFieldToFilter(EventInterface::ID, ['lt' => $event->getId()])
            ->addFieldToFilter(EventInterface::TYPE, $event->getType())
            ->addFieldToFilter(EventInterface::ACCOUNT_ID, $event->getAccountId())
            ->addFieldToFilter(EventInterface::VISITOR_IDENTIFIER, $event->getVisitorIdentifier());
        if ($event->getCustomerId()) {
            $collection->addFieldToFilter(EventInterface::CUSTOMER_ID, $event->getCustomerId());
        } else {
            $collection->addFieldToFilter(EventInterface::CUSTOMER_ID, ['null' => true]);
        }

        return $collection->getSize() == 0;
    }

    private function getVisitorIdentifier(): string
    {
        $remoteAddr = $this->request->getServer('HTTP_CF_CONNECTING_IP');

        if (!$remoteAddr) {
            $remoteAddr = $this->request->getServer('HTTP_X_FORWARDED_FOR');
        }

        if (!$remoteAddr) {
            $remoteAddr = $this->request->getServer('HTTP_CLIENT_IP');
        }

        if (!$remoteAddr) {
            $remoteAddr = $this->request->getServer('REMOTE_ADDR');
        }

        return (string)$remoteAddr;
    }

    private function getReferer(): string
    {
        $referrer = (string)$this->cookieManager->getCookie('affiliate_referrer');
        if (!$referrer) {
            $referrer = trim((string)$this->request->getParam('referer'));
        }
        if (!$referrer) {
            $referrer = trim((string)$this->request->getServer('HTTP_REFERER'));
        }
        if (!$referrer) {
            return '';
        }

        $referrerHost = parse_url($referrer, PHP_URL_HOST);
        if (!$referrerHost) {
            return '';
        }

        $storeHost = parse_url($this->storeManager->getStore()->getBaseUrl(), PHP_URL_HOST);
        if ($referrerHost == $storeHost) {
            return '';
        }

        return (string)$referrer;
    }
}
