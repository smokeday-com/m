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

class Save extends Group
{
    private $dataProcessor;

    private $accountRepository;

    public function __construct(
        PostDataProcessor $dataProcessor,
        AccountRepository $accountRepository,
        GroupRepository   $groupRepository,
        Registry          $registry,
        Context           $context
    ) {
        $this->dataProcessor     = $dataProcessor;
        $this->accountRepository = $accountRepository;

        parent::__construct($groupRepository, $registry, $context);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(): ResultInterface
    {
        $id             = $this->getRequest()->getParam(GroupInterface::ID);
        $resultRedirect = $this->resultRedirectFactory->create();
        $data           = $this->getRequest()->getParams();

        if ($data) {
            $model = $this->initModel();

            if ($id && !$model) {
                $this->messageManager->addErrorMessage((string)__('This affiliate group no longer exists.'));

                return $resultRedirect->setPath('*/*/');
            }

            $data = $this->dataProcessor->filter($model, $data);

            $model->setName((string)$data[GroupInterface::NAME])
                ->setIsDefault((bool)$data[GroupInterface::IS_DEFAULT])
                ->setIsAutoUpdate((bool)$data[GroupInterface::IS_AUTO_UPDATE])
                ->setConditionsSerialized((string)$data[GroupInterface::CONDITIONS_SERIALIZED])
                ->setIsVisible((bool)$data[GroupInterface::IS_VISIBLE])
                ->setPriority((int)$data[GroupInterface::PRIORITY])
                ->setDescription((string)$data[GroupInterface::DESCRIPTION]);

            try {
                $this->groupRepository->save($model);
                $this->resolveDefaultGroup($model);

                if (isset($data['links']['assigned_affiliates']) && !$model->getIsAutoUpdate()) {
                    $this->unassignRemovedAccounts($model, $data['links']['assigned_affiliates']);
                    $this->updateAccounts($model, $data['links']['assigned_affiliates']);
                }

                $this->messageManager->addSuccessMessage((string)__('You have saved the affiliate group.'));

                if ($this->getRequest()->getParam('back') == 'edit') {
                    return $resultRedirect->setPath('*/*/edit', [GroupInterface::ID => $model->getId()]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());

                return $resultRedirect->setPath('*/*/edit', [GroupInterface::ID => $id]);
            }
        } else {
            $resultRedirect->setPath('*/*/');
            $this->messageManager->addErrorMessage((string)__('No data to save.'));

            return $resultRedirect;
        }
    }

    private function resolveDefaultGroup(GroupInterface $model): void
    {
        $groups = $this->groupRepository->getAutoUpdateGroupsCollection();

        if ($groups->count() === 0 && !$model->getIsAutoUpdate()) {
            $model->setIsAutoUpdate(true);
            $this->groupRepository->save($model);
            $this->messageManager->addWarningMessage((string)__('At least 1 auto-updating group should be present to be used as the default group'));
        }

        if ($model->getIsDefault() && !$model->getIsAutoUpdate()) {
            $model->setIsDefault(false);
            $this->groupRepository->save($model);
            $this->messageManager->addWarningMessage((string)__('Manual updating group can\'t be set as default'));
        }

        $defaultModel = $this->groupRepository->getDefaultGroup();

        if (!$defaultModel) {
            $newDefaultModel = $this->groupRepository->getAutoUpdateGroupsCollection()->getFirstItem();

            $newDefaultModel->setIsDefault(true);
            $this->groupRepository->save($newDefaultModel);
        }

        if ($model->getIsDefault()) {
            $this->resetDefaultGroup($model);
        }
    }

    private function resetDefaultGroup(GroupInterface $model): void
    {
        foreach ($this->groupRepository->getAutoUpdateGroupsCollection() as $group) {
            if ($group->getIsDefault() && $group->getId() !== $model->getId()) {
                $group->setIsDefault(false);
                $this->groupRepository->save($group);
                $this->messageManager->addSuccessMessage((string)__('Default group has been changed'));
            }
        }
    }

    private function updateAccounts(GroupInterface $group, array $accounts): void
    {
        foreach ($accounts as $accountData) {
            $account = $this->accountRepository->get((int)$accountData['id']);
            $account->setGroupId($group->getId());

            $this->accountRepository->save($account);
        }
    }

    private function unassignRemovedAccounts(GroupInterface $group, array $requestAccounts): void
    {
        $requestIds = [];
        foreach ($requestAccounts as $accountData) {
            $requestIds[] = (int)$accountData['id'];
        }

        $currentIds = [];
        foreach ($this->accountRepository->getByGroupId($group->getId()) as $account) {
            $currentIds[] = (int)$account->getId();
        }
        $unassignIds = array_diff($currentIds, $requestIds);
        $unassignIds = array_filter($unassignIds);

        foreach ($unassignIds as $id) {
            $account = $this->accountRepository->get((int)$id);
            $account->setGroupId(null);
            $this->accountRepository->save($account);
        }
    }
}
