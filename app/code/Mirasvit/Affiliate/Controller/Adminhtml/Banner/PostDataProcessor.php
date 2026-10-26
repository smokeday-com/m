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

namespace Mirasvit\Affiliate\Controller\Adminhtml\Banner;

use Mirasvit\Affiliate\Api\Data\BannerInterface;

class PostDataProcessor
{
    public function filter(array $data): array
    {
        if ($data[BannerInterface::TYPE] == BannerInterface::TYPE_TEXT) {
            $data[BannerInterface::SOURCE] = $data['source_text'];
        } elseif ($data[BannerInterface::TYPE] == BannerInterface::TYPE_IMAGE) {
            if (isset($data['source_image'][0])) {
                $data[BannerInterface::SOURCE] = $data['source_image'][0]['name'];
            } else {
                $data[BannerInterface::SOURCE] = '';
            }
        }

        return $data;
    }
}
