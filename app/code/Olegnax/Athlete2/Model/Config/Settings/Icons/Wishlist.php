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

class Wishlist implements ArrayInterface
{
    const TYPE_INHERIT = '';
    const TYPE_DEFAULT = 'heart';
    const TYPE_2 = 'heart2';
    const TYPE_3 = 'star_outline';
    const TYPE_4 = 'heart4';
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
                'value' => static::TYPE_INHERIT,
                'label' => $this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/icons/inherit.png' ),
            ],
            [
                'value' => static::TYPE_DEFAULT,
                'label' => $this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/icons/heart-01.png' ),
            ],
            [
                'value' => static::TYPE_2,
                'label' => $this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/icons/heart-02.png' ),
            ],
            [
                'value' => static::TYPE_3,
                'label' => $this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/icons/star-outline.png' ),
            ],
            [
                'value' => static::TYPE_4,
                'label' => $this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/icons/heart-04.png' ),
            ],
            [
                'value' => static::TYPE_CUSTOM,
                'label' =>$this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/icons/custom.png' ),
            ],
        ];
    }

}
