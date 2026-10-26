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
use Mirasvit\Affiliate\Api\Data\BannerInterface;

class Banner extends AbstractModel implements BannerInterface
{
    protected function _construct(): void
    {
        $this->_init(\Mirasvit\Affiliate\Model\ResourceModel\Banner::class);
    }

    public function getId(): ?int
    {
        return $this->getData(self::ID)
            ? (int)$this->getData(self::ID)
            : null;
    }

    public function getTitle(): string
    {
        return (string)$this->getData(self::TITLE);
    }

    public function setTitle(string $title): BannerInterface
    {
        return $this->setData(self::TITLE, $title);
    }

    public function getType(): string
    {
        return (string)$this->getData(self::TYPE);
    }

    public function setType(string $type): BannerInterface
    {
        return $this->setData(self::TYPE, $type);
    }

    public function getSource(): string
    {
        return (string)$this->getData(self::SOURCE);
    }

    public function setSource(string $source): BannerInterface
    {
        return $this->setData(self::SOURCE, $source);
    }

    public function getWidth(): ?int
    {
        return $this->getData(self::WIDTH)
            ? (int)$this->getData(self::WIDTH)
            : null;
    }

    public function setWidth(int $width = null): BannerInterface
    {
        return $this->setData(self::WIDTH, $width);
    }

    public function getHeight(): ?int
    {
        return $this->getData(self::HEIGHT)
            ? (int)$this->getData(self::HEIGHT)
            : null;
    }

    public function setHeight(int $height = null): BannerInterface
    {
        return $this->setData(self::HEIGHT, $height);
    }

    public function getLink(): string
    {
        return (string)$this->getData(self::LINK);
    }

    public function setLink(string $link): BannerInterface
    {
        return $this->setData(self::LINK, $link);
    }

    public function getIsActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(bool $isActive): BannerInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    public function getStoreIds(): array
    {
        return explode(',', $this->getData(self::STORE_IDS));
    }

    public function setStoreIds(array $storeIds): BannerInterface
    {
        $storeIdsData = implode(',', $storeIds);
        return $this->setData(self::STORE_IDS, $storeIdsData);
    }
}
