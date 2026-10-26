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
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Api\Data\TierInterface;
use Mirasvit\Affiliate\Model\Program\Rule\ProgramRule;
use Mirasvit\Affiliate\Service\TierService;
use Mirasvit\Core\Service\SerializeService;

class Program extends AbstractModel implements ProgramInterface
{
    private $ruleFactory;

    private $rule;

    private $tierService;

    public function __construct(
        Program\Rule\ProgramRuleFactory $ruleFactory,
        TierService                     $tierService,
        Context                         $context,
        Registry                        $registry
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->tierService = $tierService;

        parent::__construct($context, $registry);
    }

    protected function _construct(): void
    {
        $this->_init(ResourceModel\Program::class);
    }

    public function getId(): int
    {
        return (int)$this->getData(self::ID);
    }

    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
    }

    public function setName(string $name): ProgramInterface
    {
        return $this->setData(self::NAME, $name);
    }

    public function getDescription(): string
    {
        return (string)$this->getData(self::DESCRIPTION);
    }

    public function setDescription(string $description): ProgramInterface
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    public function getType(): string
    {
        return (string)$this->getData(self::TYPE);
    }

    public function setType(string $type): ProgramInterface
    {
        return $this->setData(self::TYPE, $type);
    }

    public function getIsActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(bool $isActive): ProgramInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    public function getActiveFrom(): ?string
    {
        return $this->getData(self::ACTIVE_FROM) ?: null;
    }

    public function setActiveFrom(string $from = null): ProgramInterface
    {
        return $this->setData(self::ACTIVE_FROM, $from);
    }

    public function getActiveTo(): ?string
    {
        return $this->getData(self::ACTIVE_TO) ?: null;
    }

    public function setActiveTo(string $to = null): ProgramInterface
    {
        return $this->setData(self::ACTIVE_TO, $to);
    }

    public function getIsVisible(): bool
    {
        return (bool)$this->getData(self::IS_VISIBLE);
    }

    public function setIsVisible(bool $isVisible): ProgramInterface
    {
        return $this->setData(self::IS_VISIBLE, $isVisible);
    }

    /**
     * @return TierInterface[]
     */
    public function getTiers(): array
    {
        $data = SerializeService::decode($this->getData(self::TIERS_SERIALIZED)) ?? [];

        return $this->tierService->toTierObjectArray($data);
    }

    /**
     * @param TierInterface[] $value
     *
     * @return ProgramInterface
     */
    public function setTiers(array $value): ProgramInterface
    {
        $data = $this->tierService->toArray($value);

        return $this->setData(self::TIERS_SERIALIZED, SerializeService::encode($data));
    }

    public function setConditionsSerialized(string $value): ProgramInterface
    {
        return $this->setData(self::CONDITIONS_SERIALIZED, $value);
    }

    public function getRule(): ProgramRule
    {
        if (!$this->rule) {
            $this->rule = $this->ruleFactory->create()
                ->setData(self::CONDITIONS_SERIALIZED, $this->getData(self::CONDITIONS_SERIALIZED));
        }

        return $this->rule;
    }

    public function getPriority(): int
    {
        return (int)$this->getData(self::PRIORITY);
    }

    public function setPriority(int $priority): ProgramInterface
    {
        return $this->setData(self::PRIORITY, $priority);
    }

    public function getStoreIds(): array
    {
        return explode(',', $this->getData(self::STORE_IDS));
    }

    public function setStoreIds(array $storeIds): ProgramInterface
    {
        $storeIdsData = implode(',', $storeIds);

        return $this->setData(self::STORE_IDS, $storeIdsData);
    }

    public function getCouponIsActive(): bool
    {
        return (bool)$this->getData(self::COUPON_IS_ACTIVE);
    }

    public function setCouponIsActive(bool $isActive): ProgramInterface
    {
        return $this->setData(self::COUPON_IS_ACTIVE, $isActive);
    }

    public function getCartRuleId(): int
    {
        return (int)$this->getData(self::CART_RULE_ID);
    }

    public function setCartRuleId(int $cartRuleId): ProgramInterface
    {
        return $this->setData(self::CART_RULE_ID, $cartRuleId);
    }

    public function getIsStopProcessing(): bool
    {
        return (bool)$this->getData(self::IS_STOP_PROCESSING);
    }

    public function setIsStopProcessing(bool $isActive): ProgramInterface
    {
        return $this->setData(self::IS_STOP_PROCESSING, $isActive);
    }
}
