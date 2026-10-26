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

use Mirasvit\Affiliate\Model\Group\Rule\GroupRule;

interface GroupInterface
{
    public const TABLE_NAME = 'mst_affiliate_group';

    public const ID                    = 'group_id';
    public const NAME                  = 'name';
    public const IS_DEFAULT            = 'is_default';
    public const IS_AUTO_UPDATE        = 'is_auto_update';
    public const CONDITIONS_SERIALIZED = 'conditions_serialized';
    public const DESCRIPTION           = 'description';
    public const IS_VISIBLE            = 'is_visible';
    public const PRIORITY              = 'priority';

    public function getId(): ?int;

    public function getName(): string;

    public function setName(string $value): self;

    public function getIsDefault(): bool;

    public function setIsDefault(bool $value): self;

    public function getIsAutoUpdate(): bool;

    public function setIsAutoUpdate(bool $value): self;

    public function getConditionsSerialized(): string;

    public function setConditionsSerialized(string $value): self;

    public function getRule(): GroupRule;

    public function getDescription(): string;

    public function setDescription(string $value): self;

    public function getIsVisible(): bool;

    public function setIsVisible(bool $value): self;

    public function getPriority(): int;

    public function setPriority(int $priority): self;
}
