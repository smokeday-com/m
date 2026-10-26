<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PageCache\Observer;

use Magento\Framework\App\Cache\Tag\Resolver;
use Magento\Framework\App\PageCache\Cache;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\PageCache\Model\Cache\Type;
use Magento\PageCache\Model\Config;
use Zend_Cache;

/**
 * Observer used to cache by tags when using built-in full page cache
 */
class FlushCacheByTags implements ObserverInterface
{
    /**
     * @var Cache
     *
     * @deprecated 100.1.0
     */
    protected $_cache;

    /**
     * Application config object
     *
     * @var Config
     */
    protected $_config;

    /**
     * @var Type
     */
    private $fullPageCache;

    /**
     * Invalidation tags resolver
     *
     * @var Resolver
     */
    private $tagResolver;

    /**
     * @param Config $config
     * @param Cache $cache
     * @param Type $fullPageCache
     * @param Resolver $tagResolver
     */
    public function __construct(
        Config $config,
        Cache $cache,
        Type $fullPageCache,
        Resolver $tagResolver
    ) {
        $this->_config = $config;
        $this->_cache = $cache;
        $this->fullPageCache = $fullPageCache;
        $this->tagResolver = $tagResolver;
    }

    /**
     * If Built-In caching is enabled it collects array of tags of incoming object and asks to clean cache.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->_config->getType() === Config::BUILT_IN && $this->_config->isEnabled()) {
            $object = $observer->getEvent()->getObject();
            if (!is_object($object)) {
                /**
                 * 2026-10-26
                 * "Invalidate Built-in FPC and Cloudflare Edge Cache on mass product attribute/status updates
                 * and catalogrule_after_apply": https://github.com/smokeday-com/m/issues/14
                 * Root Cause:
                 * When `FrontController::dispatchPostDispatchEvents()` dispatches `controller_action_postdispatch_*`
                 * events (such as `controller_action_postdispatch_adminhtml_catalog_product_massStatus` and
                 * `controller_action_postdispatch_adminhtml_catalog_product_action_attribute_save`), or when
                 * `catalogrule_after_apply` / `assigned_theme_changed` are dispatched, the event payload does not
                 * contain an `'object'` key (`$observer->getEvent()->getObject()` is `null`). Furthermore, when
                 * catalog indexers are set to `Update by Schedule`, `Magento\Framework\Indexer\CacheContext` is
                 * empty during mass attribute/status actions, so `UpdateAttributesFlushCache` cleans zero tags.
                 * Solution:
                 * Resolve affected product tags (`cat_p_<id>`, plus `cat_p` on `massStatus` so category listings
                 * reflect newly enabled/disabled products) from `Magento\Catalog\Helper\Product\Edit\Action\Attribute`
                 * or request parameters, and clean `CLEANING_MODE_ALL` on `catalogrule_after_apply` and
                 * `assigned_theme_changed`.
                 */
                $eventName = strtolower((string) $observer->getEvent()->getName());
                if (
                    $eventName === 'controller_action_postdispatch_adminhtml_catalog_product_massstatus'
                    || $eventName === 'controller_action_postdispatch_adminhtml_catalog_product_action_attribute_save'
                ) {
                    $tags = $this->resolveMassProductActionTags($observer, $eventName);
                    if (!empty($tags)) {
                        $this->fullPageCache->clean(
                            Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
                            array_unique($tags)
                        );
                    } else {
                        $this->fullPageCache->clean(Zend_Cache::CLEANING_MODE_ALL);
                    }
                } elseif (
                    $eventName === 'catalogrule_after_apply'
                    || $eventName === 'assigned_theme_changed'
                ) {
                    $this->fullPageCache->clean(Zend_Cache::CLEANING_MODE_ALL);
                }
                return;
            }
            $tags = $this->tagResolver->getTags($object);

            if (!empty($tags)) {
                $this->fullPageCache->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array_unique($tags));
            }
        }
    }

    /**
     * Resolve affected product cache tags for admin mass status and mass attribute update postdispatch events.
     *
     * @param Observer $observer
     * @param string $eventName
     * @return string[]
     */
    private function resolveMassProductActionTags(Observer $observer, string $eventName): array
    {
        $productIds = [];
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            if (class_exists(\Magento\Catalog\Helper\Product\Edit\Action\Attribute::class)) {
                $attributeHelper = $objectManager->get(
                    \Magento\Catalog\Helper\Product\Edit\Action\Attribute::class
                );
                $helperIds = $attributeHelper->getProductIds();
                if (is_array($helperIds)) {
                    $productIds = $helperIds;
                }
            }
        } catch (\Throwable $e) {
            $productIds = [];
        }
        if (empty($productIds)) {
            $request = $observer->getEvent()->getData('request');
            if ($request instanceof \Magento\Framework\App\RequestInterface) {
                $selected = $request->getParam('selected');
                if (is_array($selected)) {
                    $productIds = $selected;
                }
            }
        }
        $tags = [];
        foreach ($productIds as $productId) {
            $id = (int) $productId;
            if ($id > 0) {
                $tags[] = 'cat_p_' . $id;
            }
        }
        if (
            !empty($tags)
            && $eventName === 'controller_action_postdispatch_adminhtml_catalog_product_massstatus'
        ) {
            $tags[] = 'cat_p';
        }
        return $tags;
    }
}
