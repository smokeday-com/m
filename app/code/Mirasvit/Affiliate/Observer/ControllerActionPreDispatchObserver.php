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


namespace Mirasvit\Affiliate\Observer;

use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Service\EventService;

class ControllerActionPreDispatchObserver implements ObserverInterface
{
    private $eventService;

    private $configProvider;

    private $redirect;

    public function __construct(
        EventService $eventService,
        ConfigProvider $configProvider,
        RedirectInterface $redirect
    ) {
        $this->eventService   = $eventService;
        $this->configProvider = $configProvider;
        $this->redirect       = $redirect;
    }

    public function execute(Observer $observer): void
    {
        $controller = $observer->getControllerAction();

        if (!$this->configProvider->isEnabled()) {
            $this->redirect->redirect($controller->getResponse(), 'customer/account/');
        }
    }
}
