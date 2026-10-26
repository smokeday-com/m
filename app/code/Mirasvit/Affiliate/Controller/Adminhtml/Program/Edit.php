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
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Controller\Adminhtml\Program;
use Mirasvit\Affiliate\Repository\ProgramRepository;
use Mirasvit\Affiliate\Service\Coupon\SalesRuleService;
use Mirasvit\Affiliate\Service\ProgramService;

class Edit extends Program
{
    private $programService;

    private $salesRuleService;

    public function __construct(
        SalesRuleService  $salesRuleService,
        ProgramService    $programService,
        ProgramRepository $accountRepository,
        Registry          $registry,
        Context           $context
    ) {
        parent::__construct($accountRepository, $registry, $context);
        $this->programService   = $programService;
        $this->salesRuleService = $salesRuleService;
    }

    public function execute(): ResultInterface
    {
        /** @var \Magento\Backend\Model\View\Result\Page\Interceptor $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $id    = (int)$this->getRequest()->getParam(ProgramInterface::ID);
        $model = $this->initModel();

        if ($id) {
            if (!$model) {
                $this->messageManager->addErrorMessage((string)__('This program no longer exists.'));

                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }

            if ($model->getCartRuleId() && $model->getType() == ProgramInterface::TYPE_PAY_PER_SALE) {
                $program = $this->programRepository->get($id);
                if (!$this->programService->verifyProgram($program)) {
                    $this->messageManager->addErrorMessage((string)__('Cart Price Rule and Store View must belong to the same website'));
                }

                if ($model->getCartRuleId() && !$this->salesRuleService->verifyRule($model->getCartRuleId())) {
                    $this->messageManager->addErrorMessage((string)__('Cart Price Rule must use autogeneration and Specific Coupon'));
                }
            }

            $model->getRule()->getConditions()->setJsFormObject('config_conditions_fieldset');
        }

        $this->initPage($resultPage)->getConfig()->getTitle()
            ->prepend($id ? $model->getName() : (string)__('New Program'));

        return $resultPage;
    }
}
