<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PageCache\Model\Cache;

/**
 * System / Cache Management / Cache type "Full Page Cache"
 *
 */
class Type extends \Magento\Framework\Cache\Frontend\Decorator\TagScope
{
    /**
     * Cache type code unique among all cache types
     */
    const TYPE_IDENTIFIER = 'full_page';

    /**
     * Cache tag used to distinguish the cache type from all other cache
     */
    const CACHE_TAG = 'FPC';

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Emit Cache-Tag HTTP header from Magento\Framework\App\PageCache\Kernel and automatically purge Cloudflare Edge Cache on cache invalidation events":
    # https://github.com/smokeday-com/m/issues/12
    private array $purgedCloudflareTags = [];
    private bool $purgedCloudflareAll = false;

    /**
     * @param \Magento\Framework\App\Cache\Type\FrontendPool $cacheFrontendPool
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Magento\Framework\App\Cache\Type\FrontendPool $cacheFrontendPool,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        parent::__construct($cacheFrontendPool->get(self::TYPE_IDENTIFIER), self::CACHE_TAG);
        $this->eventManager = $eventManager;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $mode
     * @param array $tags
     * @return bool
     */
    public function clean($mode = \Zend_Cache::CLEANING_MODE_ALL, array $tags = [])
    {
        $this->eventManager->dispatch('adminhtml_cache_refresh_type');
        $result = parent::clean($mode, $tags);
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Emit Cache-Tag HTTP header from Magento\Framework\App\PageCache\Kernel and automatically purge Cloudflare Edge Cache on cache invalidation events":
        # https://github.com/smokeday-com/m/issues/12
        $this->purgeCloudflare((string)$mode, $tags);
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Implement event-driven and prioritized cron Full Page Cache & Cloudflare Edge Cache warmer (page-cache:warm / magento_pagecache_warm)":
        # https://github.com/smokeday-com/m/issues/19
        Warmer::enqueueTags((string)$mode, $tags);
        return $result;
    }

    # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Emit Cache-Tag HTTP header from Magento\Framework\App\PageCache\Kernel and automatically purge Cloudflare Edge Cache on cache invalidation events":
    # https://github.com/smokeday-com/m/issues/12
    public function purgeCloudflare(string $mode = \Zend_Cache::CLEANING_MODE_ALL, array $tags = []): void
    {
        if ($this->purgedCloudflareAll || !function_exists('curl_init')) {
            return;
        }
        try {
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            /** @var \Magento\Framework\App\DeploymentConfig $deploymentConfig */
            $deploymentConfig = $om->get(\Magento\Framework\App\DeploymentConfig::class);
            $cfConfig = $deploymentConfig->get('cloudflare');
            if (!is_array($cfConfig) || empty($cfConfig['zone_id']) || empty($cfConfig['api_token'])) {
                return;
            }
            $zoneId = (string)$cfConfig['zone_id'];
            $apiToken = (string)$cfConfig['api_token'];

            $tagsToPurge = [];
            if ($mode === \Zend_Cache::CLEANING_MODE_ALL || empty($tags)) {
                $tagsToPurge = [self::CACHE_TAG];
                $this->purgedCloudflareAll = true;
            } else {
                foreach ($tags as $tag) {
                    $tagStr = trim((string)$tag);
                    if ($tagStr === '') {
                        continue;
                    }
                    $lower = strtolower($tagStr);
                    if ($lower === 'fpc' || $lower === 'mage') {
                        $tagsToPurge = [self::CACHE_TAG];
                        $this->purgedCloudflareAll = true;
                        break;
                    }
                    if (!isset($this->purgedCloudflareTags[$lower])) {
                        $this->purgedCloudflareTags[$lower] = true;
                        $tagsToPurge[] = $tagStr;
                    }
                }
                if (count($tagsToPurge) > 90) {
                    $tagsToPurge = [self::CACHE_TAG];
                    $this->purgedCloudflareAll = true;
                }
            }

            if (empty($tagsToPurge)) {
                return;
            }

            $endpoint = sprintf('https://api.cloudflare.com/client/v4/zones/%s/purge_cache', $zoneId);
            foreach (array_chunk($tagsToPurge, 30) as $chunk) {
                $ch = curl_init($endpoint);
                if ($ch === false) {
                    break;
                }
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => 2,
                    CURLOPT_TIMEOUT => 4,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $apiToken,
                        'Content-Type: application/json',
                    ],
                    CURLOPT_POSTFIELDS => json_encode(['tags' => array_values($chunk)]),
                ]);
                curl_exec($ch);
                curl_close($ch);
            }
        } catch (\Throwable $e) {
            // Do not interrupt application flow if Cloudflare API is temporarily unreachable
        }
    }
}
