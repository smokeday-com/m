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



namespace Mirasvit\Affiliate\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Repository\GroupRepository;
use Mirasvit\Affiliate\Repository\ProgramRepository;
use Mirasvit\Affiliate\Service\TierService;

class DataPatch1010 implements DataPatchInterface, PatchVersionInterface
{
    private $setup;

    private $groupRepository;

    private $accountRepository;

    private $programRepository;

    private $tierService;

    public function __construct(
        ModuleDataSetupInterface $setup,
        GroupRepository          $groupRepository,
        AccountRepository        $accountRepository,
        ProgramRepository        $programRepository,
        TierService              $tierService
    ) {
        $this->setup             = $setup;
        $this->groupRepository   = $groupRepository;
        $this->accountRepository = $accountRepository;
        $this->programRepository = $programRepository;
        $this->tierService       = $tierService;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->setup->getConnection()->startSetup();
        $setup = $this->setup;

        $defaultGroup = $this->groupRepository->create();

        $defaultGroup->setName('Default group')
            ->setIsDefault(true)
            ->setIsAutoUpdate(true)
            ->setIsVisible(true);

        $this->groupRepository->save($defaultGroup);

        $accounts = $this->accountRepository->getCollection();

        foreach ($accounts as $account) {
            $account->setGroupId($defaultGroup->getId());
            $this->accountRepository->save($account);
        }

        $programs = $this->programRepository->getCollection();

        foreach ($programs as $program) {
            $tiers = $this->tierService->toArray($program->getTiers());
            if (!isset($tiers[0])) {
                continue;
            }

            $tier             = $tiers[0];
            $tier['group_id'] = $defaultGroup->getId();

            if ($program->getType() === ProgramInterface::TYPE_PAY_PER_SALE && isset($tier['amount'])) {
                $amount           = $tier['amount'];
                $tier['amount_1'] = $amount;
                $tier['amount_n'] = $amount;
                unset($tier['amount']);
            }

            $newTiers = $this->tierService->toTierObjectArray([$tier]);

            $program->setTiers($newTiers);
            $this->programRepository->save($program);
        }

        $this->setup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion(): string
    {
        return '1.0.10';
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [DataPatch100::class];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
