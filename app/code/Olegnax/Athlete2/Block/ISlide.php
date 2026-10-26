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

namespace Olegnax\Athlete2\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Widget\Block\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magento\Cms\Model\BlockFactory;
use Magento\Framework\App\Http\Context as HttpContext;
use Olegnax\Athlete2\Model\DynamicStyle\EscapeCss;

class ISlide extends Template implements BlockInterface {

    protected $_template = 'widgets/islide.phtml';

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;
    /**
     * Json Serializer Instance
     *
     * @var Json
     */
    protected $json;
    /**
     * @var FilterProvider
     */
    protected $filterProvider;
    /**
     * @var EscapeCss
     */
    protected $escapeCss;

    public function __construct(
        Template\Context $context,
        BlockFactory $blockFactory,
        HttpContext $httpContext,
        EscapeCss $escapeCss,
        FilterProvider $filterProvider,
        Json $json,
        array $data = []
    ) {
        $this->blockFactory = $blockFactory;
        $this->httpContext = $httpContext;
        $this->escapeCss = $escapeCss;
        $this->filterProvider = $filterProvider;
        $this->json = $json;
        parent::__construct($context, $data);
    }

    public function getCacheKeyInfo($newval = []) {
        return array_merge([
            'OLEGNAX_ISLIDE_WIDGET',
            $this->_storeManager->getStore()->getId(),
            $this->_design->getDesignTheme()->getId(),
            $this->httpContext->getValue( Context::CONTEXT_GROUP),
            $this->json->serialize($this->getRequest()->getParams()),
            $this->json->serialize($this->getData()),
        ], parent::getCacheKeyInfo(), $newval);
    }

    public function getBlockId() {
        $name = $this->getNameInLayout();
        $name = preg_replace('/[^a-zA-Z0-9_]/i', '_', $name);
        $name .= substr(md5(microtime()), -5);
        return 'ox_' . $name;
    }
    public function getBlockContent($store_id = null)
    {
        $blockId = $this->getData('block_id');
        $block = $this->blockFactory->create()->load($blockId);
        $content = $block->getContent();
        if($content){
            /** @var Template $filter */
            $filter = $this->filterProvider->getBlockFilter();
            if (!empty($store_id)) {
                $filter = $filter->setStoreId($store_id);
            }
            return $filter->filter($content);
        }
        return '';
    }
    public function escapeCss($css){
        return $this->escapeCss->escapeCss($css);
    }
    /**
     * Render Inline styles.
     *
     * @param string $styles CSS styles to render.
     * @return string Rendered CSS styles wrapped in style tags.
     */
    public function renderStyles($styles = ''){
        return $this->escapeCss->renderStyles($styles);
    }
}
