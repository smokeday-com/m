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
use Mirasvit\Affiliate\Api\Data\BannerInterface;
use Mirasvit\Affiliate\Model\ResourceModel\Banner\CollectionFactory as BannerCollectionFactory;
use Mirasvit\Affiliate\Api\Data\BannerInterfaceFactory;

class BannerRepository
{
    private $bannerFactory;

    private $bannerCollectionFactory;

    private $entityManager;

    public function __construct(
        EntityManager $entityManager,
        BannerInterfaceFactory $bannerFactory,
        BannerCollectionFactory $bannerCollectionFactory
    ) {
        $this->entityManager           = $entityManager;
        $this->bannerFactory           = $bannerFactory;
        $this->bannerCollectionFactory = $bannerCollectionFactory;
    }

    /**
     * @return BannerInterface[]|\Mirasvit\Affiliate\Model\ResourceModel\Banner\Collection
     */
    public function getCollection()
    {
        return $this->bannerCollectionFactory->create();
    }

    public function create(): BannerInterface
    {
        return $this->bannerFactory->create();
    }

    public function get(int $id): ?BannerInterface
    {
        $banner = $this->create();

        $this->entityManager->load($banner, $id);

        return $banner->getId() ? $banner : null;
    }

    public function delete(BannerInterface $banner): bool
    {
        return $this->entityManager->delete($banner);
    }

    public function save(BannerInterface $banner): BannerInterface
    {
        return $this->entityManager->save($banner);
    }
}
