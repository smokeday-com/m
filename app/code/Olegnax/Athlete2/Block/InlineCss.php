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

    # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Optimize Magefan_Blog and Olegnax_Athlete2 (Olegnax_ProductSlider, Olegnax_MegaMenu, InlineCss, Olegnax_ProductLabel, ProductImage) storefront performance":
    # https://github.com/smokeday-com/m/issues/20
    protected function _construct()
    {
        $this->addData([
            'cache_lifetime' => 86400,
        ]);
        parent::_construct();
    }

    public function getCacheKeyInfo()
    {
        return array_merge(
            parent::getCacheKeyInfo(),
            [
                'OLEGNAX_ATHLETE2_INLINE_CSS',
                (string)$this->getNameInLayout(),
                (int)$this->_storeManager->getStore()->getId(),
                (int)$this->_loadObject(\Olegnax\Core\Helper\Helper::class)->isMobileTheme(),
                (string)$this->getConfig('athlete2_design/appearance_custom/dynamic_file_arg'),
                (string)$this->getConfig('athlete2_design/appearance_custom/inline_css'),
            ]
        );
    }

    protected function _saveCache($data)
    {
        if (
            trim((string)$data) === ''
            && $this->getTemplate() === 'Olegnax_Athlete2::dynamic_css/inline-css.phtml'
            && $this->getConfig('athlete2_design/appearance_custom/inline_css')
        ) {
            return false;
        }
        return parent::_saveCache($data);
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
