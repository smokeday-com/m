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
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Mirasvit\Affiliate\Api\Data\GroupInterface;
use Mirasvit\Affiliate\Controller\Adminhtml\Group;
use Mirasvit\Affiliate\Repository\GroupRepository;

class Edit extends Group
{
    public function execute(): ResultInterface
    {
        /** @var \Magento\Backend\Model\View\Result\Page\Interceptor $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $id    = (int)$this->getRequest()->getParam(GroupInterface::ID);
        $model = $this->initModel();

        if ($id) {
            if (!$model) {
                $this->messageManager->addErrorMessage((string)__('This affiliate group no longer exists.'));

                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $this->initPage($resultPage)->getConfig()->getTitle()
            ->prepend($id ? $model->getName() : (string)__('New Affiliate Group'));

        return $resultPage;
    }
}
