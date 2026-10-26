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

namespace Mirasvit\Affiliate\Controller\Adminhtml\Banner;

use Magento\Framework\Controller\ResultInterface;
use Mirasvit\Affiliate\Api\Data\BannerInterface;
use Mirasvit\Affiliate\Controller\Adminhtml\Banner;
use Magento\Backend\App\Action\Context;
use Mirasvit\Affiliate\Repository\BannerRepository;

class Save extends Banner
{
    private $dataProcessor;

    public function __construct(
        PostDataProcessor $postDataProcessor,
        BannerRepository $bannerRepository,
        Context $context
    ) {
        $this->dataProcessor = $postDataProcessor;

        parent::__construct($bannerRepository, $context);
    }

    /**
     * @SuppressWarnings(PHPMD)
     */
    public function execute(): ResultInterface
    {
        $id             = $this->getRequest()->getParam(BannerInterface::ID);
        $resultRedirect = $this->resultRedirectFactory->create();

        $data = $this->getRequest()->getPostValue();

        if ($data) {
            $model = $this->initModel();

            if ($id && !$model) {
                $this->messageManager->addErrorMessage((string)__('This banner no longer exists.'));

                return $resultRedirect->setPath('*/*/');
            }

            $data = $this->dataProcessor->filter($data);

            $model->setType((string)$data[BannerInterface::TYPE])
                ->setTitle((string)$data[BannerInterface::TITLE])
                ->setSource((string)$data[BannerInterface::SOURCE])
                ->setIsActive((bool)$data[BannerInterface::IS_ACTIVE])
                ->setLink((string)$data[BannerInterface::LINK])
                ->setWidth($data[BannerInterface::WIDTH] ? (int)$data[BannerInterface::WIDTH] : null)
                ->setHeight($data[BannerInterface::HEIGHT] ? (int)$data[BannerInterface::HEIGHT] : null)
                ->setStoreIds((array)$data[BannerInterface::STORE_IDS]);

            try {
                $this->bannerRepository->save($model);

                $this->messageManager->addSuccessMessage((string)__('Banner has been saved.'));

                if ($this->getRequest()->getParam('back') == 'edit') {
                    return $resultRedirect->setPath('*/*/edit', [BannerInterface::ID => $model->getId()]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());

                return $resultRedirect->setPath('*/*/edit', [BannerInterface::ID => $id]);
            }
        } else {
            $resultRedirect->setPath('*/*/');
            $this->messageManager->addErrorMessage((string)__('No data to save.'));

            return $resultRedirect;
        }
    }
}
