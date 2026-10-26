<?php
/**
 * Athlete2 Theme
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Olegnax.com license that is
 * available through the world-wide-web at this URL:
 * https://www.olegnax.com/license
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Olegnax
 * @package     Olegnax_Athlete2
 * @copyright   Copyright (c) 2023 Olegnax (http://www.olegnax.com/)
 * @license     https://www.olegnax.com/license
 */

namespace Olegnax\Athlete2\Helper;

use Exception;
use Magento\Catalog\Block\Product\Image as CatalogBlockProductImage;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\ReadHandler;
use Magento\Catalog\Model\Product\Image\ParamsBuilder;
use Magento\Catalog\Model\View\Asset\ImageFactory;
use Magento\Catalog\Model\View\Asset\PlaceholderFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Config\View;
use Magento\Framework\DataObject;
use Magento\Framework\Filesystem;
use Magento\Framework\Image\AdapterFactory;
use Magento\Framework\View\ConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Description of Image
 *
 * @author Master
 */
class ProductImage extends AbstractHelper
{
    const HOVER_IMAGE_ENABLED = 'athlete2_settings/products_listing/hover_image';
    const TEMPLATE = 'Magento_Catalog::product/image_with_borders.phtml';
    const HOVER_TEMPLATE = 'Magento_Catalog::product/hover_image_with_borders.phtml';
    const THUMB_TEMPLATE = 'Magento_Catalog::product/thumb_image_with_borders.phtml';
    /**
     * @var Image
     */
    protected $imageHelper;
    /**
     * @var ConfigInterface
     */
    protected $viewConfig;
    /**
     * @var View
     */
    protected $configView;
    /**
     * @var string
     */
    protected $_magentoVersion;
    /**
     * @var Video
     */
    protected $videoHelper;
    /**
     * @var AdapterFactory
     */
    protected $adapterFactory;
    /**
     * @var ParamsBuilder
     */
    private $imageParamsBuilder;
    protected $carousel;
    protected $imageCount;
    protected $onlySelected;
    protected $maxItems;
    protected $minItems;
    protected $optionsSet;
    protected $gridImageSizes;
    # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Optimize Magefan_Blog and Olegnax_Athlete2 (Olegnax_ProductSlider, Olegnax_MegaMenu, InlineCss, Olegnax_ProductLabel, ProductImage) storefront performance":
    # https://github.com/smokeday-com/m/issues/20
    private $imageParamsCache = [];
    private $resizedImageInfoCache = [];
    /**
     * ProductImage constructor.
     *
     * @param Context $context
     * @param Image $imageHelper
     * @param ConfigInterface $viewConfig
     * @param ParamsBuilder $imageParamsBuilder
     */
    public function __construct(
        Context $context,
        Image $imageHelper,
        ConfigInterface $viewConfig,
        ParamsBuilder $imageParamsBuilder,
        Video $videoHelper,
        AdapterFactory $adapterFactory
    ) {
        $this->imageHelper = $imageHelper;
        $this->viewConfig = $viewConfig;
        $this->imageParamsBuilder = $imageParamsBuilder;
        $this->videoHelper = $videoHelper;
        $this->adapterFactory = $adapterFactory;
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function checkDependencies() {
        try {
            $this->adapterFactory->create();
            return true;
        } catch (Exception $e) {
            $this->_logger->critical($e->getMessage());
            return false;
        }
    }

    /**
     * @param Product $product
     * @param string $imageId
     * @param string $imageIdHover
     * @param string $template
     * @param array $attributes
     * @param array $properties
     *
     * @return mixed
     */
    public function getImageHover(
        Product $product,
        $imageId,
        $imageIdHover,
        $template = self::HOVER_TEMPLATE,
        array $attributes = [],
        $properties = []
    ) {
        if (!$this->hasHoverImage($product, $imageId, $imageIdHover)
            && !$this->videoHelper->videoOnHover($product)
        ) {
            return $this->getImage($product, $imageId, self::TEMPLATE, $attributes, $properties);
        }
        if ($this->videoHelper->videoOnHover($product)) {
            $imageIdHover = $imageId;
        }

        $image = $this->_getImage($product, $imageId, $properties)->getUrl();
        $imageMiscParams = $this->getImageParams($imageId);
        $image_hoverMiscParams = $this->getImageParams($imageIdHover);

        $image_hover = $this->resizeImage(
            $product,
            $imageIdHover,
            [
                $imageMiscParams['image_width'],
                $imageMiscParams['image_height'],
            ],
            $properties
        )->getUrl();

        $data = [
            'data' => [
                'template' => $template,
                'product_id' => $product->getId(),
                'product' => $product,
                'image_id' => $imageId,
                'image_hover_id' => $imageIdHover,
                'image_url' => $image,
                'image_hover_url' => $image_hover,
                'label' => $this->getLabel($product, $imageMiscParams['image_type']),
                'label_hover' => $this->getLabel($product, $image_hoverMiscParams['image_type']),
                'width' => $imageMiscParams['image_width'],
                'height' => $imageMiscParams['image_height'],
                'ratio' => $this->getRatio($imageMiscParams['image_width'], $imageMiscParams['image_height']),
                'class' => $this->getClass($attributes),
                'custom_attributes' => $this->getStringCustomAttributes($attributes),
            ],
        ];

        return $this->_createTemplate($data);
    }

    /**
     * @param Product $product
     * @param string $imageId
     * @param string $imageId_hover
     *
     * @return bool
     */
    public function hasHoverImage(Product $product, $imageId, $imageId_hover)
    {
        if ($this->hoverImageEnabled()) {
            if ($imageId != $imageId_hover) {
                $_imageId = $this->getImageParams($imageId);
                $_imageId_hover = $this->getImageParams($imageId_hover);
                if ($_imageId['image_type'] !== $_imageId_hover['image_type']) {
                    $image = $product->getData($_imageId['image_type']);
                    $image_hover = $product->getData($_imageId_hover['image_type']);
                    return $image && $image_hover && 'no_selection' !== $image_hover && $image !== $image_hover;
                }
            }
        }

        return false;
    }

    public function hoverImageEnabled()
    {
        return (bool)$this->getConfig(static::HOVER_IMAGE_ENABLED);
    }

    public function getConfig($path, $storeCode = null)
    {
        return $this->getSystemValue($path, $storeCode);
    }

    public function getSystemValue($path, $storeCode = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        $value = $this->scopeConfig->getValue(
            $path,
            $scopeType,
            $storeCode
        );
        if (is_null($value)) {
            $value = '';
        }
        return $value;
    }

    /**
     * @param int $imageId
     *
     * @return array
     */
    public function getImageParams($imageId)
    {
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Optimize Magefan_Blog and Olegnax_Athlete2 (Olegnax_ProductSlider, Olegnax_MegaMenu, InlineCss, Olegnax_ProductLabel, ProductImage) storefront performance":
        # https://github.com/smokeday-com/m/issues/20
        if (isset($this->imageParamsCache[$imageId])) {
            return $this->imageParamsCache[$imageId];
        }
        $viewImageConfig = $this->getConfigView()->getMediaAttributes(
            'Magento_Catalog',
            Image::MEDIA_TYPE_CONFIG_NODE,
            $imageId
        );

        $imageMiscParams = $this->imageParamsBuilder->build($viewImageConfig);
        if (empty($imageMiscParams)) {
            $imageMiscParams = $this->getDefaultParams();
            $this->_logger->critical(sprintf('No options found for "%s" images!', $imageId));
        }

        $this->imageParamsCache[$imageId] = $imageMiscParams;
        return $imageMiscParams;
    }

    /**
     * Retrieve config view
     *
     * @return View
     */
    protected function getConfigView()
    {
        if (!$this->configView) {
            $this->configView = $this->viewConfig->getViewConfig();
        }
        return $this->configView;
    }

    /**
     * @return array
     */
    protected function getDefaultParams()
    {
        return [
            "image_type" => "small_image",
            "image_height" => 240,
            "image_width" => 240,
            "background" => [255, 255, 255],
            "quality" => 80,
            "keep_aspect_ratio" => true,
            "keep_frame" => true,
            "keep_transparency" => true,
            "constrain_only" => true,
        ];
    }

    /**
     * @param Product $product
     * @param string $imageId
     * @param string $template
     * @param array $attributes
     * @param array $properties
     *
     * @return mixed
     */
    public function getImage(
        Product $product,
        $imageId,
        $template = self::TEMPLATE,
        array $attributes = [],
        $properties = []
    ) {
        $imageMiscParams = $this->getImageParams($imageId);
        $originalFilePath = $product->getData($imageMiscParams['image_type']);

        $isPlaceholder = $originalFilePath === null || $originalFilePath === 'no_selection';
        $class = '\Magento\Catalog\Model\View\Asset\\' . ($isPlaceholder ? 'PlaceholderFactory' : 'ImageFactory');
        if (class_exists($class)) {
            /** @var ImageFactory|PlaceholderFactory $viewAssetImageFactory */
            $viewAssetImageFactory = ObjectManager::getInstance()->get($class);
            $image = $viewAssetImageFactory->create(
                [
                    'miscParams' => $imageMiscParams,
                    'filePath' => $originalFilePath,
                    'type' => $imageMiscParams['image_type']
                ]
            );
        } else {
            $image = $this->_getImage($product, $imageId, $properties);
        }

        $attributes = $attributes === null ? [] : $attributes;

        $data = [
            'data' => [
                'template' => $template,
                'product_id' => $product->getId(),
                'product' => $product,
                'image_id' => $imageId,
                'image_url' => $image->getUrl(),
                'label' => $this->getLabel($product, $imageMiscParams['image_type']),
                'width' => $imageMiscParams['image_width'],
                'height' => $imageMiscParams['image_height'],
                'ratio' => $this->getRatio($imageMiscParams['image_width'], $imageMiscParams['image_height']),
                'class' => $this->getClass($attributes),
                'custom_attributes' => $this->getStringCustomAttributes($attributes),
            ],
        ];

        return $this->_createTemplate($data);
    }

    /**
     * @param Product $product
     * @param string $imageId
     * @param array $properties
     *
     * @return Image
     */
    private function _getImage(Product $product, $imageId, $properties = [])
    {
        return $this->imageHelper->init($product, $imageId, $properties);
    }

    /**
     * @param Product $product
     *
     * @param string $imageType
     *
     * @return string
     */
    private function getLabel(Product $product, string $imageType): string
    {
        $label = "";
        if (!empty($imageType)) {
            $label = $product->getData($imageType . '_' . 'label');
        }
        if (empty($label)) {
            $label = $product->getName();
        }
        return (string)$label;
    }

    /**
     * Calculate image ratio
     *
     * @param $width
     * @param $height
     *
     * @return float
     */
    private function getRatio(int $width, int $height): float
    {
        if ($width && $height) {
            return $height / $width;
        }
        return 1.0;
    }

    /**
     * Retrieve image class for HTML element
     *
     * @param array $attributes
     *
     * @return string
     */
    private function getClass(array $attributes): string
    {
        return $attributes['class'] ?? 'product-image-photo';
    }

    /**
     * Retrieve image custom attributes for HTML element
     *
     * @param array $attributes
     *
     * @return string|array
     */
    private function getStringCustomAttributes(array $attributes)
    {
        if (!$this->compareVersion()) {
            return $attributes;
        }
        $result = [];
        foreach ($attributes as $name => $value) {
            $result[] = $name . '="' . $value . '"';
        }
        return !empty($result) ? implode(' ', $result) : '';
    }

    /**
     * @param $version
     *
     * @return bool
     */
    protected function compareVersion($version = '2.4.0')
    {
        return version_compare($this->getMagentoVersion(), $version, '<');
    }

    /**
     * @return string
     */
    private function getMagentoVersion()
    {
        if (!$this->_magentoVersion) {
            $this->_magentoVersion = ObjectManager::getInstance()->get(ProductMetadataInterface::class)->getVersion();
        }

        return $this->_magentoVersion;
    }

    /**
     * @param array $data
     *
     * @return CatalogBlockProductImage
     */
    private function _createTemplate($data = [])
    {
        return ObjectManager::getInstance()->create(CatalogBlockProductImage::class, $data);
    }

    /**
     * @param Product $product
     * @param string $imageId
     * @param array|int $size
     * @param array $properties
     *
     * @return Image
     */
    public function resizeImage(Product $product, $imageId, $size, $properties = [])
    {
        $size = $this->prepareSize($size);
        $image = $this->_getImage($product, $imageId, $properties);
        if ($this->checkDependencies()) {
            $image->resize($size[0], $size[1]);
        }

        return $image;
    }

    /**
     * @param array|int $size
     *
     * @return array
     */
    private function prepareSize($size)
    {
        if (is_array($size) && 1 >= count($size)) {
            $size = array_shift($size);
        }
        if (!is_array($size)) {
            $size = [$size, $size];
        }
        $size = array_map('floatval', $size);
        $size = array_map('abs', $size);
        return $size;
    }
    public function getImagesCount()
    {
        return $this->imageCount;
    }
    public function getThumbImageDots()
    {
        $output = '';
        if($this->imageCount > 1){
            $output .='<div class="ox-dots" data-count="' . $this->imageCount . '" data-current="0">';
            $output .= '<span class="ox-dots__counter">' . '<span class="ox-dots__current">1</span>' . $this->imageCount . '</span>';
            for($i=0; $i < $this->imageCount; $i++){
                $output .= '<span class="dot '. (($i < 1) ? 'active' : '') . '">' . ($i + 1) . '</span>';                
            }
            $output .='</div>';
        }
        return $output;
    }
    public function setOptions($options = []){
        if(!$this->optionsSet && is_array($options)){
            if(array_key_exists('carousel', $options)){
                $this->carousel = $options['carousel'];
            }
            if(array_key_exists('selected_only', $options)){
                $this->onlySelected = $options['selected_only'];
            }
            if(array_key_exists('max_items', $options)){
                $this->maxItems = $options['max_items'];
            }
            if(array_key_exists('min_items', $options)){
                $this->minItems = $options['min_items'];
            }
        }
    }

    public function getResizedCatalogImages(
        Product $product,
        $imageId,
        $imageId_hover,
        $size,
        $template = self::THUMB_TEMPLATE,
        array $attributes = [],
        $properties = []
    ) {
        $this->imageCount = 0;
        if(!$this->carousel){
            return $this->getResizedImageHover($product, $imageId, $imageId_hover, $size);
        }
        ObjectManager::getInstance()->get(ReadHandler::class)->execute($product);

        if(!$this->minItems) {
            $this->minItems = 2;
        }
        $images = $product->getMediaGalleryImages();   
        
        if(count($images) < $this->minItems){
            return $this->getResizedImageHover($product, $imageId, $imageId_hover, $size);
        }

        $_images = [];
        $imageHelper = ObjectManager::getInstance()->get(\Olegnax\Athlete2\Helper\Image::class);
        $media_path = ObjectManager::getInstance()->get(Filesystem::class)->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath('');
        $i = 0;
        $cssClass = '';

        foreach ($images as $image) {
            if($this->onlySelected === 'selected' && !$image->getThumbcarousel()){
                continue;
            }
            $id = $image->getId();
            $path = str_replace($media_path, '', $image->getPath());
            $image = $imageHelper->init($path, $properties);
            if(is_array($size)){
                if ($size[0] === null) {
                    $size[0] = $this->getGridImageSize('width');
                }
                if ($size[1] === null) {
                    $size[1] = $this->getGridImageSize('height');
                }
            }
            $image->adaptiveResize($size);

            $image_width = $image->getWidth();
            $image_height = $image->getHeight();
            $image = $image->getUrl();

            $_images[] = [
                'image_id' => $id,
                'image_url' => $image,
                'label' => $this->getLabel($product, ''),
                'width' => $image_width,
                'height' => $image_height,
                'ratio' => $this->getRatio($image_width, $image_height),
                'class' => $cssClass,
            ];
            $i++;
            $cssClass = 'hide';
            if($this->maxItems && $i >= $this->maxItems){
                break;
            }
        }
        $this->imageCount = $i;

        if($this->imageCount < $this->minItems){            
            return $this->getResizedImageHover($product, $imageId, $imageId_hover, $size);
        } else{
            $data = [
                'data' => [
                    'template' => $template,
                    'product_id' => $product->getId(),
                    'product' => $product,
                    'images' => $_images,
                    'width' => isset($_images[0]['width']) ? $_images[0]['width'] : 1,
                    'height' => isset($_images[0]['height']) ? $_images[0]['height'] : 1,
                    'ratio' => isset($_images[0]['ratio']) ? $_images[0]['ratio'] : 1,
                    'class' => $this->getClass($attributes),
                    'custom_attributes' => $this->getStringCustomAttributes($attributes),
                ],
            ];
            return $this->_createTemplate($data);
        }
    }

    private function getGridImageSize($value){
        if(!$this->gridImageSizes){
            $this->gridImageSizes = $this->viewConfig->getViewConfig()->getMediaAttributes('Magento_Catalog', Image::MEDIA_TYPE_CONFIG_NODE, 'category_page_grid');
        }
        return $this->gridImageSizes[$value];
    }
    /**
     * @param Product $product
     * @param string $imageId
     * @param string $imageId_hover
     * @param array|int $size
     * @param string $template
     * @param array $attributes
     * @param array $properties
     *
     * @return CatalogBlockProductImage|mixed
     */
    public function getResizedImageHover(
        Product $product,
        $imageId,
        $imageId_hover,
        $size,
        $template = self::HOVER_TEMPLATE,
        array $attributes = [],
        $properties = []
    ) {
        $this->imageCount = 0;
        if (!is_array($template)) {
            $template = [
                $template,
                self::TEMPLATE,
            ];
        } else {
            foreach ([self::HOVER_TEMPLATE, self::TEMPLATE] as $key => $value) {
                if (!isset($template[$key]) || empty($template[$key])) {
                    $template[$key] = $value;
                }
            }
        }
        if (!$this->hasHoverImage($product, $imageId, $imageId_hover)
            && !$this->videoHelper->videoOnHover($product)
        ) {
            return $this->getResizedImage($product, $imageId, $size, $template[1], $attributes, $properties);
        }
        if ($this->videoHelper->videoOnHover($product)) {
            $imageId_hover = $imageId;
        }
        $imageMiscParams = $this->getImageParams($imageId);
        if (empty($size)) {
            $size = [$imageMiscParams['image_width'], $imageMiscParams['image_height']];
        } elseif (is_array($size)) {
            foreach (['image_width', 'image_height'] as $key => $value) {
                if (!isset($size[$key]) || empty($size[$key])) {
                    $size[$key] = $imageMiscParams[$value];
                }
            }
        }

        $image = $this->resizeImage($product, $imageId, $size, $properties);
        $placeholder = $image->getDefaultPlaceholderUrl();
        $imageUrl = $image->getUrl();
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Optimize Magefan_Blog and Olegnax_Athlete2 (Olegnax_ProductSlider, Olegnax_MegaMenu, InlineCss, Olegnax_ProductLabel, ProductImage) storefront performance":
        # https://github.com/smokeday-com/m/issues/20
        if (isset($this->resizedImageInfoCache[$imageUrl])) {
            [
                $imageMiscParams['image_width'],
                $imageMiscParams['image_height']
            ] = $this->resizedImageInfoCache[$imageUrl];
        } else {
            try {
                [
                    $imageMiscParams['image_width'],
                    $imageMiscParams['image_height']
                ] = $image->getResizedImageInfo();
            } catch (Exception $e) {
                $this->_logger->error("OX Product Image: " . $e->getMessage());
                $imageMiscParams['image_width'] = $imageMiscParams['image_height'] = 1;
            }
            $this->resizedImageInfoCache[$imageUrl] = [
                $imageMiscParams['image_width'],
                $imageMiscParams['image_height']
            ];
        }
        $image = $imageUrl;
        $image_hover = $this->resizeImage($product, $imageId_hover, $size, $properties)->getUrl();
        $image_hoverMiscParams = $this->getImageParams($imageId_hover);
        if ($image_hover == $placeholder) {
            return $this->getResizedImage($product, $imageId, $size, $template[1], $attributes, $properties);
        }
        if ($image == $placeholder) {
            return $this->getImage($product, $imageId, $template[1], $attributes, $properties);
        }

        if (array_key_exists('class', $attributes)) {
            unset($attributes['class']);
        }

        $data = [
            'data' => [
                'template' => $template[0],
                'product_id' => $product->getId(),
                'product' => $product,
                'image_id' => $imageId,
                'image_hover_id' => $imageId_hover,
                'image_url' => $image,
                'image_hover_url' => $image_hover,
                'label' => $this->getLabel($product, $imageMiscParams['image_type']),
                'label_hover' => $this->getLabel($product, $image_hoverMiscParams['image_type']),
                'width' => $imageMiscParams['image_width'],
                'height' => $imageMiscParams['image_height'],
                'ratio' => $this->getRatio($imageMiscParams['image_width'], $imageMiscParams['image_height']),
                'class' => $this->getClass($attributes),
                'custom_attributes' => $this->getStringCustomAttributes($attributes),
            ],
        ];

        return $this->_createTemplate($data);
    }

    /**
     * @param Product $product
     * @param string $imageId
     * @param array|int $size
     * @param string $template
     * @param array $attributes
     * @param array $properties
     *
     * @return CatalogBlockProductImage|mixed
     */
    public function getResizedImage(
        Product $product,
        $imageId,
        $size,
        $template = self::TEMPLATE,
        array $attributes = [],
        $properties = []
    ) {
        $imageMiscParams = $this->getImageParams($imageId);
        if (empty($size)) {
            return $this->getImage($product, $imageId, $template, $attributes, $properties);
        }
        if (is_array($size)) {
            foreach (['image_width', 'image_height'] as $key => $value) {
                if (!isset($size[$key]) || empty($size[$key])) {
                    $size[$key] = $imageMiscParams[$value];
                }
            }
        }
        $image = $this->resizeImage($product, $imageId, $size, $properties);
        $imageMiscParams = $this->getImageParams($imageId);
        $placeholder = $image->getDefaultPlaceholderUrl();
        $imageUrl = $image->getUrl();
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Optimize Magefan_Blog and Olegnax_Athlete2 (Olegnax_ProductSlider, Olegnax_MegaMenu, InlineCss, Olegnax_ProductLabel, ProductImage) storefront performance":
        # https://github.com/smokeday-com/m/issues/20
        if (isset($this->resizedImageInfoCache[$imageUrl])) {
            [
                $imageMiscParams['image_width'],
                $imageMiscParams['image_height']
            ] = $this->resizedImageInfoCache[$imageUrl];
        } else {
            try {
                [
                    $imageMiscParams['image_width'],
                    $imageMiscParams['image_height']
                ] = $image->getResizedImageInfo();
            } catch (Exception $e) {
                $this->_logger->error("OX Product Image: " . $e->getMessage());
                $imageMiscParams['image_width'] = $imageMiscParams['image_height'] = 1;
            }
            $this->resizedImageInfoCache[$imageUrl] = [
                $imageMiscParams['image_width'],
                $imageMiscParams['image_height']
            ];
        }
        $image = $imageUrl;
        if ($image == $placeholder) {
            return $this->getImage($product, $imageId, $template, $attributes, $properties);
        }

        $data = [
            'data' => [
                'template' => $template,
                'product_id' => $product->getId(),
                'product' => $product,
                'image_id' => $imageId,
                'image_url' => $image,
                'label' => $this->getLabel($product, $imageMiscParams['image_type']),
                'width' => $imageMiscParams['image_width'],
                'height' => $imageMiscParams['image_height'],
                'ratio' => $this->getRatio($imageMiscParams['image_width'], $imageMiscParams['image_height']),
                'class' => $this->getClass($attributes),
                'custom_attributes' => $this->getStringCustomAttributes($attributes),
            ],
        ];

        return $this->_createTemplate($data);
    }

    /**
     * @param Product $product
     * @param string $image
     * @param array|int $size
     * @param array $properties
     *
     * @return string
     */
    public function getUrlResizedImage(Product $product, $image, $size, $properties = [])
    {
        $image = $this->resizeImage($product, $image, $size, $properties);
        return $image->getUrl();
    }
}
