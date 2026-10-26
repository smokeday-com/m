<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/* Customize Images Modal on Product Edit page to add a new checkbox */
namespace Olegnax\Athlete2\Observer;

use Magento\Framework\Event\ObserverInterface;

class ProductGalleryChangeTemplateObserver implements ObserverInterface
{
    /**
     * @param mixed $observer
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $observer->getBlock()->setTemplate('Olegnax_Athlete2::helper/gallery.phtml');
    }
}
