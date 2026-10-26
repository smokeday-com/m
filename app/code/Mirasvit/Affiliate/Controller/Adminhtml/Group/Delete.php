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

namespace Mirasvit\Affiliate\Controller\Adminhtml\Group;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Mirasvit\Affiliate\Api\Data\GroupInterface;
use Mirasvit\Affiliate\Controller\Adminhtml\Group;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Repository\GroupRepository;
use Mirasvit\Affiliate\Repository\ProgramRepository;

class Delete extends Group
{
    private $accountRepository;

    private $programRepository;

    public function __construct(
        AccountRepository $accountRepository,
        ProgramRepository $programRepository,
        GroupRepository $groupRepository,
        Registry $registry,
        Context $context
    ) {
        $this->accountRepository = $accountRepository;
        $this->programRepository = $programRepository;

        parent::__construct($groupRepository, $registry, $context);
    }

    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $model = $this->initModel();

        if (!$model) {
            $this->messageManager->addErrorMessage((string)__('This affiliate group no longer exists.'));

            return $resultRedirect->setPath('*/*/');
        }

        if ($model->getIsDefault()) {
            $autoUpdateGroups = $this->groupRepository
                ->getAutoUpdateGroupsCollection()
                ->addFieldToFilter(GroupInterface::ID, ['neq' => $model->getId()]);

            if (!$autoUpdateGroups->count()) {
                $this->messageManager->addErrorMessage((string)__('At least 1 auto-updating group should be present to be used as the default group'));
                return $resultRedirect->setPath('*/*/');
            }

            $newDefaultGroup = $autoUpdateGroups->getFirstItem();

            $newDefaultGroup->setIsDefault(true);
            $this->groupRepository->save($newDefaultGroup);
        }

        try {
            $this->groupRepository->delete($model);

            $accountsToUpdate = $this->accountRepository->getByGroupId($model->getId());
            $defaultGroup     = $this->groupRepository->getDefaultGroup();

            foreach ($accountsToUpdate as $account) {
                $account->setGroupId($defaultGroup->getId());
                $this->accountRepository->save($account);
            }

            $this->updatePrograms($model);

            $this->messageManager->addSuccessMessage((string)__('Affiliate Group has been removed.'));

            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return $resultRedirect->setPath('*/*/');
        }
    }

    private function updatePrograms(GroupInterface $group): void
    {
        $programs = $this->programRepository->getCollection();

        foreach ($programs as $program) {
            $tiers = $program->getTiers();

            foreach ($tiers as $idx => $tier) {
                if ($group->getId() === $tier->getGroupId()) {
                    unset($tiers[$idx]);
                }
            }

            foreach ($tiers as $idx => $tier) {
                $tier->setRecordId((int)$idx);
            }

            $program->setTiers($tiers);
            $this->programRepository->save($program);
        }
    }
}
