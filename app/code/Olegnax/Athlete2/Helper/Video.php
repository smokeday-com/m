<?php
/**
 * @author      Olegnax
 * @package     Olegnax_Athlete2
 * @copyright   Copyright (c) 2024 Olegnax (http://olegnax.com/). All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Olegnax\Athlete2\Helper;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem\Glob;
use Olegnax\Athlete2\Service\GetVideoService;

class Video extends AbstractHelper
{
    /**
     * @var GetVideoService
     */
    private $videoService;

    /**
     * @var array
     */
    protected $_productVideos;

    /**
     * Video constructor.
     * @param Context $context
     */
    public function __construct(
        Context $context,
        GetVideoService $videoService
    ) {
        $this->videoService = $videoService;
        $this->_productVideos = [];
        parent::__construct($context);
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function issetVideo($product)
    {
        $files = $this->_getVideoFiles($product);

        return !empty($files);
    }
    /**
     * @param Product $product
     * @param array $config
     * @return string
     */
    public function getVideo($product, $config = [])
    {
        $files = $this->_getVideoFiles($product);
        return $this->videoService->getVideo($files,$config);
    }
    /**
     * @param Product $product
     * @return string[]
     */
    private function _getVideoFiles($product)
    {

        if (!$product) {
            return [];
        }
        $productId = $product->getId();
        if (!array_key_exists($productId, $this->_productVideos)) {
            $this->_productVideos[$productId] = [];
        }
        if (empty($this->_productVideos[$productId])) {
            $videoPath = (string)$this->getProductData($product, 'ox_gallery_video');
            $videoPath = ltrim($videoPath, '\\\/');
            if (!empty($videoPath)) {
                $videoAbsolutePath = $this->videoService->getAbsolutePath($videoPath);
                $videoReplaceAbsolutePath = preg_replace('#\.[a-z0-9]{3,}$#i', '', $videoAbsolutePath);
                $this->_productVideos[$productId] = Glob::glob($videoReplaceAbsolutePath . '\.*');
                if (empty($this->_productVideos[$productId])) {
                    $this->_productVideos[$productId] = [];
                    if (file_exists($videoAbsolutePath)) {
                        $this->_productVideos[$productId][] = $videoAbsolutePath;
                    }
                }
            }
        }
        return $this->_productVideos[$productId];
    }

    /**
     * @param Product $product
     * @param string $key
     * @return mixed|null
     */
    private function getProductData($product, $key)
    {
        $productId = $product->getId();
        $data = $product->getData($key);
        if (null === $data) {
            $product = $product->load($productId);
            if ($product->getId() == $productId) {
                $data = $product->getData($key);
            }
        }

        return $data;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function stopOnClick($product)
    {
        return (bool)$this->getProductData($product, 'ox_gallery_video_stop_on_click');
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function loopVideo($product)
    {
        return (bool)$this->getProductData($product, 'ox_gallery_video_loop');
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function videoPosition($product)
    {
        return (int)$this->getProductData($product, 'ox_gallery_video_index') ?: 2;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function videoOnHover($product)
    {
        return (bool)$this->getProductData($product, 'ox_gallery_video_listing_hover');
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function autoplayVideo($product)
    {
        return (bool)$this->getProductData($product, 'ox_gallery_video_autoplay');
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function showControls($product)
    {
        return (bool)$this->getProductData($product, 'ox_gallery_video_controls');
    }
}