<?php
/* Save thumbcarousel checkbox value on product save. Checkbox can be found on product edit page, in a modal which appear when clicked on image. */
namespace Olegnax\Athlete2\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;

class ProductSaveAfter implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Gallery
     */
    protected $galleryResource;

    /**
     * @param RequestInterface $request
     * @param Gallery $galleryResource
     */
    public function __construct(
        RequestInterface $request,
        Gallery $galleryResource
    ) {
        $this->request = $request;
        $this->galleryResource = $galleryResource;
    }

    /**
     * @inheritdoc
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $data = $this->request->getPostValue();

        if (isset($data['product']['media_gallery']['images'])) {
            $product = $observer->getProduct();
            $mediaGallery = $product->getMediaGallery();

            if (isset($mediaGallery['images'])) {
                foreach ($mediaGallery['images'] as $image) {
                    $val = !empty($image['thumbcarousel']) ? (int)$image['thumbcarousel'] : 0;

                    $condition = ['value_id = ?' => $image['value_id']];
                    $this->galleryResource->getConnection()->update(
                        $this->galleryResource->getMainTable(),
                        ['thumbcarousel' => $val],
                        $condition
                    );
                }
            }
        }
    }
}
