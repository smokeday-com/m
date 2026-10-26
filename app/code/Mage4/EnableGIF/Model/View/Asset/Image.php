<?php
namespace Mage4\EnableGIF\Model\View\Asset;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\ContextInterface;

class Image extends \Magento\Catalog\Model\View\Asset\Image
{
    /**
     * Image type of image (thumbnail,small_image,image,swatch_image,swatch_thumb)
     *
    /**
     * @var ContextInterface
     */
    protected $context;
    /**
     * @var string
     */
    protected $mediaFormatUrl;

    /**
     * Get catalog image URL.
     *
     * @return string
     * @throws LocalizedException
     */
    /**
     * Image constructor.
     *
     * @param string $filePath
     */
    public function getUrl()
    {
        $file = $this->getFilePath();
        $pathEnding = substr($file, strpos($file, ".") + 1);
        if ($pathEnding == "gif" || $pathEnding == "GIF") {
            $path = substr($file, strpos($file, "/") + 1);
            return $this->getContext()->getBaseUrl() . DIRECTORY_SEPARATOR . $path;
        } else {
            return parent::getUrl();
        }
    }
}
