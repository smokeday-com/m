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

namespace Mirasvit\Affiliate\Controller\Adminhtml\Account;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Mirasvit\Affiliate\Api\Data\AccountCustomerInterface;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Controller\Adminhtml\Account;
use Mirasvit\Affiliate\Repository\AccountCustomerRepository;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Repository\GroupRepository;
use Mirasvit\Affiliate\Service\AccountService;
use Mirasvit\Core\Service\SerializeService;

class Save extends Account
{
    private $accountService;

    private $accountCustomerRepository;

    private $groupRepository;

    public function __construct(
        AccountService            $accountService,
        AccountCustomerRepository $accountCustomerRepository,
        GroupRepository           $groupRepository,
        AccountRepository         $accountRepository,
        Context                   $context
    ) {
        $this->accountService            = $accountService;
        $this->accountCustomerRepository = $accountCustomerRepository;
        $this->groupRepository           = $groupRepository;

        parent::__construct($accountRepository, $context);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(): ResultInterface
    {
        $id             = $this->getRequest()->getParam(AccountInterface::ID);
        $resultRedirect = $this->resultRedirectFactory->create();
        $data           = $this->getRequest()->getPostValue();

        if ($data) {
            $model = $this->initModel();

            if ($id && !$model) {
                $this->messageManager->addErrorMessage((string)__('This account no longer exists.'));

                return $resultRedirect->setPath('*/*/');
            }

            if (!isset($data[AccountInterface::PROGRAM_IDS])) {
                $data[AccountInterface::PROGRAM_IDS] = [];
            }

            $groupId = $model->getGroupId();

            if (isset($data[AccountInterface::GROUP_ID]) && $model->getGroupId() !== (int)$data[AccountInterface::GROUP_ID]) {
                $defaultGroup   = $this->groupRepository->getDefaultGroup();
                $manualGroupIds = $this->groupRepository->getManualUpdateGroupsIds();

                if ($defaultGroup->getId() === (int)$data[AccountInterface::GROUP_ID]
                    || in_array($data[AccountInterface::GROUP_ID], $manualGroupIds)
                ) {
                    $groupId = (int)$data[AccountInterface::GROUP_ID];
                } else {
                    $this->messageManager->addErrorMessage(
                        (string)__('Affiliate can be manually assigned only to manual updating or default affiliate group.')
                    );

                    return $resultRedirect->setPath('*/*/edit', [AccountInterface::ID => $id]);
                }
            }

            $model->setIdentifier((string)$data[AccountInterface::IDENTIFIER])
                ->setIsSubscribed((bool)$data[AccountInterface::IS_SUBSCRIBED])
                ->setStatus((int)$data[AccountInterface::STATUS])
                ->setPaymentMethod((string)$data[AccountInterface::PAYMENT_METHOD])
                ->setPaymentEmail((string)$data[AccountInterface::PAYMENT_EMAIL])
                ->setProgramIds((array)$data[AccountInterface::PROGRAM_IDS])
                ->setGroupId($groupId);

            $additionalData = $this->getRequest()->getParam(AccountInterface::ADDITIONAL_DATA_SERIALIZED);

            if (is_array($additionalData)) {
                $model->setAdditionalDataSerialized(SerializeService::encode($additionalData));
            }

            try {
                $this->accountService->filterMissingProgram($model);
                $this->accountRepository->save($model);
                $this->saveAccountCustomerRelation($model, $data);

                $this->messageManager->addSuccessMessage((string)__('Account was saved.'));

                if ($this->getRequest()->getParam('back') == 'edit') {
                    return $resultRedirect->setPath('*/*/edit', [AccountInterface::ID => $model->getId()]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());

                return $resultRedirect->setPath('*/*/edit', [AccountInterface::ID => $id]);
            }
        } else {
            $resultRedirect->setPath('*/*/');
            $this->messageManager->addErrorMessage((string)__('No data to save.'));

            return $resultRedirect;
        }
    }

    private function saveAccountCustomerRelation(AccountInterface $account, array $data): bool
    {
        if (!isset($data['account_customer'])) {
            return false;
        }
        $customerIds = [];
        foreach ($data['account_customer'] as $row) {
            $customerIds[] = (int)$row['id'];
        }

        // remove old relations
        $collection = $this->accountCustomerRepository->getCollection();
        $collection->addFieldToFilter(AccountCustomerInterface::ACCOUNT_ID, $account->getId())
            ->addFieldToFilter(AccountCustomerInterface::CUSTOMER_ID, ['nin' => array_merge($customerIds, [0])]);

        foreach ($collection as $relation) {
            $this->accountCustomerRepository->delete($relation);
        }

        // add new
        $collection = $this->accountCustomerRepository->getCollection();
        $collection->addFieldToFilter(AccountCustomerInterface::ACCOUNT_ID, $account->getId())
            ->addFieldToFilter(AccountCustomerInterface::CUSTOMER_ID, ['in' => array_merge($customerIds, [0])]);
        $alreadyAddedIds = $collection->getColumnValues(AccountCustomerInterface::CUSTOMER_ID);

        foreach ($customerIds as $id) {
            if (!in_array($id, $alreadyAddedIds) && $account->getCustomerId() != $id) {
                $relation = $this->accountCustomerRepository->create();
                $relation->setCustomerId((int)$id)
                    ->setAccountId((int)$account->getId());

                $this->accountCustomerRepository->save($relation);
            }
        }

        return true;
    }
}
