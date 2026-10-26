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

namespace Mirasvit\Affiliate\Model;

use Magento\Framework\Model\AbstractModel;
use Mirasvit\Affiliate\Api\Data\AccountCustomerInterface;

class AccountCustomer extends AbstractModel implements AccountCustomerInterface
{
    protected function _construct(): void
    {
        $this->_init(\Mirasvit\Affiliate\Model\ResourceModel\AccountCustomer::class);
    }

    public function getId(): int
    {
        return (int)$this->getData(self::ID);
    }

    public function getAccountId(): int
    {
        return (int)$this->getData(self::ACCOUNT_ID);
    }

    public function setAccountId(int $input): AccountCustomerInterface
    {
        return $this->setData(self::ACCOUNT_ID, $input);
    }

    public function getCustomerId(): int
    {
        return (int)$this->getData(self::CUSTOMER_ID);
    }

    public function setCustomerId(int $input): AccountCustomerInterface
    {
        return $this->setData(self::CUSTOMER_ID, $input);
    }

    public function getSumCommission(): float
    {
        return (float)$this->getData(self::SUM_COMMISSION);
    }

    public function setSumCommission(float $sumCommission): AccountCustomerInterface
    {
        return $this->setData(self::SUM_COMMISSION, $sumCommission);
    }

    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): AccountCustomerInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): string
    {
        return (string)$this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt(string $updatedAt): AccountCustomerInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
