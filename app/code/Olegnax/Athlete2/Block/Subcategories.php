<?php 
namespace Olegnax\Athlete2\Block;

use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Magento\Framework\Registry;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Olegnax\Athlete2\Helper\Image as AthleteImageHelper;
use Olegnax\Athlete2\Model\DynamicStyle\EscapeCss;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Olegnax\Athlete2\Service\GetSubcategoriesService;

class Subcategories extends Template implements BlockInterface
{
    const BASE_IMAGE_PATH = "catalog/category/";

    /**
     * @var string
     */
    private $_mediaUrl;
    private $_count;
    private $_category;
    private $_categories;
    private $_imagePath;
    protected $_registry;
    protected $_imageHelper;
    protected $_athleteImageHelper;
    protected $_storeManager;
    protected $_cacheKey;
    protected $layerResolver;
    protected $json;
    protected $escapeCss;
    protected $getSubcategoriesService;

    public function __construct(
        Template\Context $context,
        Registry $registry,
        LayerResolver $layerResolver,
        ImageHelper $imageHelper,
        AthleteImageHelper $athleteImageHelper,
        CategoryRepository $categoryRepository,
        StoreManagerInterface $storeManager,
        EscapeCss $escapeCss,
        GetSubcategoriesService $getSubcategoriesService,
        Json $json,
        array $data = []
    ) {
        $this->_registry = $registry;
        $this->layerResolver = $layerResolver;
        $this->_imageHelper = $imageHelper;
        $this->_athleteImageHelper = $athleteImageHelper;
        $this->categoryRepository = $categoryRepository;
        $this->_storeManager = $storeManager;
        $this->escapeCss = $escapeCss;
        $this->getSubcategoriesService = $getSubcategoriesService;
        $this->json = $json;
        parent::__construct($context, $data);
        $this->setCurrentCategory();
    }

    protected function _beforeToHtml()
    {
        if (!$this->hasData('template') && !$this->getTemplate()) {
            $this->setTemplate('Olegnax_Athlete2::widget/subcategories_grid.phtml');
        }
        return parent::_beforeToHtml();
    }

    /**
     * @param array $newval
     * @return array
     * @throws NoSuchEntityException
     */
    public function getCacheKeyInfo($newval = [])
    {
        return array_merge([
            'OLEGNAX_SUBCATEGORIES_WIDGET',
            $this->_storeManager->getStore()->getId(),
            $this->_design->getDesignTheme()->getId(),
            $this->httpContext->getValue(Context::CONTEXT_GROUP),
            $this->json->serialize($this->getData()),
        ], parent::getCacheKeyInfo(), $newval);
    }

    /**
     * @return string
     */
    public function getWidgetId()
    {
        return 'ox_cats_' . substr(md5(microtime()), -5);
    }

    public function loadProductsCount($subCats)
    {
        if ($this->getData('show_product_count') && $subCats) {
            $this->_count = 0;
            foreach ($subCats as $subcategory) {
                $this->_count++;
                $productCount =  $subcategory->getProductCollection()->count();
                $subcategory->setData('product_count', $productCount);
            }
        }
        return;
    }

    public function getCount(){
        return abs((int)$this->_count);
    }

    public function getImage($subcategory){
        $thumb = $this->getData('thumb');
        if($thumb === 'custom'){
            $url = $subcategory->getData('ox_category_thumb');
        } else{
            $url = $subcategory->getData('image');
        }
        if ($url && strpos((string)$url, static::BASE_IMAGE_PATH) == false) {
            $url = static::BASE_IMAGE_PATH . $url;
        }

        $this->_imagePath = $url;
        return $this->getImageUrl();
    }

    public function getImageSize(){
        $output  = '';
        $width = $this->getData('thumb_width');
        $height = $this->getData('thumb_height');
        if(!$width && $this->_imagePath){
            $width = $this->_athleteImageHelper->init($this->_imagePath)->getOriginalWidth();
        }
        if(!$height && $this->_imagePath){
            $height = $this->_athleteImageHelper->init($this->_imagePath)->getOriginalHeight();
        }
        if($width){
            $output .= ' width="' . abs((int)$width) . '"';
        }
        if($height){
            $output .= ' height="' . abs((int)$height) .'"';
        }   
        return  $output;
    }

