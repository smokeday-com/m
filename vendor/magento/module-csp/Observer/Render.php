<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Csp\Observer;

use Magento\Csp\Api\CspRendererInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Response\HttpInterface as HttpResponse;

/**
 * Adds CSP rendering after HTTP response is generated.
 */
class Render implements ObserverInterface
{
    /**
     * @var CspRendererInterface
     */
    private $cspRenderer;

    /**
     * @param CspRendererInterface $cspRenderer
     */
    public function __construct(CspRendererInterface $cspRenderer)
    {
        $this->cspRenderer = $cspRenderer;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Skip checkout-only Signifyd and Vault config providers on /checkout/cart/ in CompositeConfigProvider and skip Magento_Csp header rendering on AJAX/POST requests":
        # https://github.com/smokeday-com/m/issues/25
        $request = \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magento\Framework\App\RequestInterface::class
        );
        if (
            (method_exists($request, 'isXmlHttpRequest') && $request->isXmlHttpRequest())
            || (method_exists($request, 'isPost') && $request->isPost())
        ) {
            return;
        }
        /** @var HttpResponse $response */
        $response = $observer->getEvent()->getData('response');

        $this->cspRenderer->render($response);
    }
}
