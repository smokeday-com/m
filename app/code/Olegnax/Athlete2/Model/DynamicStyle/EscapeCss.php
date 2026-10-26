<?php

namespace Olegnax\Athlete2\Model\DynamicStyle;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
// use Magento\Framework\App\ObjectManager;

class EscapeCss
{
    /**
     * @var SecureHtmlRenderer
     */
    private $secureRenderer;

    /**
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        // $this->secureRenderer = $secureRenderer ?? ObjectManager::getInstance()->get(SecureHtmlRenderer::class);
        $this->secureRenderer = $secureRenderer;
    }

    public function escapeCss($css){
        if (!empty($css)) {
            $css = preg_replace('/[\r\n\t]/', ' ', $css);
            $css = preg_replace('/[\r\n\t ]{2,}/', ' ', $css);
            $css = preg_replace('/\s+(\:|\;|\{|\})\s+/', '\1', $css);
            $css = preg_replace('/<[^<>]+>(.*?)<\/[^<>]+>/m', '/* Forbidden tags in styles */', $css);
            return $css;
        }
        return '';
    }

    public function renderStyles($styles = ''){
        if($styles){
            if($this->secureRenderer){
                return $this->secureRenderer->renderTag('style', [], $this->escapeCss($styles), false);
            } else{
                return '<style type="text/css">' . $this->escapeCss($styles) . '</style>';
            }

        }
        return '';
    }
}