    public function getImageUrl()
    {
        $path = $this->_imagePath;
        if((bool)$path) {
            $prefix = '/media';
            if (strpos((string)$path, $prefix) === 0) {
                $path = substr((string)$path, strlen($prefix));
            }
            $width = $this->getData('thumb_width');
            $height = $this->getData('thumb_height');
            if($width && $height){
                $url = $this->imageResized($path, [abs((int)$width), abs((int)$height)]);
            } else {
                $url = $this->getModuleMediaUrl($path);
            }
        } else {
            $url = $this->_imageHelper->getDefaultPlaceholderUrl('small_image');
        }

        return $url;
    }
    private function imageResized($file, $size = null, $attributes = ['aspect_ratio' => true, 'crop' => false]){
        if (!empty($size)) {
            if ($file) {
                $image = $this->_athleteImageHelper->init($file, $attributes)->adaptiveResize($size)->getUrl();
            } else {
                $image = false;
            }
            return $image;            
        }
    }
    
    public function getModuleMediaUrl($path = '')
    {
        return $this->_getMediaUrl() . preg_replace('/^(.*?)(\/pub)/i', '$2', $path);
    }

    protected function _getMediaUrl()
    {
        if (!$this->_mediaUrl) {
            $this->_mediaUrl = (string)$this->_storeManager
                ->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            $this->_mediaUrl = preg_replace('/\/$/', '', $this->_mediaUrl);
        }
        return $this->_mediaUrl;
    }

    public function getCategoryById($categoryId)
    {
        if(!$categoryId){
            return null;
        }       
        try {
            return $this->categoryRepository->get($categoryId);
        } catch (\Exception $e) {
            return null;
        }
    }
    public function getSubcategories(){
        $subCats = $this->getSubcategoriesService->setCategory($this->_category)->setCategories($this->_categories)->getSubcategories();
        if(!empty($subCats)){
            $this->loadProductsCount($subCats);
        }
        return $subCats;
    }

    protected function setCurrentCategory()
    {
        $categoriesList = $this->getCategories();
        if(!empty($categoriesList)){
            $this->_categories = explode(",", $categoriesList);
        }
        if(!(is_array($this->_categories) && count($this->_categories))){
            //@todo check if $categoryId === current category id and use layer/registry
            if($categoryId = (int)$this->getParentCategory()){
                $this->_category = $this->getCategoryById($categoryId);
            } else{
                $this->_category = $this->layerResolver->get()->getCurrentCategory();
                if(!$this->_category){
                    $this->_category =  $this->_registry->registry('current_category');
                }
            }
        }

        return $this->_category;
    }
    // protected function setCurrentCategory()
    // {
    //     $categoryId = (int)$this->getParentCategory();
    //     if(!$categoryId){
    //         $categoryId = (int)$this->getRequest()->getParam('id', false);
    //     }
    //     $this->_category = $this->getCategoryById($categoryId);
    //     return $this->_category;
    // }

