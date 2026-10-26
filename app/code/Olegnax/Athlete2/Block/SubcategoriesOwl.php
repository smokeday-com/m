<?php 
namespace Olegnax\Athlete2\Block;

class SubcategoriesOwl extends Subcategories
{
    protected function _beforeToHtml()
    {
        if (!$this->hasData('template') && !$this->getTemplate()) {
            $this->setTemplate('Olegnax_Athlete2::widget/subcategories_owlcarousel.phtml');
        }
        return parent::_beforeToHtml();
    }
    /**
     * @param array $options
     * @param bool $json
     * @return array|bool|false|string
     */
    public function getCarouselOptions($options = [], $json = true)
    {
        $options['margin'] = (int)$this->getGap() ?: 30;
        $options['loop'] = (bool)$this->getLoop();
        $options['dots'] = (bool)$this->getDots();
        $options['nav'] = (bool)$this->getNav();
        $options['items'] = (int)$this->getColumnsDesktop();
        $options['lazyLoad'] = true;
        $options['rewind'] = (bool)$this->getRewind();
        $options['responsive'] = [
            '0' => [
                'items' => max(1, ((int)$this->getColumnsMobile() ?: 2)),
            ],
            '640' => [
                'items' => max(1, ((int)$this->getColumnsTablet() ?: 3)),
            ],
            '1025' => [
                'items' => max(1, ((int)$this->getColumnsDesktopSmall() ?: 4)),
            ],
            '1160' => [
                'items' => max(1, ((int)$this->getColumnsDesktop() ?: 5)),
            ],
        ];

        if ($json) {
            return $this->json->serialize($options);
        }

        return $options;
    }
    /**
     * Override the cssStyles method.
     * Append additional styles to the existing styles.
     *
     * @param string $id
     * @param string $styles
     * @return string
     */
    public function cssStyles($id = '', $styles = '')
    {
        $width = (int)$this->getData('thumb_width');
        if($width){
            $styles .= $id . ' .owl-carousel .owl-item .ox-cat__img{
                width:' . $width . 'px;            
            }';
        }

        // Call the parent method
        return parent::cssStyles($id, $styles);
    }
}
