<?php

namespace Macrew\StockLogger\Plugin;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Magento\Framework\App\State;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\App\RequestInterface;

class ProductResourceLogger
{
    protected $logger;
    protected $appState;
    protected $adminSession;
    protected $request;

    public function __construct(
        State $appState,
        AdminSession $adminSession,
        RequestInterface $request
    ) {
        $this->appState = $appState;
        $this->adminSession = $adminSession;
        $this->request = $request;

        $this->logger = new Logger('status_write');
        $this->logger->pushHandler(
            new StreamHandler(BP . '/var/log/status_write.log', Logger::DEBUG)
        );
    }

    public function beforeSave(
        \Magento\Catalog\Model\ResourceModel\Product $subject,
        \Magento\Catalog\Model\Product $product
    ) {
        /*if (!$product->dataHasChangedFor('status')) {
            return;
        }

        $area = 'UNKNOWN';

        try {
            $area = $this->appState->getAreaCode();
        } catch (\Exception $e) {
            $area = 'NO_AREA';
        }

        $source = $area;

        if ($area === 'adminhtml' && $this->adminSession->getUser()) {
            $source .= ' | Admin ID: ' . $this->adminSession->getUser()->getId();
        }

        if ($area === 'webapi_rest') {
            $source .= ' | REST: ' . $this->request->getRequestUri();
        }

        if ($area === 'frontend') {
            $source .= ' | Frontend: ' . $this->request->getRequestUri();
        }

        if ($area === 'graphql') {
            $source .= ' | GraphQL';
        }

        $this->logger->critical(
            "STATUS WRITE\n" .
            "SKU: " . $product->getSku() . "\n" .
            "ProductID: " . $product->getId() . "\n" .
            "OldStatus: " . $product->getOrigData('status') . "\n" .
            "NewStatus: " . $product->getStatus() . "\n" .
            "Source: " . $source . "\n" .
            "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n" .
            "-------------------------"
        );*/
    }
}