    // @todo move to viewmodel
    /**
     * Css Styles
     *
     * @param string $id
     * @param string $styles
     * @return string
     */
    public function cssStyles($id = '', $styles = ''){

        $wrapperCss = $sectionCss = $catName = $numCss = '';
        
        if($this->getGap()){
            if($this->getVertical()){
                $wrapperCss .= 'row-gap:' . (int)$this->getGap() . 'px;';
            } else{
                $wrapperCss .= '--grid-margin:' . (int)$this->getGap()/2 . 'px;';
                $styles .= $id . ' .ox-cats.row{ margin-right: -' .  (int)$this->getGap()/2 .'px; margin-left: -' .  (int)$this->getGap()/2 .'px;}';
                $styles .= $id . ' .ox-cat{ padding-right: ' .  (int)$this->getGap()/2 .'px; padding-left: ' .  (int)$this->getGap()/2 .'px;}';
            }
        }
        if($this->getScrollbarColor()){
            $wrapperCss .= '--a2-scrollbar-color:' . $this->getScrollbarColor() . ';'; 
        }
        if($this->getScrollbarBgColor()){
            $wrapperCss .= '--a2-scrollbar-bg-color:' . $this->getScrollbarBgColor() . ';'; 
        }
        if($wrapperCss){
            $styles .= $id . ' .ox-cats{' . $wrapperCss . '}';
        }

        if($this->getPadding()){
            $sectionCss .= 'padding:' . $this->getPadding() . ';'; 
        }
        if($this->getBgColor()){
            $sectionCss .= 'background-color:' .  $this->getBgColor() . ';'; 
        }
        if($this->getTextColor()){
            $sectionCss .= 'color:' .  $this->getTextColor() . ';'; 
        }
        if($this->getBorderRadius()){
            $sectionCss .= 'border-radius:' .  (int)$this->getBorderRadius() . 'px; overflow: hidden;'; 
        }
        if($sectionCss){
            $styles .= $id . ' .ox-cat__inner{' . $sectionCss . '}';
        }

        if($this->getTextFontWeight()){
            $catName .= 'font-weight:' . (int)$this->getTextFontWeight() . ';'; 
        }
        if($this->getTextFontSize()){
            $catName .= 'font-size:' . $this->getTextFontSize() . ';'; 
        }
        if($catName){
            $styles .= $id . ' .ox-cat__name{' . $catName . '}';
        }
    
        if($this->getData('show_product_count')){
            if($this->getNumFontSize()){
                $numCss .= 'font-size:' . $this->getNumFontSize() . ';'; 
            }
            if($this->getNumFontWeight()){
                $numCss .= 'font-weight:' . (int)$this->getNumFontWeight() . ';'; 
            }
            if($this->getNumBgColor()){
                $numCss .= 'background-color:' . $this->getNumBgColor() . ';'; 
            }
            if($this->getNumTextColor()){
                $numCss .= 'color:' . $this->getNumTextColor() . ';'; 
            }
            if($numCss){
            $styles .= $id . ' .ox-cat__count{' . $numCss . '}';
            }
            if($this->getNumTextHoverColor()){
                $styles .= $id . ' .ox-cat:hover .ox-cat__count{color:' . $this->getNumTextHoverColor() . ';}'; 
            }
            if($this->getNumBgHoverColor()){
                $styles .= $id . ' .ox-cat:hover .ox-cat__count{background-color:' . $this->getNumBgHoverColor() . ';}'; 
            }
        }

        $hoverEffect = $this->getHoverEffect();
        if($hoverEffect === 'zoom-out'){
            $styles .= $id . ' .ox-cat__img-holder{transition: transform 0.4s ease;}';
            $styles .= $id . ' .ox-cat:hover .ox-cat__img-holder{transform: scale3d(0.94, 0.94, 0.94)}';
        } elseif($hoverEffect === 'zoom-in'){
            $styles .= $id . ' .ox-cat__img-holder{transition: transform 0.4s ease;}';
            $styles .= $id . ' .ox-cat:hover .ox-cat__img-holder{transform: scale3d(1.04, 1.04, 1.04)}';
        } elseif($hoverEffect === 'zoom-in-item'){
            $styles .= $id . ' .ox-cat .ox-cat__inner{transition: transform 0.4s ease;}';
            $styles .= $id . ' .ox-cat:hover .ox-cat__inner{transform: scale3d(1.04, 1.04, 1.04)}';
        } elseif($hoverEffect === 'zoom-out-item'){
            $styles .= $id . ' .ox-cat .ox-cat__inner{transition: transform 0.4s ease;}';
            $styles .= $id . ' .ox-cat:hover .ox-cat__inner{transform: scale3d(0.94, 0.94, 0.94)}';
        } elseif($hoverEffect === 'move-up'){
            $styles .= $id . ' .ox-cats{padding-top:10px;}';
            $styles .= $id . ' .ox-cat .ox-cat__inner{transition: transform 0.4s ease;}';
            $styles .= $id . ' .ox-cat:hover .ox-cat__inner{transform: translateY(-10px)}';
        }
        // elseif($hoverEffect === 'overlay'){
        //     $styles .= $id . ' .ox-cat:after {
        //         content: '';
        //         display: block;
        //         position: absolute;
        //         top: 0;
        //         left: 0;
        //         right: 0;
        //         bottom: 0;
        //         background-color: rgb(0 0 0 / 0%);
        //         z-index: 1;
        //         pointer-events: none;
        //         transition: all 0.4s ease;
        //     }';
        //     $styles .= $id . ' .ox-cat:hover:after {background-color: rgb(0 0 0 / 10%);}';
        // }
        if(!$this->getVertical()){
            if($this->getColumnWidth()){
                if($this->getColumnWidthFit()){
                    $col_width = (int)$this->getColumnWidth() . 'px';
                    $styles .= $id . ' .ox-cats{--col-width:' . $col_width . ';}';
                } else{
                    $col_width = (int)$this->getColumnWidth() . 'px';
                    $styles .= $id . ' .ox-cats{--cols:' . $col_width . ';--cols-s:var(--cols);--cols-m:var(--cols);--cols-l:var(--cols);}';
                }
            } else{
                $cols =  '';
                if($this->getColumnsMobile()){
                    $col_width = 100/(int)$this->getColumnsMobile() . '%';
                    $cols .='--cols:' . $col_width . ';';  
                }
                if($this->getColumnsTablet()){
                    $col_width = 100/(int)$this->getColumnsTablet() . '%';
                    $cols .='--cols-s:' . $col_width . ';';  
                }
                if($this->getColumnsDesktopSmall()){
                    $col_width = 100/(int)$this->getColumnsDesktopSmall() . '%';
                    $cols .='--cols-m:' . $col_width . ';';  
                }
                if($this->getColumnsDesktop()){
                    $col_width = 100/(int)$this->getColumnsDesktop() . '%';
                    $cols .='--cols-l:' . $col_width . ';';  
                }
                if($cols !== ''){
                    $styles .= $id . ' .ox-cats{ ' . $cols . ' }';
                }
            }
        }

        /* rounded */
        // if($this->getStyle() === 3 || $this->getStyle() === 4){
        //     $styles .= $id . ' .ox-cat__img{
        //         height: 100%;
        //         width: 100%;
        //         object-fit: cover;
        //         position: absolute;
        //     }
        //     ' . $id . ' .ox-cat__img-holder {
        //         height: 0;
        //         padding-bottom: 100%;
        //         border-radius: 50%;
        //         overflow: hidden;
        //     }';
        //     $styles .= $id . ' .ox-cat__inner{
        //         background-color: transparent;
        //         padding: 0;
        //     }';
        // }
        /* text overlay  */
        // if($this->getStyle() === 2){
        // }
        // if($this->getStyle() === 4){
        // }
        
        $sectionCss_mobile = $styles_mobile = $catName_mobile = $gap_mobile = $wrapperCss_mobile = '';
        if($this->getPaddingMobile()){
            $sectionCss_mobile .= 'padding:' . $this->getPaddingMobile() . ';'; 
        }
        if($sectionCss_mobile){
            $styles_mobile .= $id . ' .ox-cat__inner{' . $sectionCss_mobile . '}';
        }
        if($this->getTextFontSizeMobile()){
            $catName_mobile .= 'font-size:' . $this->getTextFontSizeMobile() . ';'; 
        }
        if($catName_mobile){
            $styles_mobile .= $id . ' .ox-cat__name{' . $catName_mobile . '}';
        }

        if($this->getGapMobile()){
            $gap_mobile = (int)$this->getGapMobile();
            if($this->getVertical()){
                $wrapperCss_mobile .= 'row-gap:' . $gap_mobile . 'px;';
            } else{
                $wrapperCss_mobile .= '--grid-margin:' . $gap_mobile/2 . 'px;';
                $styles_mobile .= $id . ' .ox-cats.row{ margin-right: -' .  $gap_mobile/2 .'px; margin-left: -' .  $gap_mobile/2 .'px;}';
                $styles_mobile .= $id . ' .ox-cat{ padding-right: ' .  $gap_mobile/2 .'px; padding-left: ' .  $gap_mobile/2 .'px;}';
            }
        }
        if($wrapperCss_mobile){
            $styles_mobile .= $id . ' .ox-cats{' . $wrapperCss_mobile . '}';
        }
        if($styles_mobile){
            $styles .= '@media only screen and (max-width: 479px){' . $styles_mobile . '}';
        }

        return $styles;
    }

    /**
     * Render Inline styles.
     *
     * @param string $id     Block Identifier
     * @param string $styles CSS styles to render.
     * @return string Rendered CSS styles wrapped in style tags.
     */
    public function renderStyles($id = '', $styles = ''){
        $cssStyles = $this->cssStyles($id, $styles);
        return $this->escapeCss->renderStyles($cssStyles);
    }

    public function escapeCss($css){
        return $this->escapeCss->escapeCss($css);
    }
}
