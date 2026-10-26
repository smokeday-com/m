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

namespace Mirasvit\Affiliate\Block\Widget;

use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\GroupInterface;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Api\Data\TierInterface;
use Mirasvit\Affiliate\Model\Config\Source\Program\TypeSource as ProgramTypeSource;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Repository\GroupRepository;
use Mirasvit\Affiliate\Repository\ProgramRepository;
use Mirasvit\Affiliate\Service\SessionService;
use Mirasvit\Affiliate\Service\TierService;

class Programs extends Template implements BlockInterface
{
    protected $_template = 'Mirasvit_Affiliate::widget/programs.phtml';

    private $groupRepository;

    private $programRepository;

    private $configProvider;

    private $sessionService;

    private $programTypeSource;

    private $tierService;

    private $pricingHelper;

    public function __construct(
        GroupRepository   $groupRepository,
        ProgramRepository $programRepository,
        ConfigProvider    $configProvider,
        SessionService    $sessionService,
        ProgramTypeSource $programTypeSource,
        TierService       $tierService,
        PricingHelper     $pricingHelper,
        Template\Context  $context,
        array             $data
    ) {
        $this->groupRepository   = $groupRepository;
        $this->programRepository = $programRepository;
        $this->configProvider    = $configProvider;
        $this->sessionService    = $sessionService;
        $this->tierService       = $tierService;
        $this->programTypeSource = $programTypeSource;
        $this->pricingHelper     = $pricingHelper;

        parent::__construct($context, $data);
    }

    /** @return ProgramInterface[] */
    public function getList(): array
    {
        $collection = $this->programRepository->getCollection()
            ->addActiveFilter()
            ->addStoreFilter()
            ->setOrder(ProgramInterface::PRIORITY);

        if ($this->getAccount()) {
            $assignedPrograms = $this->getAccount()->getProgramIds();

            if (count($assignedPrograms)) {
                $collection->addFieldToFilter(
                    [ProgramInterface::ID, ProgramInterface::IS_VISIBLE],
                    [$assignedPrograms, true]
                );

                return $collection->getItems();
            }
        }

        $collection->addFieldToFilter(ProgramInterface::IS_VISIBLE, true);

        return $collection->getItems();
    }

    public function getConfig(): ConfigProvider
    {
        return $this->configProvider;
    }

    public function getAccount(): ?AccountInterface
    {
        $account = $this->sessionService->getAccount();

        return $account && $account->getId() ? $account : null;
    }

    public function getDefaultGroup(): GroupInterface
    {
        return $this->groupRepository->getDefaultGroup();
    }

    public function getGroup(int $groupId): GroupInterface
    {
        return $this->groupRepository->get($groupId);
    }

    public function getProgramTypeLabel(ProgramInterface $program): string
    {
        $types = $this->programTypeSource->toOptionArray();

        foreach ($types as $type) {
            if ($program->getType() === $type['value']) {
                return (string)$type['label'];
            }
        }

        return '';
    }

    public function getTierAmount(TierInterface $tier, int $eventsCount = null): float
    {
        return $this->tierService->getAmount($tier, $eventsCount);
    }

    public function getTierAmountFirst(TierInterface $tier): float
    {
        return $this->tierService->getAmount($tier, 0);
    }

    public function getTierAmountOther(TierInterface $tier): float
    {
        return $this->tierService->getAmount($tier, 1);
    }

    public function getCurrencyFormatted(float $amount): string
    {
        return (string)$this->pricingHelper->currency($amount, true, false);
    }
}
