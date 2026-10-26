<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-affiliate
 * @version   1.5.2
 * @copyright Copyright (C) 2024 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Affiliate\Service;

use Mirasvit\Affiliate\Api\Data\BannerInterface;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Filesystem\Driver\File as DriverFile;
use Magento\Framework\Filesystem\Io\File as IoFile;

class BannerService
{
    private $configProvider;

    private $driverFile;

    private $ioFile;

    private $urlBuilder;

    /**
     * @var \Magento\Framework\App\Response\Http
     */
    private $response;

    public function __construct(
        ConfigProvider    $configProvider,
        DriverFile        $driverFile,
        IoFile            $ioFile,
        UrlInterface      $urlBuilder,
        ResponseInterface $response
    ) {
        $this->configProvider = $configProvider;
        $this->driverFile     = $driverFile;
        $this->ioFile         = $ioFile;
        $this->urlBuilder     = $urlBuilder;
        $this->response       = $response;
    }

    public function getSourceUrl(BannerInterface $banner): ?string
    {
        return $this->configProvider->getMediaUrl($banner->getSource());
    }

    public function gerReferralUrl(string $identifier, string $url): string
    {
        $param = $this->configProvider->getReferralParam();

        if (strpos($url, '?') !== false) {
            $url = $url . '&' . $param . '=' . $identifier;
        } else {
            $url = $url . '?' . $param . '=' . $identifier;
        }

        return $url;
    }

    public function getHtmlCode(BannerInterface $banner, AccountInterface $account): string
    {
        $ref    = $this->configProvider->getReferralParam();
        $params = [
            $ref => $account->getIdentifier(),
            'b'  => $banner->getId(),
        ];

        $link = $banner->getLink() . '?' . http_build_query($params);

        if ($banner->getType() == BannerInterface::TYPE_IMAGE) {
            $width  = $banner->getWidth();
            $height = $banner->getHeight();
            $src    = $this->urlBuilder->getUrl('affiliate/banner/view', [
                '_query' => [
                    $this->configProvider->getReferralParam() => $account->getIdentifier(),
                    'b'                                       => $banner->getId(),
                ],
            ]);
            $html   = '<a href="' . $link . '"><img src="' . $src . '" width="' . $width . 'px" height="' . $height . 'px"></a>';
        } else {
            $html = '<a href="' . $link . '">' . $banner->getSource() . '</a>';
        }

        return $html;
    }

    public function echoBanner(BannerInterface $banner): void
    {
        $sourceFile = $this->configProvider->getBaseMediaPath() . '/' . $banner->getSource();

        $mimeTypes = [
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
        ];

        $pathInfo = $this->ioFile->getPathInfo($sourceFile);
        $type     = $pathInfo['extension'];

        if (isset($mimeTypes[$type])) {
            $contentType = $mimeTypes[$type];
        } else {
            $contentType = 'image/jpeg';
        }

        $fileStat = $this->driverFile->stat($sourceFile);
        $this->response->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', $contentType, true)
            ->setHeader('Content-Length', $fileStat['size'])
            ->setHeader('Last-Modified', date('r'), true);
        $this->response->sendHeaders();
        $this->response->setBody($this->driverFile->fileGetContents($sourceFile));
    }

    public function echoPixel(): void
    {
        $this->response->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Content-type', 'image/png', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Last-Modified', date('r'), true);
        $this->response->sendHeaders();

        $pixel = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII='
        );

        $this->response->setBody($pixel);
    }
}
