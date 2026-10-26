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

namespace Mirasvit\Affiliate\Repository;

use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Mirasvit\Affiliate\Api\Data\CouponInterface;
use Mirasvit\Affiliate\Model\CouponFactory;
use Mirasvit\Affiliate\Model\ResourceModel\Coupon\CollectionFactory as CouponCollectionFactory;

class CouponRepository
{
    private $entityManager;

    private $couponFactory;

    private $couponCollectionFactory;

    public function __construct(
        EntityManager           $entityManager,
        CouponFactory           $couponFactory,
        CouponCollectionFactory $couponCollectionFactory
    ) {
        $this->entityManager           = $entityManager;
        $this->couponFactory           = $couponFactory;
        $this->couponCollectionFactory = $couponCollectionFactory;
    }

    /**
     * @return CouponInterface[]|\Mirasvit\Affiliate\Model\ResourceModel\Coupon\Collection
     */
    public function getCollection()
    {
        return $this->couponCollectionFactory->create();
    }

    public function create(): CouponInterface
    {
        return $this->couponFactory->create();
    }

    public function get(int $couponId): ?CouponInterface
    {
        $coupon = $this->couponFactory->create();
        $this->entityManager->load($coupon, $couponId);
        if (!$coupon->getId()) {
            throw NoSuchEntityException::singleField(CouponInterface::ID, $couponId);
        }

        return $coupon;
    }

    public function save(CouponInterface $coupon): ?CouponInterface
    {
        return $this->entityManager->save($coupon);
    }
}
