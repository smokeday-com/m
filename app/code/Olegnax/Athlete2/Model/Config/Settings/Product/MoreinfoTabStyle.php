<?php /**/
namespace Olegnax\Athlete2\Model\Config\Settings\Product;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\View\Asset\Repository;

class MoreinfoTabStyle implements ArrayInterface
{
    protected $_assetRepo;

    public function __construct(
        Repository $assetRepo
    ) {
        $this->_assetRepo = $assetRepo;
    }

    public function toOptionArray() {
        $optionArray = [ ];
        $array		 = $this->toArray();
        foreach ( $array as $key => $value ) {
            $optionArray[] = [ 'value' => $key, 'label' => $value ];
        }

        return $optionArray;
    }

    public function toArray() {
        return [
            '' => $this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/more-info-tab-01.png' ),
            '2' => $this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/more-info-tab-02.png' ),
        ];
    }
}
