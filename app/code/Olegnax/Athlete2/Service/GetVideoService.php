<?php

namespace Olegnax\Athlete2\Service;

use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Glob;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Escaper;
use Psr\Log\LoggerInterface;

class GetVideoService
{
    const ORDER_MIME = [
        'video/webm',
		'video/mp4',
        'video/ogg',
    ];

    const TEMPLATE_VIDEO = 'Olegnax_Athlete2::video.phtml';

    /**
     * @var ReadInterface
     */
    protected $_mediaDirectory;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var LayoutInterface
     */
    protected $_layout;
        /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * GetVideoService constructor
     * @param LayoutInterface $layout
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        LayoutInterface $layout,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->_layout = $layout;
        $this->_mediaDirectory = $filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $this->_storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * @param Product $product
     * @param array $config
     * @return string
     * @throws NoSuchEntityException
     */
    public function getVideo($files, $config = [])
    {
        if (!empty($files)) {
            try {
                $mimeFiles = [];
                foreach ($files as $file) {
                    $fileMime = $this->_detectMimeType($file);
                    $mimeFiles[$fileMime] = $this->getUrlPath($file);
                }
                if (!empty($mimeFiles)) {
                    uksort($mimeFiles, [$this, 'sortByMime']);

                    return $this->getLayout()
                        ->createBlock(
                            Template::class,
                            '',
                            [
                                'data' => array_replace(
                                    [
                                        'mime_files' => $mimeFiles,
                                    ],
                                    $config
                                ),
                            ]
                        )
                        ->setTemplate(static::TEMPLATE_VIDEO)
                        ->toHtml();
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                return '';
            }
        }

        return '';
    }
    /**
     * @param string
     * @return string[]
     */
    public function getFiles($url)
    {
        if (!$url) {
            return [];
        }
        $videoFiles = [];
        try {
            $videoPath = ltrim($url, '\\\/');
            if (!empty($videoPath)) {
                $videoAbsolutePath = $this->getAbsolutePath($videoPath);
                $videoReplaceAbsolutePath = preg_replace('#\.[a-z0-9]{3,}$#i', '', $videoAbsolutePath);
                $videoFiles = Glob::glob($videoReplaceAbsolutePath . '\.*');
                if (empty($videoFiles)) {
                    if (file_exists($videoAbsolutePath)) {
                        $videoFiles[] = $videoAbsolutePath;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return [];
        }
        return $videoFiles;
    }

    /**
     * @param array $attributes
     * @return string
     */
    public static function prepareAttributes(array $attributes, $instance)
    {
        $attributes = array_filter($attributes);
        if (empty($attributes)) {
            return '';
        }
        $html = '';
        foreach ($attributes as $attributeName => $attributeValue) {
            if (is_bool($attributeValue)) {
                if ($attributeValue) {
                    $html .= sprintf(
                        ' %s',
                        $attributeName
                    );
                }
            } else {
                $html .= sprintf(
                    ' %s="%s"',
                    $attributeName,
                    $instance->escapeHtmlAttr($attributeValue)
                );
            }
        }

        return $html;
    }
    /**
     * @param string $path
     * @return string
     */
    public function getAbsolutePath(
        $path = ''
    ) {
        return $this->_mediaDirectory->getAbsolutePath($path);
    }

    /**
     * Internal method to detect the mime type of a file
     *
     * @param string $file File
     * @return string Mimetype of given file
     */
    protected function _detectMimeType($file)
    {
        $result = '';
        if (class_exists('finfo', false)) {
            $const = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
            $mime = @finfo_open($const);

            if (!empty($mime)) {
                $result = finfo_file($mime, $file);
            }

            unset($mime);
        }

        if (empty($result) && (function_exists('mime_content_type')
                && ini_get('mime_magic.magicfile'))) {
            $result = mime_content_type($file);
        }

        if (empty($result)) {
            $result = 'application/octet-stream';
        }

        return $result;
    }

    /**
     * @param string $path
     * @return string
     * @throws NoSuchEntityException
     */
    protected function getUrlPath(
        $path = ''
    ) {
        $path = str_replace($this->getAbsolutePath(), '', $path);
        $path = $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $path;

        return $path;
    }

    /**
     * @return LayoutInterface
     */
    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * @param string $itemPrev
     * @param string $itemNext
     * @return int
     */
    public function sortByMime(
        $itemPrev,
        $itemNext
    ) {
        $indexPrev = in_array($itemPrev, static::ORDER_MIME) ? array_search($itemPrev, static::ORDER_MIME) : 9999;
        $indexNext = in_array($itemNext, static::ORDER_MIME) ? array_search($itemNext, static::ORDER_MIME) : 9999;
        if ($indexPrev == $indexNext) {
            return 0;
        } elseif ($indexPrev > $indexNext) {
            return 1;
        }
        return -1;
    }   
}
