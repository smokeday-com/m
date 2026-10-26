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

namespace Mirasvit\Affiliate\Plugin;

use Magento\Catalog\Ui\Component\Listing\Columns\Price as ColumnsPrice;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Core\Service\CompatibilityService;

/**
 * @see \Magento\Catalog\Ui\Component\Listing\Columns\Price::prepareDataSource
 */
class BaseCurrencyAmountFormatPlugin
{

    private $context;

    private $storeManager;

    private $priceCurrency;

    private $request;

    private $actionList
        = [
            'affiliate_transaction_index',
            'affiliate_withdraw_index',
            'affiliate_account_index',
        ];

    private $namespaceList
        = [
            'affiliate_transaction_listing',
            'affiliate_withdraw_listing',
            'affiliate_account_listing',
        ];

    public function __construct(
        ContextInterface       $context,
        StoreManagerInterface  $storeManager,
        PriceCurrencyInterface $priceCurrency,
        RequestInterface       $request
    ) {
        $this->context       = $context;
        $this->storeManager  = $storeManager;
        $this->priceCurrency = $priceCurrency;
        $this->request       = $request;
    }

    public function aroundPrepareDataSource(ColumnsPrice $columnsPrice, \Closure $proceed, array $dataSource)
    {
        if (!$this->isApplicable()) {
            return $proceed($dataSource);
        }

        if (isset($dataSource['data']['items'])) {
            $store        = $this->storeManager->getStore(
                $this->context->getFilterParam('store_id', Store::DEFAULT_STORE_ID)
            );
            $baseCurrency = $store->getBaseCurrency();

            $fieldName = $columnsPrice->getName();

            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$fieldName])) {
                    $item[$fieldName] = $this->priceCurrency->format(
                        (float)sprintf("%F", $item[$fieldName]),
                        false,
                        PriceCurrencyInterface::DEFAULT_PRECISION,
                        $store,
                        $baseCurrency
                    );
                }
            }
        }

        return $dataSource;
    }

    private function isApplicable(): bool
    {
        $version = CompatibilityService::getVersion();
        if (version_compare($version, '2.4.6', '<')) {
            return false;
        }

        $action = $this->request->getFullActionName();
        if (in_array($action, $this->actionList)) {
            return true;
        }

        $namespace = (string)$this->request->getParam('namespace');
        if ($action == 'mui_index_render' && in_array($namespace, $this->namespaceList)) {
            return true;
        }

        return false;
    }
}
