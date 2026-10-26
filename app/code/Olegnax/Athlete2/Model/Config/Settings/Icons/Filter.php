<?php
/**
 * @author      Olegnax
 * @package     Olegnax_Athlete2
 * @copyright   Copyright (c) 2023 Olegnax (http://olegnax.com/). All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Olegnax\Athlete2\Model\Config\Settings\Icons;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\View\Asset\Repository;

class Filter implements ArrayInterface
{
    const TYPE_DEFAULT = '';
    const TYPE_2 = 'filters2';
    const TYPE_3 = 'filters3';
    const TYPE_4 = 'filters4';
    const TYPE_5 = 'filters5';
    const TYPE_6 = 'filters6';
    const TYPE_7 = 'filters7';
    const TYPE_CUSTOM = 'custom';

    /**
     * @var array
     */
    private $styleArray;

    protected $_assetRepo;

    public function __construct(
        Repository $assetRepo
    ) {
        $this->_assetRepo = $assetRepo;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $array = [];
        foreach ($this->toOptionArray() as $item) {
            $array[$item['value']] = $item['label'];
        }
        return $array;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => static::TYPE_DEFAULT,
                'label' => $this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/icons/filters-01.png' ),
            ],
            [
                'value' => static::TYPE_2,
                'label' => $this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/icons/filters-02.png' ),
            ],
            [
                'value' => static::TYPE_3,
                'label' => $this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/icons/filters-03.png' ),
            ],
            [
                'value' => static::TYPE_4,
                'label' => $this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/icons/filters-04.png' ),
            ],
            [
                'value' => static::TYPE_5,
                'label' => $this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/icons/filters-05.png' ),
            ],
            [
                'value' => static::TYPE_6,
                'label' => $this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/icons/filters-06.png' ),
            ],
            [
                'value' => static::TYPE_7,
                'label' => $this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/icons/filters-07.png' ),
            ],
            [
                'value' => static::TYPE_CUSTOM,
                'label' =>$this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/icons/custom.png' ),
            ],
        ];
    }

}
