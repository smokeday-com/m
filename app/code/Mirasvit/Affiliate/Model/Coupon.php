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
use Mirasvit\Affiliate\Api\Data\CouponInterface;

class Coupon extends AbstractModel implements CouponInterface
{
    protected function _construct(): void
    {
        $this->_init(\Mirasvit\Affiliate\Model\ResourceModel\Coupon::class);
    }

    public function getId(): int
    {
        return (int)$this->getData(self::ID);
    }

    public function getAccountId(): int
    {
        return (int)$this->getData(self::ACCOUNT_ID);
    }

    public function setAccountId(int $input): CouponInterface
    {
        return $this->setData(self::ACCOUNT_ID, $input);
    }

    public function getProgramId(): int
    {
        return (int)$this->getData(self::PROGRAM_ID);
    }

    public function setProgramId(int $input): CouponInterface
    {
        return $this->setData(self::PROGRAM_ID, $input);
    }

    public function getCode(): string
    {
        return (string)$this->getData(self::CODE);
    }

    public function setCode(string $input): CouponInterface
    {
        return $this->setData(self::CODE, $input);
    }
}
