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

use Mirasvit\Affiliate\Model\Program\Rule\ProgramRule;

interface ProgramInterface
{
    public const TABLE_NAME = 'mst_affiliate_program';

    public const TYPE_PAY_PER_SALE       = 'sale';
    public const TYPE_PAY_PER_LEAD       = 'lead';
    public const TYPE_PAY_PER_IMPRESSION = 'impression';
    public const TYPE_PAY_PER_CLICK      = 'click';

    public const COMMISSION_TYPE_PERCENT_TOTAL    = 'percent_total';
    public const COMMISSION_TYPE_PERCENT_SUBTOTAL = 'percent_subtotal';
    public const COMMISSION_TYPE_FIXED            = 'fixed';

    public const ID          = 'program_id';
    public const NAME        = 'name';
    public const DESCRIPTION = 'description';
    public const TYPE        = 'type';
    public const IS_ACTIVE   = 'is_active';
    public const ACTIVE_FROM = 'active_from';
    public const ACTIVE_TO   = 'active_to';
    public const IS_VISIBLE  = 'is_visible';

    public const IS_STOP_PROCESSING = 'is_stop_processing';

    public const PRIORITY              = 'priority';
    public const TIERS                 = 'tiers';
    public const TIERS_SERIALIZED      = 'tiers_serialized';
    public const CONDITIONS_SERIALIZED = 'conditions_serialized';
    public const STORE_IDS             = 'store_ids';
    public const COUPON_IS_ACTIVE      = 'coupon_is_active';
    public const CART_RULE_ID          = 'cart_rule_id';

    public function getId(): int;

    public function getName(): string;

    public function setName(string $name): self;

    public function getDescription(): string;

    public function setDescription(string $description): self;

    public function getType(): string;

    public function setType(string $type): self;

    public function getIsActive(): bool;

    public function setIsActive(bool $isActive): self;

    public function getActiveFrom(): ?string;

    public function setActiveFrom(string $from = null): self;

    public function getActiveTo(): ?string;

    public function setActiveTo(string $to = null): self;

    public function getIsVisible(): bool;

    public function setIsVisible(bool $isVisible): self;

    /** @return TierInterface[] */
    public function getTiers(): array;

    public function setTiers(array $value): self;

    public function setConditionsSerialized(string $value): self;

    public function getRule(): ProgramRule;

    public function getPriority(): int;

    public function setPriority(int $priority): self;

    public function getStoreIds(): array;

    public function setStoreIds(array $storeIds): self;

    public function getCouponIsActive(): bool;

    public function setCouponIsActive(bool $isActive): self;

    public function getCartRuleId(): int;

    public function setCartRuleId(int $cartRuleId): self;

    public function getIsStopProcessing(): bool;

    public function setIsStopProcessing(bool $isActive): self;
}
