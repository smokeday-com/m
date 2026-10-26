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

namespace Mirasvit\Affiliate\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Mirasvit\Affiliate\Api\Data\BannerInterface;
use Mirasvit\Affiliate\Repository\BannerRepository;

abstract class Banner extends Action
{
    protected $bannerRepository;

    private $context;

    public function __construct(
        BannerRepository $accountRepository,
        Context $context
    ) {
        $this->bannerRepository = $accountRepository;
        $this->context          = $context;

        parent::__construct($context);
    }

    protected function initPage(Page $resultPage): Page
    {
        $resultPage->setActiveMenu('Magento_Backend::marketing');
        $resultPage->getConfig()->getTitle()->prepend((string)__('Affiliates'));
        $resultPage->getConfig()->getTitle()->prepend((string)__('Banners'));

        return $resultPage;
    }

    public function initModel(): BannerInterface
    {
        $model = $this->bannerRepository->create();

        if ($this->getRequest()->getParam(BannerInterface::ID)) {
            $model = $this->bannerRepository->get((int)$this->getRequest()->getParam(BannerInterface::ID));
        }

        return $model;
    }

    protected function _isAllowed(): bool
    {
        return $this->context->getAuthorization()->isAllowed('Mirasvit_Affiliate::affiliate_banner');
    }
}
