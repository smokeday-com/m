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

use Magento\Framework\Model\AbstractModel;
use Magento\Quote\Model\Quote\Item;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\ConditionInterface;
use Magento\Rule\Model\Condition\Context;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Repository\AccountCustomerRepository;
use Mirasvit\Affiliate\Repository\AccountRepository;

class AccountCondition extends AbstractCondition
{
    private const OPTION_LIFETIME_COMMISSION = 'lifetime_commission';

    private $accountCustomerRepository;

    private $accountRepository;

    public function __construct(
        AccountRepository         $accountRepository,
        AccountCustomerRepository $accountCustomerRepository,
        Context                   $context,
        array                     $data = []
    ) {
        parent::__construct($context, $data);

        $this->accountRepository         = $accountRepository;
        $this->accountCustomerRepository = $accountCustomerRepository;
    }

    public function loadAttributeOptions(): ConditionInterface
    {
        $attributes = [
            self::OPTION_LIFETIME_COMMISSION => (string)__('Lifetime Commission'),
        ];
        $this->setData('attribute_option', $attributes);

        return $this;
    }

    public function getInputType(): string
    {
        switch ($this->getAttribute()) {
            case self::OPTION_LIFETIME_COMMISSION:
                return 'numeric';
            default:
                return 'string';
        }
    }

    public function getValueElementType(): string
    {
        return 'text';
    }

    public function getValueParsed()
    {
        return $this->getData('value');
    }

    public function validate(AbstractModel $model): bool
    {
        if (!($model instanceof Item)) {
            return true;
        }

        /** @var AccountInterface $model */
        switch ($this->getAttribute()) {
            case self::OPTION_LIFETIME_COMMISSION:
                return $this->validateAttribute($this->getAccountTotalCommission($model));

            default:
                return false;
        }
    }

    protected function getAccountTotalCommission(Item $item): float
    {
        $customerId = (int)$item->getQuote()->getCustomer()->getId();
        $relation   = $this->accountCustomerRepository->getByCustomerId($customerId);
        if (!$relation) {
            return 0.0;
        }

        $account = $this->accountRepository->get($relation->getAccountId());
        if (!$account) {
            return 0.0;
        }

        return $account->getTotalCommissions();
    }
}
