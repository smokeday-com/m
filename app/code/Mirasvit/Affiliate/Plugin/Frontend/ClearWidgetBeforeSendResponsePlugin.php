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

namespace Mirasvit\Affiliate\Plugin\Frontend;

use Magento\Framework\App\RequestInterface;
use Mirasvit\Affiliate\Service\WidgetService;

/**
 * @see \Magento\Framework\App\Response\Http::sendResponse()
 */
class ClearWidgetBeforeSendResponsePlugin
{
    private $request;

    private $widgetService;

    public function __construct(
        RequestInterface $request,
        WidgetService    $widgetService
    ) {
        $this->request       = $request;
        $this->widgetService = $widgetService;
    }

    /**
     * @param mixed|\Magento\Framework\App\Response\Http $response
     */
    public function beforeSendResponse($response)
    {
        $fullActionName = implode(
            '_',
            [
                $this->request->getRouteName(),
                $this->request->getControllerName(),
                $this->request->getActionName(),
            ]
        );

        if ($fullActionName == 'affiliate_widget_view') {
            $content = $this->widgetService->clearHtml($response->getContent());
            if ($content) {
                $response->setBody($content);
            }
        }
    }
}
