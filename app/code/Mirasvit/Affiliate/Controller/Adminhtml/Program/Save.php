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

namespace Mirasvit\Affiliate\Controller\Adminhtml\Program;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Controller\Adminhtml\Program;
use Mirasvit\Affiliate\Repository\ProgramRepository;
use Mirasvit\Affiliate\Service\TierService;

class Save extends Program
{
    private $dataProcessor;

    private $tierService;

    public function __construct(
        PostDataProcessor $dataProcessor,
        TierService $tierService,
        ProgramRepository $programRepository,
        Registry $registry,
        Context $context
    ) {
        $this->dataProcessor = $dataProcessor;
        $this->tierService   = $tierService;

        parent::__construct($programRepository, $registry, $context);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(): ResultInterface
    {
        $id             = $this->getRequest()->getParam(ProgramInterface::ID);
        $resultRedirect = $this->resultRedirectFactory->create();

        $data = $this->getRequest()->getPostValue();

        if ($data) {
            $model = $this->initModel();

            if ($id && !$model) {
                $this->messageManager->addErrorMessage((string)__('This program no longer exists.'));

                return $resultRedirect->setPath('*/*/');
            }

            $data = $this->dataProcessor->filter($model, $data);

            $tiers = $this->tierService->toTierObjectArray((array)$data[ProgramInterface::TIERS]);

            $model->setName((string)$data[ProgramInterface::NAME])
                ->setDescription((string)$data[ProgramInterface::DESCRIPTION])
                ->setType((string)$data[ProgramInterface::TYPE])
                ->setIsActive((bool)$data[ProgramInterface::IS_ACTIVE])
                ->setIsVisible((bool)$data[ProgramInterface::IS_VISIBLE])
                ->setTiers($tiers)
                ->setPriority((int)$data[ProgramInterface::PRIORITY])
                ->setIsStopProcessing((bool)$data[ProgramInterface::IS_STOP_PROCESSING])
                ->setConditionsSerialized((string)$data[ProgramInterface::CONDITIONS_SERIALIZED])
                ->setStoreIds((array)$data[ProgramInterface::STORE_IDS]);

            if ($data[ProgramInterface::ACTIVE_FROM]) {
                $model->setActiveFrom((string)$data[ProgramInterface::ACTIVE_FROM]);
            }

            if ($data[ProgramInterface::ACTIVE_TO]) {
                $model->setActiveTo((string)$data[ProgramInterface::ACTIVE_TO]);
            }

            if (array_key_exists(ProgramInterface::COUPON_IS_ACTIVE, $data)) {
                $model->setCouponIsActive((bool)$data[ProgramInterface::COUPON_IS_ACTIVE]);
            }

            if (array_key_exists(ProgramInterface::CART_RULE_ID, $data)) {
                $model->setCartRuleId((int)$data[ProgramInterface::CART_RULE_ID]);
            }

            try {
                $this->programRepository->save($model);

                $this->messageManager->addSuccessMessage((string)__('You have saved the program.'));

                if ($this->getRequest()->getParam('back') == 'edit') {
                    return $resultRedirect->setPath('*/*/edit', [ProgramInterface::ID => $model->getId()]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());

                return $resultRedirect->setPath('*/*/edit', [ProgramInterface::ID => $id]);
            }
        } else {
            $resultRedirect->setPath('*/*/');
            $this->messageManager->addErrorMessage((string)__('No data to save.'));

            return $resultRedirect;
        }
    }
}
