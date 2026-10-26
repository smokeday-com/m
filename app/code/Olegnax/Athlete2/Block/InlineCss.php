<?php 
namespace Olegnax\Athlete2\Block;

use Olegnax\Athlete2\Block\SimpleTemplate;
use Olegnax\Athlete2\Model\DynamicStyle\EscapeCss;

class InlineCss extends SimpleTemplate
{   
    protected $escapeCss;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        EscapeCss $escapeCss,
        array $data = []
    ) {
        $this->escapeCss = $escapeCss;
        parent::__construct($context, $data);       
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
