<?php

declare(strict_types=1);

namespace Olegnax\Athlete2\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Olegnax\Athlete2\Service\GetTotalSoldAmountService;
use Olegnax\Athlete2\Service\GetCurrentProductService;
use Olegnax\Athlete2\Service\GetProductStockService;
use Magento\Framework\Escaper;
use Olegnax\Athlete2\Model\DynamicStyle\EscapeCss;

/**
 *  StockInfoViewModel
 */
class StockInfoViewModel implements ArgumentInterface
{
    /**
     * @var Escaper
     */
    private $escaper;
    /**
     * @var EscapeCss
     */
    protected $escapeCss;

    protected $currentProduct;
    protected $currentProductId;
    protected $totalSold;

    /**
     * @var GetTotalSoldAmountService
     */
    private $totalSoldAmountService;

    /**
     * @var GetCurrentProductService
     */
    private $currentProductService;

    /**
     * @var GetProductStockService
     */
    private $productStockService;

    /**
     * @param GetTotalSoldAmountService $totalSoldAmountService
     */
    public function __construct(
        GetProductStockService $productStockService,
        GetCurrentProductService $currentProductService,
        GetTotalSoldAmountService $totalSoldAmountService,
        Escaper $escaper,
        EscapeCss $escapeCss
    )
    {
        $this->productStockService = $productStockService;
        $this->currentProductService = $currentProductService;
        $this->totalSoldAmountService = $totalSoldAmountService;
        $this->escaper = $escaper;
        $this->escapeCss = $escapeCss;
    }

    public function getCurrentProductId(){
        if(!$this->currentProductId){
            $this->currentProductId = $this->currentProductService->getProductId();
        }
        return $this->currentProductId;
    }

    public function getCurrentProduct(){
        if(!$this->currentProduct){
            $this->currentProduct= $this->currentProductService->getProduct();
        }
        return $this->currentProduct;
    }

    // public function getStockLeft($product = null)
    // {
    //     $_product = $product ?: $this->getCurrentProduct();
    //     if(!$_product){
    //         return null;
    //     }
    //     return $this->productStockService->getProductQuantity($_product, $_product->getSku());
    // }

    // public function getStockTotal($productId = null)
    // {
    //     $_productId = $productId ?: $this->getCurrentProductId();
    //     if(!$_productId){
    //         return 0;
    //     }
    //     return (int)($this->getTotalSoldAmount() + $this->productStockService->getBaseStockQty($_productId));
    // }

    public function getTotalSoldAmount($productId = null)
    {
        $_productId = $productId ?: $this->getCurrentProductId();
        if(!$_productId){
            return 0;
        }
        if (!$this->totalSold) {
            $this->totalSold = $this->totalSoldAmountService->getTotalSoldAmount($_productId);
        }

        return $this->totalSold;
    }
    
    protected function getConfigurableProductQuantity($product, $qtyOnly = false)
    {
        $output = [];
        // $qtyTotal = 0;
        // $qtyBaseTotal = 0;
        $childProducts = $product->getTypeInstance()->getUsedProducts($product);

        foreach ($childProducts as $childProduct) {
            $productId = $childProduct->getId();
            $qty = (int)$this->productStockService->getSimpleProductQuantity($childProduct);
            if(!$qtyOnly){
                $qtyBase = (int)$this->productStockService->getBaseStockQty($productId);
                $totalSold = (int)$this->getTotalSoldAmount($productId);
                // $qtyTotal += $_qty;
                // $qtyBaseTotal += $_qtyBase;
                $output[$productId] = ['qty' => $qty, 'qty_base' => $qtyBase, 'total_sold'=> $totalSold];
            } else{
                $output[$productId] = ['qty' => $qty];
            }
        }
        // if($qtyTotal && is_array($qty)){
        //     $output['total'] = [$qtyTotal, $qtyBaseTotal];
        // }
        return $output;
    }

    // protected function getBundleProductQuantity($product)
    // {
    //     $qty = 0;
    //     $selections = $product->getTypeInstance()->getSelectionsCollection($product->getOptionsIds(), $product);

    //     foreach ($selections as $selection) {
    //         $qty += (int)$this->productStockService->getSimpleProductQuantity($selection);
    //     }

    //     return $qty;
    // }

    // protected function getGroupedProductQuantity($product)
    // {
    //     $qty = 0;
    //     $associatedProducts = $product->getTypeInstance()->getAssociatedProducts($product);

    //     foreach ($associatedProducts as $associatedProduct) {
    //         $qty += (int)$this->productStockService->getSimpleProductQuantity($associatedProduct);
    //     }

    //     return $qty;
    // }

    protected function getSimpleProductQuantity($product, $qtyOnly = false){
        $productId = $product->getId();
        
        $qty = (int)$this->productStockService->getSimpleProductQuantity($product);
        if(!$qtyOnly){
            $qtyBase = (int)$this->productStockService->getBaseStockQty($productId);
            $totalSold = (int)$this->getTotalSoldAmount($productId);
            if(!$qty || $qty > $qtyBase || $qty > ($qtyBase +  $totalSold)){
                return null;
            }
            return [$productId => ['qty' => $qty, 'qty_base' => $qtyBase, 'total_sold'=> $totalSold]];
        }
        return [$productId => ['qty' => $qty]];        
    }

    public function getProductQuantity($product = null, $qtyOnly = false)
    {
        $_product = $product ?: $this->getCurrentProduct();
        if ($_product !== null && $_product instanceof \Magento\Catalog\Model\Product) {
            $productType = $_product->getTypeId();
            switch ($productType) {
                case 'virtual':
                case 'simple':
                    return $this->getSimpleProductQuantity($_product, $qtyOnly);
                case 'configurable':
                    return $this->getConfigurableProductQuantity($_product, $qtyOnly);
                // case 'bundle':
                //     return $this->getBundleProductQuantity($_product);
                // case 'grouped':
                //     return $this->getGroupedProductQuantity($_product);
                default:
                    return '';
            }
        }
        return null;
    }

    public function replaceVars($inputString, $qty = 0, $total = 0)
    {
        if(empty($inputString)){
            return '';
        }
        $inputString = $this->escapeString($inputString);
        $outputString = '';
        $patterns = [
            '/{{qty}}/',
            '/{{total_stock}}/'
        ];
        $replacements = [
            '<span class="qty">' . (int)$qty . '</span>',
            '<span class="amount">' . (int)$total . '</span>'
        ];
        
        $outputString = preg_replace($patterns, $replacements, $inputString);
        
        return $outputString;
    }

    /**
     * Converts incoming data to string format and escapes special characters.
     *
     * @return string
     */
    private function escapeString($data)
    {
        return $this->escaper->escapeHtml((string)$data, ['span, a']);
    }

    public function renderStyles($styles){
        return $this->escapeCss->renderStyles($styles);
    }

}
