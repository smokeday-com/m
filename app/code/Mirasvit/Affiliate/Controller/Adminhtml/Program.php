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
use Magento\Framework\Registry;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Repository\ProgramRepository;

abstract class Program extends Action
{
    protected $programRepository;

    private $registry;

    protected $context;

    public function __construct(
        ProgramRepository $accountRepository,
        Registry $registry,
        Context $context
    ) {
        $this->programRepository = $accountRepository;
        $this->registry          = $registry;
        $this->context           = $context;

        parent::__construct($context);
    }

    protected function initPage(Page $resultPage): Page
    {
        $resultPage->setActiveMenu('Magento_Backend::marketing');
        $resultPage->getConfig()->getTitle()->prepend((string)__('Affiliates'));
        $resultPage->getConfig()->getTitle()->prepend((string)__('Programs'));

        return $resultPage;
    }

    public function initModel(): ProgramInterface
    {
        $model = $this->programRepository->create();

        if ($this->getRequest()->getParam(ProgramInterface::ID)) {
            $model = $this->programRepository->get((int)$this->getRequest()->getParam(ProgramInterface::ID));
        }

        $this->registry->register(ProgramInterface::class, $model);

        return $model;
    }

    protected function _isAllowed(): bool
    {
        return $this->context->getAuthorization()->isAllowed('Mirasvit_Affiliate::affiliate_program');
    }
}
