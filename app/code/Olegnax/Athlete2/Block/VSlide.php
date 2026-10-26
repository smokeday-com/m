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
 * @copyright   Copyright (c) 2024 Olegnax (http://www.olegnax.com/)
 * @license     https://www.olegnax.com/license
 */

namespace Olegnax\Athlete2\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Widget\Block\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magento\Cms\Model\BlockFactory;
use Magento\Framework\App\Http\Context as HttpContext;
use Olegnax\Athlete2\Model\DynamicStyle\EscapeCss;

class VSlide extends Template implements BlockInterface {

    protected $_template = 'widgets/vslide.phtml';

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
    protected $escapeCss;

    public function __construct(
        Template\Context $context,
        BlockFactory $blockFactory,
        HttpContext $httpContext,
        EscapeCss $escapeCss,
        Json $json,
        array $data = []
    ) {
        $this->blockFactory = $blockFactory;
        $this->httpContext = $httpContext;
        $this->escapeCss = $escapeCss;
        $this->json = $json;
        parent::__construct($context, $data);
    }

    public function getCacheKeyInfo($newval = []) {
        return array_merge([
            'OLEGNAX_VSLIDE_WIDGET',
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

    public function getContent(){
        $output = $this->getData('content');
        if(!empty($output)){
            $output = explode("||", $output);
        }
        return $output;
    }

    public function generateKeyframes($numItems) {
        $keyframes = '';
        $moveStep = 100 / ($numItems + 1);

        $animDelay = (100 / $numItems) * 0.4;
        $gapDelay = (100 / $numItems ) * 0.6;
        $p = 0;
        for ($i = 0; $i < $numItems; $i++) {
            $keyframes .= round($p,2) . "% { transform: translateY(-" . round(($i * $moveStep), 3) . "%); }\n";
            $p += $gapDelay;
            $keyframes .= round($p,2) . "% { transform: translateY(-" . round(($i * $moveStep), 3) . "%); }\n";
            $p += $animDelay;
        }

        // Add the last keyframe
        $keyframes .= "100% { transform: translateY(-" . round(($numItems * $moveStep), 3)  . "%); }\n";
    
        return $keyframes;
    }
}
