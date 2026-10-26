<?php

namespace Mage4\EnableGIF\Model\Product\Attribute\Media;

use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryExtensionFactory;
use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;
use Magento\ProductVideo\Model\Product\Attribute\Media\ExternalVideoEntryConverter;
use Magento\Catalog\Model\Product\Attribute\Backend\Media\ImageEntryConverter;

class FixExternalVideoEntryConverter extends ExternalVideoEntryConverter
{
    protected ImageEntryConverter $_imageEntryConverter;

    public function __construct(
        \Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterfaceFactory $mediaGalleryEntryFactory,
        \Magento\Framework\Api\DataObjectHelper                                     $dataObjectHelper,
        \Magento\Framework\Api\Data\VideoContentInterfaceFactory                    $videoEntryFactory,
        ProductAttributeMediaGalleryEntryExtensionFactory                           $mediaGalleryEntryExtensionFactory,
        ImageEntryConverter                                                         $_imageEntryConverter
    )
    {
        parent::__construct(
            $mediaGalleryEntryFactory,
            $dataObjectHelper,
            $videoEntryFactory,
            $mediaGalleryEntryExtensionFactory
        );
        $this->_imageEntryConverter = $_imageEntryConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function convertFrom(ProductAttributeMediaGalleryEntryInterface $entry)
    {
        $videoContent = $entry->getExtensionAttributes()->getVideoContent();
        if ($videoContent) {
            return parent::convertFrom($entry);
        }
        return $this->_imageEntryConverter->convertFrom($entry);
    }

}
