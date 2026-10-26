<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Product;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class EnergyRating extends AbstractSource
{
    public function getAllOptions()
    {
        $this->_options = [
            ['value' => '',     'label' => __('None')],
            ['value' => 'a',     'label' => __('A')],
            ['value' => 'b',  'label' => __('B')],
            ['value' => 'c',     'label' => __('C')],
            ['value' => 'd',  'label' => __('D')],
            ['value' => 'e',     'label' => __('E')],
            ['value' => 'f',  'label' => __('F')],
            ['value' => 'g',     'label' => __('G')],
        ];
        return $this->_options;
    }
}