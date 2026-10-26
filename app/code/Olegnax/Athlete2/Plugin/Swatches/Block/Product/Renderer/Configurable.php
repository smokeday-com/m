<?php

declare(strict_types=1);

namespace Olegnax\Athlete2\Plugin\Swatches\Block\Product\Renderer;

use Magento\Swatches\Block\Product\Renderer\Configurable as ConfigurableBlock;

class Configurable
{
    public function afterGetJsonConfig(ConfigurableBlock $subject, $result)
    {
        $config = json_decode($result, true);

        if (!isset($config['sku'])) {
            // Fetch child product SKUs
            $childSkus = [];
            foreach ($subject->getAllowProducts() as $product) {
                // if ($product->isSaleable()) {
                //     $childSkus[$product->getId()] = $product->getSku();
                // }
                $childSkus[$product->getId()] = $product->getSku();
            }

            // Add child product SKUs to the JSON configuration
            if(count($childSkus)){
                $config['sku'] = $childSkus;
            }
            $result = json_encode($config);
        }

        return $result;
    }
}