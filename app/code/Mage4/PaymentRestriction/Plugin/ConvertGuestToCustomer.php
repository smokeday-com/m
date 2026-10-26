<?php
/**
 * ParadoxLabs, Inc.
 * https://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author <support@paradoxlabs.com>
 */

namespace Mage4\PaymentRestriction\Plugin;

use Magento\Framework\Exception\LocalizedException;

/**
 * Class ConvertGuestToCustomer
 */
class ConvertGuestToCustomer
{
    /**
     * @var \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory
     */
    protected $cardCollectionFactory;

    /**
     * @var \ParadoxLabs\TokenBase\Api\CardRepositoryInterface
     */
    protected $cardRepository;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Plugin constructor.
     *
     * @param \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory
     * @param \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory,
        \ParadoxLabs\TokenBase\Api\CardRepositoryInterface                $cardRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface                $scopeConfig,
        \Magento\Customer\Model\Session                                   $customerSession,
        \Magento\Sales\Api\OrderRepositoryInterface                       $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder                      $searchCriteriaBuilder
    )
    {
        $this->cardCollectionFactory = $cardCollectionFactory;
        $this->cardRepository = $cardRepository;
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Associate customer cards after post-checkout register.
     * Subsequently, for any registration done within the time span.
     *
     * @param \Magento\Customer\Controller\Account\CreatePost $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(\Magento\Customer\Controller\Account\CreatePost $subject, $result)
    {

        if ($this->customerSession->getCustomerData()) {
            /**
             * Look for a guest card used by this email within the last day, and blindly attach it if we get a match.
             * This isn't flawless, but loading the order to get any tokenbase_id would be much slower.
             */
            $cardCollection = $this->cardCollectionFactory->create();
            $cardCollection->addFieldToFilter('customer_id', '0');
            $cardCollection->addFieldToFilter('customer_email', $this->customerSession->getCustomerData()->getEmail());
            $cardCollection->addFieldToFilter(
                'last_use',
                [
                    'gt' => date('c', strtotime('-48 hours')),
                    'date' => true,
                ]
            );
            $cardCollection->setOrder('id', 'desc');
            $cardCollection->setPageSize(1);
            if ($cardCollection->getSize() > 0) {

                $searchCriteria = $this->searchCriteriaBuilder->addFilter('customer_email', $this->customerSession->getCustomerData()->getEmail(), 'eq')->create();

                $orders = $this->orderRepository->getList($searchCriteria);
                foreach ($orders as $order) {
                    if ($order->getId() && !$order->getCustomerId()) {
                        $order->setCustomerId($this->customerSession->getId());
                        $order->setCustomerIsGuest(0);
                        $order->setCustomerGroupId($this->customerSession->getCustomerData()->getGroupId());
                        $this->orderRepository->save($order);
                    }
                }
                /** @var \ParadoxLabs\TokenBase\Api\Data\CardInterface $card */
                foreach ($cardCollection as $card) {
                    $card->setCustomerId($this->customerSession->getId());

                    // Activate the card by default if config is opt-out.
                    $activate = (int)$this->scopeConfig->getValue(
                        'payment/' . $card->getMethod() . '/savecard_opt_out',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    );
                    if ($activate === 1) {
                        $card->setActive(1);
                    }

                    try {
                        $this->cardRepository->save($card);
                    } catch (LocalizedException $e) {
                        // No-op: gracefully skip a card save if it fails.
                    }
                }
            }
        }

        return $result;
    }
}
