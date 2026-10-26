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
use Mirasvit\Affiliate\Api\Data\WithdrawInterface;

class Withdraw extends AbstractModel implements WithdrawInterface
{
    protected function _construct(): void
    {
        $this->_init(\Mirasvit\Affiliate\Model\ResourceModel\Withdraw::class);
    }

    public function getId(): int
    {
        return (int)$this->getData(self::ID);
    }

    public function getAccountId(): int
    {
        return (int)$this->getData(self::ACCOUNT_ID);
    }

    public function setAccountId(int $accountId): WithdrawInterface
    {
        return $this->setData(self::ACCOUNT_ID, $accountId);
    }

    public function getAmount(): float
    {
        return (float)$this->getData(self::AMOUNT);
    }

    public function setAmount(float $amount): WithdrawInterface
    {
        return $this->setData(self::AMOUNT, $amount);
    }

    public function getFee(): float
    {
        return (float)$this->getData(self::FEE);
    }

    public function setFee(float $fee): WithdrawInterface
    {
        return $this->setData(self::FEE, $fee);
    }

    public function getMessage(): string
    {
        return (string)$this->getData(self::MESSAGE);
    }

    public function setMessage(string $message): WithdrawInterface
    {
        return $this->setData(self::MESSAGE, $message);
    }

    public function getStatus(): string
    {
        return (string)$this->getData(self::STATUS);
    }

    public function setStatus(string $status): WithdrawInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): WithdrawInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
