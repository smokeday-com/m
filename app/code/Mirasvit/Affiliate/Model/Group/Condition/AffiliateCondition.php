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

namespace Mirasvit\Affiliate\Model\Group\Condition;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\EventInterface;
use Mirasvit\Affiliate\Repository\EventRepository;
use Mirasvit\Affiliate\Service\AccountService;

class AffiliateCondition extends AbstractCondition
{
    private $orderRepository;

    private $eventRepository;

    private $customerRepository;

    private $accountService;

    public function __construct(
        OrderRepositoryInterface    $orderRepository,
        EventRepository             $eventRepository,
        CustomerRepositoryInterface $customerRepository,
        AccountService              $accountService,
        Context                     $context,
        array                       $data = []
    ) {
        $this->orderRepository    = $orderRepository;
        $this->eventRepository    = $eventRepository;
        $this->customerRepository = $customerRepository;
        $this->accountService     = $accountService;

        parent::__construct($context, $data);
    }

    public function loadAttributeOptions(): self
    {
        $attributes = [
            'orders_number'            => (string)__('Number of Orders from reffered customers(all)'),
            'sales_amount'             => (string)__('Sales Amount from reffered customers(all)'),
            'orders_number_commission' => (string)__('Number of Orders from reffered customers(with commission)'),
            'sales_amount_commission'  => (string)__('Sales Amount from reffered customers(with commission)'),
        ];

        asort($attributes);
        $this->setData('attribute_option', $attributes);

        return $this;
    }

    public function loadOperatorOptions(): self
    {
        $this->setData('operator_option', [
            '==' => __('is'),
            '!=' => __('is not'),
            '>'  => __('greater than'),
            '<'  => __('less than'),
            '>=' => __('equals or greater than'),
            '<=' => __('equals or less than'),
        ]);

        return $this;
    }

    public function getInputType(): string
    {
        switch ($this->getAttribute()) {
            case 'orders_number':
            case 'sales_amount':
            case 'orders_number_commission':
            case 'sales_amount_commission':
                return 'numeric';

            default:
                return 'string';
        }
    }

    public function getValueElementType(): string
    {
        switch ($this->getAttribute()) {
            case 'orders_number':
            case 'sales_amount':
            case 'orders_number_commission':
            case 'sales_amount_commission':
                return 'text';

            default:
                return 'text';
        }
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
        if (!($object instanceof AccountInterface)) {
            return true;
        }

        $events = $this->eventRepository
            ->getCollection()
            ->addFieldToFilter(EventInterface::ACCOUNT_ID, $object->getId())
            ->addFieldToFilter(EventInterface::TYPE, EventInterface::TYPE_SALE)
            ->addFieldToFilter(EventInterface::IS_PROCESSED, true);

        if ($this->getAttribute() == 'orders_number_commission' || $this->getAttribute() == 'sales_amount_commission') {
            $events->addFieldToFilter(EventInterface::COMMISSION, ['gt' => 0.00001]);
        }

        $result = null;
        /** @var AccountInterface $object */
        switch ($this->getAttribute()) {
            case 'orders_number_commission':
            case 'orders_number':
                $result = $this->validateAttribute($events->count());
                break;

            case 'sales_amount_commission':
            case 'sales_amount':
                $amount = 0;

                foreach ($events as $event) {
                    $amount += $event->getAmount();
                }

                $result = $this->validateAttribute($amount);
                break;

            /** mp comment start */
            default:
                return false;
            /** mp comment end */
        }

        return $result;
    }
}
