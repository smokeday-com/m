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

namespace Mirasvit\Affiliate\Model\Program\Condition;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Model\AbstractModel;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\ConditionInterface;
use Magento\Rule\Model\Condition\Context;
use Magento\Sales\Api\OrderRepositoryInterface;

class GeneralCondition extends AbstractCondition
{
    private const OPTION_STORE          = 'store';
    private const OPTION_CUSTOMER_GROUP = 'customer_group';

    private $orderRepository;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Context                  $context,
        array                    $data = []
    ) {
        $this->orderRepository = $orderRepository;
        parent::__construct($context, $data);
    }

    public function loadAttributeOptions(): ConditionInterface
    {
        $attributes = [
            self::OPTION_CUSTOMER_GROUP => (string)__('Customer Group'),
            self::OPTION_STORE          => (string)__('Store'),
        ];

        asort($attributes);
        $this->setData('attribute_option', $attributes);

        return $this;
    }

    public function getInputType(): string
    {
        switch ($this->getAttribute()) {
            case self::OPTION_CUSTOMER_GROUP:
            case self::OPTION_STORE:
                return 'multiselect';

            default:
                return 'string';
        }
    }

    public function getValueElementType(): string
    {
        switch ($this->getAttribute()) {
            case self::OPTION_CUSTOMER_GROUP:
            case self::OPTION_STORE:
                return 'multiselect';

            default:
                return 'text';
        }
    }

    public function getValueOption(string $option = null): array
    {
        $om      = ObjectManager::getInstance();
        $options = [];

        switch ($this->getAttribute()) {
            case self::OPTION_CUSTOMER_GROUP:
                /** @var \Magento\Customer\Model\ResourceModel\Group\Collection $groupCollection */
                $groupCollection = $om->get(\Magento\Customer\Model\ResourceModel\Group\Collection::class);
                foreach ($groupCollection as $group) {
                    $options[$group->getId()] = $group->getCustomerGroupCode();
                }

                break;

            case self::OPTION_STORE:
                /** @var \Magento\Store\Model\StoreManagerInterface $storeManager */
                $storeManager = $om->get(\Magento\Store\Model\StoreManagerInterface::class);
                foreach ($storeManager->getStores() as $store) {
                    $options[$store->getId()] = $store->getName();
                }

                break;
        }

        $this->setData('value_option', $options);

        return $this->getData('value_option' . ($option !== null ? '/' . $option : ''));
    }

    /**
     * {@inheritdoc}
     */
    public function getValueParsed()
    {
        return $this->getData('value');
    }

    public function validate(AbstractModel $object): bool
    {
        if ($object instanceof \Magento\Sales\Api\Data\OrderItemInterface) {
            return $this->validateWithoutQuote($object);
        }

        if (!($object instanceof CartItemInterface)) {
            return true;
        }

        /** @var \Magento\Quote\Model\Quote\Item $object */
        switch ($this->getAttribute()) {
            case self::OPTION_STORE:
                return $this->validateAttribute($object->getQuote()->getStoreId());

            case self::OPTION_CUSTOMER_GROUP:
                $groupId = (int)$object->getQuote()->getCustomer()->getGroupId();

                return $this->validateAttribute($groupId);

            default:
                return false;
        }
    }

    private function validateWithoutQuote(\Magento\Sales\Api\Data\OrderItemInterface $orderItem): bool
    {
        switch ($this->getAttribute()) {
            case self::OPTION_STORE:
                return $this->validateAttribute($orderItem->getStoreId());

            case self::OPTION_CUSTOMER_GROUP:
                try {
                    $order = $this->orderRepository->get($orderItem->getOrderId());

                    return $this->validateAttribute($order->getCustomerGroupId());
                } catch (\Exception $e) {
                    return false;
                }

            default:
                return false;
        }
    }
}
