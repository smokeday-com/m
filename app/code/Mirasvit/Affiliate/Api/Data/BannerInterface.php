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

namespace Mirasvit\Affiliate\Api\Data;

interface BannerInterface
{
    public const TABLE_NAME = 'mst_affiliate_banner';

    public const TYPE_IMAGE = 'image';
    public const TYPE_TEXT  = 'text';

    public const ID        = 'banner_id';
    public const TITLE     = 'title';
    public const TYPE      = 'type';
    public const SOURCE    = 'source';
    public const WIDTH     = 'width';
    public const HEIGHT    = 'height';
    public const LINK      = 'link';
    public const IS_ACTIVE = 'is_active';
    public const STORE_IDS = 'store_ids';

    public function getId(): ?int;

    public function getTitle(): string;

    public function setTitle(string $title): self;

    public function getType(): string;

    public function setType(string $type): self;

    public function getSource(): string;

    public function setSource(string $source): self;

    public function getWidth(): ?int;

    public function setWidth(int $width = null): self;

    public function getHeight(): ?int;

    public function setHeight(int $height): self;

    public function getLink(): string;

    public function setLink(string $link): self;

    public function getIsActive(): bool;

    public function setIsActive(bool $isActive): self;

    public function getStoreIds(): array;

    public function setStoreIds(array $value): self;
}
