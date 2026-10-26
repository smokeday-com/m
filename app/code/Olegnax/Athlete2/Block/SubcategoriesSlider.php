<?php 
namespace Olegnax\Athlete2\Block;

class SubcategoriesSlider extends Subcategories
{
    protected function _beforeToHtml()
    {
        if (!$this->hasData('template') && !$this->getTemplate()) {
            $this->setTemplate('Olegnax_Athlete2::widget/subcategories_slider.phtml');
        }
        return parent::_beforeToHtml();
    }
}
