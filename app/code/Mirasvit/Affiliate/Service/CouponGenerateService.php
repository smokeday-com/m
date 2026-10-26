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

use Mirasvit\Affiliate\Api\Data\CouponInterface;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Model\CouponGenerate\Builder;
use Mirasvit\Affiliate\Repository\CouponRepository;

class CouponGenerateService
{
    private const MAX_ATTEMPT_COUNT = 20;

    private $configProvider;

    private $builder;

    private $couponRepository;

    public function __construct(
        ConfigProvider   $configProvider,
        Builder          $builder,
        CouponRepository $couponRepository
    ) {
        $this->configProvider   = $configProvider;
        $this->builder          = $builder;
        $this->couponRepository = $couponRepository;
    }

    public function generateCode(): string
    {
        $pattern = $this->configProvider->getCouponPattern();
        $code    = $this->generateCouponCodeByPattern($pattern);
        if (!$code) {
            $code = $this->generateNumCouponCode();
        }

        if (!$code) {
            $code = 'COUPON-' . uniqid();
        }

        return $code;
    }

    private function generateCouponCodeByPattern(string $pattern): ?string
    {
        try {
            $attempt = 0;
            do {
                $code = $this->builder->createCouponCode($pattern);
                $attempt++;
                $isExists = $this->couponRepository->getCollection()
                        ->addFieldToFilter(CouponInterface::CODE, $code)
                        ->count() > 0;
            } while ($isExists && $attempt < self::MAX_ATTEMPT_COUNT);

            return $isExists ? null : $code;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function generateNumCouponCode(): ?string
    {
        $attempt = 0;
        do {
            $code = 'COUPON-' . rand(100, 999);
            $attempt++;
            $isExists = $this->couponRepository->getCollection()
                    ->addFieldToFilter(CouponInterface::CODE, $code)
                    ->count() > 0;
        } while ($isExists && $attempt < self::MAX_ATTEMPT_COUNT);

        return $isExists ? null : $code;
    }
}
