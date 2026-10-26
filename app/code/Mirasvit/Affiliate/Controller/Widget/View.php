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

namespace Mirasvit\Affiliate\Controller\Widget;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Repository\BannerRepository;
use Mirasvit\Affiliate\Service\EventService;

class View extends Action
{
    private $bannerRepository;

    private $accountRepository;

    private $configProvider;

    private $eventManagement;

    public function __construct(
        ConfigProvider $configProvider,
        AccountRepository $accountRepository,
        BannerRepository $bannerRepository,
        EventService $eventManagement,
        Context $context
    ) {
        $this->configProvider    = $configProvider;
        $this->bannerRepository  = $bannerRepository;
        $this->accountRepository = $accountRepository;
        $this->eventManagement   = $eventManagement;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        return $resultPage;
    }
}
