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
use Mirasvit\Affiliate\Api\Data\GroupInterface;
use Mirasvit\Affiliate\Model\Group\Rule\GroupRule;
use Mirasvit\Affiliate\Model\Group\Rule\GroupRuleFactory;

class Group extends AbstractModel implements GroupInterface
{
    private $ruleFactory;

    /**
     * @var mixed
     */
    private $rule;

    public function __construct(
        GroupRuleFactory $ruleFactory,
        Context $context,
        Registry $registry
    ) {
        $this->ruleFactory = $ruleFactory;
        parent::__construct($context, $registry);
    }

    public function getId(): ?int
    {
        return $this->getData(self::ID) ? (int)$this->getData(self::ID) : null;
    }

    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
    }

    public function setName(string $value): GroupInterface
    {
        return $this->setData(self::NAME, $value);
    }

    public function getIsDefault(): bool
    {
        return (bool)$this->getData(self::IS_DEFAULT);
    }

    public function setIsDefault(bool $value): GroupInterface
    {
        return $this->setData(self::IS_DEFAULT, $value);
    }

    public function getIsAutoUpdate(): bool
    {
        return (bool)$this->getData(self::IS_AUTO_UPDATE);
    }

    public function setIsAutoUpdate(bool $value): GroupInterface
    {
        return $this->setData(self::IS_AUTO_UPDATE, $value);
    }

    public function getConditionsSerialized(): string
    {
        return (string)$this->getData(self::CONDITIONS_SERIALIZED);
    }

    public function setConditionsSerialized(string $value): GroupInterface
    {
        return $this->setData(self::CONDITIONS_SERIALIZED, $value);
    }

    public function getRule(): GroupRule
    {
        if (!$this->rule) {
            $this->rule = $this->ruleFactory->create()
                ->setData(self::CONDITIONS_SERIALIZED, $this->getData(self::CONDITIONS_SERIALIZED));
        }

        return $this->rule;
    }

    public function getDescription(): string
    {
        return (string)$this->getData(self::DESCRIPTION);
    }

    public function setDescription(string $value): GroupInterface
    {
        return $this->setData(self::DESCRIPTION, $value);
    }

    public function getIsVisible(): bool
    {
        return (bool)$this->getData(self::IS_VISIBLE);
    }

    public function setIsVisible(bool $value): GroupInterface
    {
        return $this->setData(self::IS_VISIBLE, $value);
    }

    public function getPriority(): int
    {
        return (int)$this->getData(self::PRIORITY);
    }

    public function setPriority(int $priority): GroupInterface
    {
        return $this->setData(self::PRIORITY, $priority);
    }
}
