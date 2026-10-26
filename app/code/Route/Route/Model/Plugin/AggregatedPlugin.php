<?php
/**
 * A Route Magento Extension that adds secure shipping
 * insurance to your orders
 *
 * Php version 7.0^
 *
 * @category
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */

namespace Route\Route\Model\Plugin;

use Closure;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Framework\View\Layout\File\Collector\Aggregated;

/**
 * To keep backward compatibility
 *
 * Php version 7.0^
 *
 * @category
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class AggregatedPlugin
{
    /**
     * Product meta data object.
     *
     * @var ProductMetadata
     */
    protected $productMetadata;

    /**
     * Object constructor.
     *
     * @param ProductMetadata $productMetadata Product meta data object.
     */
    public function __construct(
        ProductMetadata $productMetadata
    ) {
        $this->productMetadata = $productMetadata;
    }

    /**
     * Plugin for getFiles method. Get order archive layout update file index
     * and save it in registry to be able to recognize it in further processing.
     * It will be applied for magento version lower than 2.1.
     *
     * @param Aggregated $subject Subject object.
     * @param Closure $proceed Closure object.
     * @param ThemeInterface $theme Theme object.
     * @param string $filePath File path.
     *
     * @return array
     */
    public function aroundGetFiles(
        Aggregated $subject,
        Closure $proceed,
        ThemeInterface $theme,
        $filePath
    ) {

        $files = $proceed($theme, $filePath);

        if ($filePath !== 'sales_order_grid.xml') {
            return $files;
        }

        $version = $this->productMetadata->getVersion();

        if (version_compare($version, '2.2') < 0) {
            foreach ($files as $index => $file) {
                if ($file->getModule() !== 'Route_Route') {
                    continue;
                }
                unset($files[$index]);
            }

            $files_old = $proceed($theme, 'sales_order_grid_old.xml');

            return array_merge($files, $files_old);
        }

        return $files;
    }
}
